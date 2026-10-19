<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 過去の釣行から、選んだ日の条件に合うものを探す（FN-16・FN-11）
 * 時期（選んだ日の月と前後1か月）はいつも条件に入れる。魚種ごとに釣れる季節があるため（#158）
 * ぴったり一致（時期＋時間帯＋潮）に釣れた記録がなければ、時期と時間帯 → 時期だけ と少しずつ条件をゆるめる
 * プランナーとカルテの判断ビューで同じ決まりにするため、ここ1か所で決める
 * 数え方は2つ：読み込み済みの釣行で数える match() と、データベースで数える matchQuery()（#166）
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
     * 読み込み済みの釣行（catches 付き）で数える。プランナーで使う（#166 のあと、プランナーもデータベースで数える形に変える予定）
     *
     * @param  Collection<int, Trip>  $trips  見てよい釣行（catches 付き）。どれを入れてよいかは呼ぶ側で絞っておく
     * @return array{level: string, label: string, condition: string, matched: Collection, visits: int, caught: int,
     *               exact: array{condition: string, names: string, visits: int, caught: int}, rank: int,
     *               stamps: list<array{name: string, value: string, same: bool}>}
     */
    public function match(Collection $trips, Carbon $date, string $tide, ?string $timeOfDay): array
    {
        $results = collect($this->levels($date, $tide, $timeOfDay))->map(function (array $level, string $name) use ($trips) {
            $matched = $trips->filter($level['test'])->values();

            return [
                'level' => $name,
                'label' => self::LABELS[$name],
                'condition' => $level['condition'],
                'matched' => $matched,
                'visits' => $matched->count(),
                'caught' => $matched->filter(fn(Trip $trip) => $trip->catches->isNotEmpty())->count(),
            ];
        });

        // いちばん多い値。同じ数なら先に見つかったもの。釣れた記録がなければ空
        return $this->choose($results, $date, $tide, $timeOfDay, function (array $used, string $column) {
            return (string) $used['matched']->filter(fn(Trip $trip) => $trip->catches->isNotEmpty())
                ->countBy($column)->sortDesc()->keys()->first();
        });
    }

    /**
     * データベースで数える（#166）。釣行を読み込まないので、記録が増えても重くならない
     * 返す中身は match() と同じ形。ただし matched は、選んだレベルの釣行を取り出すクエリ、test は釣行1件がそのレベルに当てはまるか
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
            ];
        });

        // いちばん多い値。同じ数なら新しい釣行に出てきたもの（カルテは新しい順に並ぶので、match() の「先に見つかった」と同じ）
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
