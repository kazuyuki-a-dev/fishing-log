<?php

namespace App\Services;

use App\Models\Trip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 過去の釣行から、選んだ日の条件に合うものを探す（FN-16・FN-11）
 * ぴったり一致に釣れた記録がなければ、潮だけ一致 → 月だけ一致 と少しずつ条件をゆるめる
 * プランナーとカルテの判断ビューで同じ決まりにするため、ここ1か所で決める
 */
class ConditionMatcher
{
    /** 一致のレベルと、画面に出す名前（上ほど条件が厳しい） */
    public const LABELS = [
        'exact' => 'ぴったり一致',
        'tide' => '潮だけ一致',
        'month' => '月だけ一致',
    ];

    /** どのレベルにも釣れた記録がないときの並び順（いちばん下） */
    public const NO_CATCH_RANK = 3;

    /**
     * @param  Collection<int, Trip>  $trips  見てよい釣行（catches 付き）。どれを入れてよいかは呼ぶ側で絞っておく
     * @return array{level: string, label: string, condition: string, matched: Collection, visits: int, caught: int,
     *               exact: array{condition: string, visits: int, caught: int}, rank: int}
     */
    public function match(Collection $trips, Carbon $date, string $tide, ?string $timeOfDay): array
    {
        $levels = [
            'exact' => [
                'condition' => $tide . ($timeOfDay ? '・' . $timeOfDay : ''),
                'test' => fn(Trip $trip) => $trip->matchesCondition($tide, $timeOfDay),
            ],
        ];
        // 時間帯が「指定なし」なら、ぴったりと潮だけは同じ意味なので飛ばす
        if ($timeOfDay !== null) {
            $levels['tide'] = [
                'condition' => "{$tide}（時間帯は問わない）",
                'test' => fn(Trip $trip) => $trip->matchesCondition($tide, null),
            ];
        }
        $levels['month'] = [
            'condition' => "{$date->month}月（潮は問わない）",
            'test' => fn(Trip $trip) => $trip->went_at->month === $date->month,
        ];

        // それぞれのレベルで数える
        $results = collect($levels)->map(function (array $level, string $name) use ($trips) {
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

        $exact = $results['exact'];

        // 釣れた記録がある、いちばん厳しいレベルを使う。どこにもなければ、ぴったりのまま（坊主だけ、または記録なし）
        $chosen = $results->first(fn(array $result) => $result['caught'] > 0);

        return ($chosen ?? $exact) + [
            // ゆるめたときも、ぴったりの条件の結果（坊主の記録）を隠さずに出すため
            'exact' => ['condition' => $exact['condition'], 'visits' => $exact['visits'], 'caught' => $exact['caught']],
            // 並べ替えに使う：ぴったり 0・潮だけ 1・月だけ 2・釣れた記録なし 3
            'rank' => $chosen ? array_search($chosen['level'], array_keys(self::LABELS), true) : self::NO_CATCH_RANK,
        ];
    }
}
