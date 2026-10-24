<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 過去の釣行から、選んだ日の条件に合うものを探す（FN-16・FN-11）
 * 時期（選んだ日の月と前後1か月）はいつも条件に入れる。魚種ごとに釣れる季節があるため（#158）
 * ぴったり一致（時期＋時間帯＋潮）に釣れた記録がなければ、時期と時間帯 → 時期だけ と少しずつ条件をゆるめる
 * プランナーとカルテの判断ビューで同じ決まりにするため、ここ1か所で決める
 * どれもデータベースで数える：1つの釣り場（カルテ）は matchQuery()、釣り場ごと（プランナー）は matchBySpot()（#166・#168）
 */
class ConditionMatcher
{
    /** 一致のレベルと、画面に出す名前（上ほど条件が厳しい） */
    public const LABELS = [
        'exact' => 'ぴったり一致',
        'time' => '時期と時間帯が一致',
        'season' => '時期だけ一致',
    ];

    /** どのレベルにも釣れた記録がないときの並び順（いちばん下） */
    public const NO_CATCH_RANK = 3;

    /** 釣れた釣行かどうか（釣果が1匹でもある） */
    private const CAUGHT_SQL = 'EXISTS (SELECT 1 FROM catches WHERE catches.trip_id = trips.id)';

    /** ダミーの人の釣行かどうか（#211） */
    private const SAMPLE_SQL = 'trips.user_id IN (SELECT id FROM users WHERE users.is_sample = 1)';

    /** 記録の内訳で数える項目と、データベースでの書き方（#211） */
    private const BREAKDOWN = [
        'time_of_day' => 'trips.time_of_day',
        'tide' => 'trips.tide',
        'year' => 'YEAR(trips.went_at)',
    ];

    /**
     * 選んだ日の「時期」に入る月（その月と前後1か月。12月なら 11・12・1月）
     *
     * @return list<int>
     */
    public static function seasonMonths(Carbon $date): array
    {
        $month = $date->month;

        return [($month + 10) % 12 + 1, $month, $month % 12 + 1];
    }

    /** 時期の書き方（例：7〜9月、12〜2月） */
    public static function seasonLabel(Carbon $date): string
    {
        [$from, , $to] = self::seasonMonths($date);

        return "{$from}〜{$to}月";
    }

    /**
     * 一致のレベルごとの条件（上ほど厳しい）。条件の決まりはここ1か所（#158・#166）
     * condition は画面に出す言葉、test は釣行1件を確かめる形、scope はデータベースで絞る形
     *
     * @return array<string, array{condition: string, test: \Closure, scope: \Closure}>
     */
    private function levels(Carbon $date, string $tide, ?string $timeOfDay): array
    {
        $months = self::seasonMonths($date);
        $season = self::seasonLabel($date);
        // 何年でもよい。月だけを見る
        $inSeason = fn(Trip $trip) => in_array($trip->went_at->month, $months, true);
        $seasonScope = fn(Builder $query) => $query->whereRaw('MONTH(trips.went_at) IN (?, ?, ?)', $months);

        $levels = [
            'exact' => [
                'condition' => $season . ($timeOfDay ? '・' . $timeOfDay : '') . '・' . $tide,
                'test' => fn(Trip $trip) => $inSeason($trip) && $trip->matchesCondition($tide, $timeOfDay),
                'scope' => fn(Builder $query) => $seasonScope($query)
                    ->where('trips.tide', $tide)
                    ->when($timeOfDay !== null, fn($query) => $query->where('trips.time_of_day', $timeOfDay)),
            ],
        ];
        // 時間帯が「指定なし」なら、時期と時間帯は時期だけと同じ意味なので飛ばす
        if ($timeOfDay !== null) {
            $levels['time'] = [
                'condition' => "{$season}・{$timeOfDay}（潮は問わない）",
                'test' => fn(Trip $trip) => $inSeason($trip) && $trip->time_of_day === $timeOfDay,
                'scope' => fn(Builder $query) => $seasonScope($query)->where('trips.time_of_day', $timeOfDay),
            ];
        }
        $levels['season'] = [
            'condition' => "{$season}（時間帯・潮は問わない）",
            'test' => $inSeason,
            'scope' => $seasonScope,
        ];

        return $levels;
    }

    /**
     * データベースで数える（#166）。釣行を読み込まないので、記録が増えても重くならない
     * matched は選んだレベルの釣行を取り出すクエリ、test は釣行1件がそのレベルに当てはまるか（カルテの履歴の印に使う）
     *
     * @param  Builder  $trips  見てよい釣行のクエリ。どれを入れてよいかは呼ぶ側で絞っておく
     */
    public function matchQuery(Builder $trips, Carbon $date, string $tide, ?string $timeOfDay): array
    {
        $results = collect($this->levels($date, $tide, $timeOfDay))->map(function (array $level, string $name) use ($trips) {
            $matched = $level['scope'](clone $trips);

            return [
                'level' => $name,
                'label' => self::LABELS[$name],
                'condition' => $level['condition'],
                'matched' => $matched,
                'test' => $level['test'],
                'visits' => (clone $matched)->count(),
                'caught' => (clone $matched)->has('catches')->count(),
                // そのうちダミーの人の釣行（#211）
                'samples' => (clone $matched)->whereIn('trips.user_id', User::where('is_sample', true)->select('id'))->count(),
            ];
        });

        // いちばん多い値。同じ数なら新しい釣行に出てきたもの
        return $this->choose($results, $date, $tide, $timeOfDay, function (array $used, string $column) {
            return (string) (clone $used['matched'])->has('catches')
                ->reorder()
                ->selectRaw("trips.{$column} as value, COUNT(*) as trip_count, MAX(trips.went_at) as last_went_at")
                ->groupBy("trips.{$column}")
                ->orderByDesc('trip_count')
                ->orderByDesc('last_went_at')
                ->value('value');
        });
    }

    /**
     * 釣り場ごとにデータベースで数える（プランナー用。#168）
     * レベルごとに「釣り場ごとの回数・釣れた回数」を GROUP BY でまとめて数え、釣り場ごとに使うレベルを選ぶ
     * 返す中身は、釣り場の番号 => matchQuery() と同じ形（matched は持たない）。記録がない釣り場も入る
     *
     * @param  Builder  $trips  見てよい釣行のクエリ（どの釣り場・どの記録を入れてよいかは呼ぶ側で絞っておく）
     * @param  list<int>  $spotIds  並べる釣り場の番号
     * @return array<int, array>
     */
    public function matchBySpot(Builder $trips, array $spotIds, Carbon $date, string $tide, ?string $timeOfDay): array
    {
        $levels = $this->levels($date, $tide, $timeOfDay);

        // レベルごと・釣り場ごとの回数と釣れた回数（1レベル1回の問い合わせ）
        $counts = [];
        foreach ($levels as $name => $level) {
            $counts[$name] = $level['scope'](clone $trips)
                ->reorder()
                ->toBase()
                ->selectRaw('trips.spot_id, COUNT(*) as visits, COUNT(CASE WHEN ' . self::CAUGHT_SQL . ' THEN 1 END) as caught, COUNT(CASE WHEN ' . self::SAMPLE_SQL . ' THEN 1 END) as samples')
                ->groupBy('trips.spot_id')
                ->get()
                ->keyBy('spot_id');
        }

        // 釣り場ごとに、どのレベルを使うか（釣れた記録がある、いちばん厳しいレベル）
        $chosenLevel = [];
        foreach ($spotIds as $spotId) {
            $chosenLevel[$spotId] = collect(array_keys($levels))
                ->first(fn(string $name) => (int) data_get($counts[$name]->get($spotId), 'caught', 0) > 0) ?? 'exact';
        }

        // ハンコの青の値：使うレベルの、釣れた釣行でいちばん多い値（同じ数なら新しい釣行に出てきたもの）
        $mostCommon = [];
        foreach ($levels as $name => $level) {
            $spots = array_keys(array_filter($chosenLevel, fn(string $chosen) => $chosen === $name));
            if ($spots === []) {
                continue;
            }
            foreach (['time_of_day', 'tide'] as $column) {
                $rows = $this->matchedQuery($trips, $name, $date, $tide, $timeOfDay)
                    ->whereIn('trips.spot_id', $spots)
                    ->has('catches')
                    ->reorder()
                    ->toBase()
                    ->selectRaw("trips.spot_id, trips.{$column} as value, COUNT(*) as trip_count, MAX(trips.went_at) as last_went_at")
                    ->groupBy('trips.spot_id', "trips.{$column}")
                    ->orderByDesc('trip_count')
                    ->orderByDesc('last_went_at')
                    ->get();
                foreach ($rows as $row) {
                    $mostCommon[$row->spot_id][$column] ??= (string) $row->value;
                }
            }
        }

        $results = [];
        foreach ($spotIds as $spotId) {
            $perLevel = collect($levels)->map(fn(array $level, string $name) => [
                'level' => $name,
                'label' => self::LABELS[$name],
                'condition' => $level['condition'],
                'test' => $level['test'],
                'visits' => (int) data_get($counts[$name]->get($spotId), 'visits', 0),
                'caught' => (int) data_get($counts[$name]->get($spotId), 'caught', 0),
                'samples' => (int) data_get($counts[$name]->get($spotId), 'samples', 0),
            ]);
            $results[$spotId] = $this->choose($perLevel, $date, $tide, $timeOfDay,
                fn(array $used, string $column) => $mostCommon[$spotId][$column] ?? '') + [
                    // 釣れたときにいちばん多かった時間帯（プランナーの「よく釣れた時間帯」）
                    'bestTimeOfDay' => ($mostCommon[$spotId]['time_of_day'] ?? '') ?: null,
                ];
        }

        return $results;
    }

    /**
     * 記録の内訳（#211）。数えた釣行が、時間帯・潮・年ごとに何回行って何回釣れたか
     * 提案のもとになった記録が、どの条件に偏っているかを見せるため。データベースで数える
     *
     * @param  Builder  $matched  数えた釣行のクエリ（matchQuery() の matched）
     * @return array<string, array<string, array{visits: int, caught: int}>>
     */
    public function breakdown(Builder $matched): array
    {
        $result = [];
        foreach (self::BREAKDOWN as $key => $expression) {
            $rows = $this->countBy($matched, $expression, false);
            $result[$key] = $this->ordered($key, $rows->mapWithKeys(fn($row) => [$this->valueLabel($row->value) => $this->counts($row)])->all());
        }

        return $result;
    }

    /**
     * 釣り場ごとの記録の内訳（プランナー用。#211）。釣り場ごとに使うレベルの釣行だけを数える
     *
     * @param  array<int, array>  $matches  matchBySpot() の結果
     * @return array<int, array<string, array<string, array{visits: int, caught: int}>>>
     */
    public function breakdownBySpot(Builder $trips, array $matches, Carbon $date, string $tide, ?string $timeOfDay): array
    {
        $result = [];
        foreach (array_keys(self::LABELS) as $level) {
            $spotIds = array_keys(array_filter($matches, fn(array $match) => $match['level'] === $level && $match['visits'] > 0));
            if ($spotIds === []) {
                continue;
            }
            $matched = $this->matchedQuery($trips, $level, $date, $tide, $timeOfDay)->whereIn('trips.spot_id', $spotIds);
            foreach (self::BREAKDOWN as $key => $expression) {
                foreach ($this->countBy($matched, $expression, true) as $row) {
                    $result[$row->spot_id][$key][$this->valueLabel($row->value)] = $this->counts($row);
                }
            }
        }
        foreach ($result as $spotId => $keys) {
            foreach ($keys as $key => $values) {
                $result[$spotId][$key] = $this->ordered($key, $values);
            }
        }

        return $result;
    }

    /** 内訳の1項目を数える問い合わせ（釣り場ごとにも分けられる） */
    private function countBy(Builder $matched, string $expression, bool $bySpot): Collection
    {
        $group = ($bySpot ? 'trips.spot_id, ' : '') . $expression;

        return (clone $matched)
            ->reorder()
            ->toBase()
            ->selectRaw(($bySpot ? 'trips.spot_id, ' : '') . "{$expression} as value, COUNT(*) as visits, COUNT(CASE WHEN " . self::CAUGHT_SQL . ' THEN 1 END) as caught')
            ->groupByRaw($group)
            ->get();
    }

    /** @return array{visits: int, caught: int} */
    private function counts(object $row): array
    {
        return ['visits' => (int) $row->visits, 'caught' => (int) $row->caught];
    }

    private function valueLabel(mixed $value): string
    {
        return $value === null || $value === '' ? '不明' : (string) $value;
    }

    /**
     * 決まった言葉の順（config/fishing.php）に並べる。年は古い順。一覧にない値（不明など）は後ろ
     *
     * @param  array<string, array{visits: int, caught: int}>  $values
     */
    private function ordered(string $key, array $values): array
    {
        $order = match ($key) {
            'time_of_day' => config('fishing.times_of_day'),
            'tide' => config('fishing.tides'),
            default => [],
        };
        uksort($values, function (string $a, string $b) use ($order) {
            $ia = array_search($a, $order, true);
            $ib = array_search($b, $order, true);

            return [$ia === false ? PHP_INT_MAX : $ia, $a] <=> [$ib === false ? PHP_INT_MAX : $ib, $b];
        });

        return $values;
    }

    /**
     * そのレベルに当てはまる釣行のクエリ（プランナーで、使うレベルの釣行だけを集計するため。#168）
     */
    public function matchedQuery(Builder $trips, string $level, Carbon $date, string $tide, ?string $timeOfDay): Builder
    {
        return $this->levels($date, $tide, $timeOfDay)[$level]['scope'](clone $trips);
    }

    /**
     * 釣れた記録がある、いちばん厳しいレベルを選ぶ。どこにもなければ、ぴったりのまま（坊主だけ、または記録なし）
     * $mostCommon は、選んだレベルの釣れた釣行で、いちばん多い値を返す（ハンコの青の値）
     */
    private function choose(Collection $results, Carbon $date, string $tide, ?string $timeOfDay, \Closure $mostCommon): array
    {
        $exact = $results['exact'];
        $chosen = $results->first(fn(array $result) => $result['caught'] > 0);
        $used = $chosen ?? $exact;

        return $used + [
            // ゆるめたときも、ぴったりの条件の結果（坊主の記録）を隠さずに出すため
            'exact' => [
                'condition' => $exact['condition'],
                'names' => $timeOfDay ? '時期・時間帯・潮' : '時期・潮',
                'visits' => $exact['visits'],
                'caught' => $exact['caught'],
            ],
            // 並べ替えに使う：ぴったり 0・時期と時間帯 1・時期だけ 2・釣れた記録なし 3
            'rank' => $chosen ? array_search($chosen['level'], array_keys(self::LABELS), true) : self::NO_CATCH_RANK,
            'stamps' => $this->stamps($used, self::seasonLabel($date), $timeOfDay, $tide, $mostCommon),
        ];
    }

    /**
     * 画面のハンコ（#158）。時期・時間帯・潮の順。時間帯が「指定なし」なら時間帯のハンコは出さない
     * 行く日と同じ条件（same）はその値、違う条件は、数えた記録のうち釣れたときにいちばん多かった値を出す
     *
     * @return list<array{name: string, value: string, same: bool}>
     */
    private function stamps(array $used, string $season, ?string $timeOfDay, string $tide, \Closure $mostCommon): array
    {
        $mostCommon = fn(string $column) => $mostCommon($used, $column);

        $stamps = [['name' => '時期', 'value' => $season, 'same' => true]];
        if ($timeOfDay !== null) {
            $same = $used['level'] !== 'season';
            $stamps[] = ['name' => '時間帯', 'value' => $same ? $timeOfDay : $mostCommon('time_of_day'), 'same' => $same];
        }
        $same = $used['level'] === 'exact';
        $stamps[] = ['name' => '潮', 'value' => $same ? $tide : $mostCommon('tide'), 'same' => $same];

        return $stamps;
    }
}
