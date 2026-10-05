<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * 記録を続けるきっかけになる数字（FN-10）
 * 継続カウンタ（今月の回数・連続月数・去年の同じ月）と、気づきカード（潮・時間帯・天候の傾向）
 * 数えるのは自分の釣行だけ（公開範囲は問わない）。坊主も1回に数える
 */
class KeepRecording
{
    /** 気づきカードを出し始める釣行の件数（FN-10：釣行3件目から） */
    public const INSIGHT_MIN_TRIPS = 3;

    /** 気づきカードにする条件で、最低限行っている回数（1回だけの当たりを「よく釣れる」と言わないため） */
    public const INSIGHT_MIN_VISITS = 2;

    /** 気づきカードで見る条件（列の名前 => 画面に出す名前） */
    public const INSIGHT_FIELDS = [
        'tide' => '潮',
        'time_of_day' => '時間帯',
        'weather' => '天候',
    ];

    /** 列の名前 => config/fishing.php の一覧（割合と回数が同じときの並び順に使う） */
    private const FIELD_OPTIONS = [
        'tide' => 'fishing.tides',
        'time_of_day' => 'fishing.times_of_day',
        'weather' => 'fishing.weathers',
    ];

    /**
     * @return array{counter: array, insights: array}
     */
    public function build(User $user, Carbon $today): array
    {
        // 自分の釣行を、数えるのに要る列だけ取り出す。caught は「1匹以上釣れたか」
        $trips = $user->trips()
            ->withExists('catches as caught')
            ->get(['id', 'went_at', 'tide', 'time_of_day', 'weather']);

        return [
            'counter' => $this->counter($trips, $today),
            'insights' => $this->insights($trips),
        ];
    }

    /**
     * 継続カウンタ
     *
     * @return array{month: int, thisMonth: int, lastYear: int, streak: int, recordedThisMonth: bool}
     *         streak は連続で記録している月数。今月まだ記録がなければ、先月までの続きの数
     */
    public function counter(Collection $trips, Carbon $today): array
    {
        // 記録のある月の一覧（例：'2026-10'）
        $months = $trips->map(fn($trip) => $trip->went_at->format('Y-m'))->countBy();

        $thisMonth = $months->get($today->format('Y-m'), 0);

        // 今月まだ記録がなくても、今月が終わるまでは途切れにしない。先月からさかのぼって数える
        $month = $today->copy()->startOfMonth();
        if ($thisMonth === 0) {
            $month->subMonthNoOverflow();
        }
        $streak = 0;
        while ($months->has($month->format('Y-m'))) {
            $streak++;
            $month->subMonthNoOverflow();
        }

        return [
            'month' => $today->month,
            'thisMonth' => $thisMonth,
            'lastYear' => $months->get($today->copy()->subYearNoOverflow()->format('Y-m'), 0),
            'streak' => $streak,
            'recordedThisMonth' => $thisMonth > 0,
        ];
    }

    /**
     * 気づきカード
     * 潮・時間帯・天候のそれぞれで「釣れた割合」がいちばん高いものを1枚ずつ（最大3枚）
     *
     * @return array{tripCount: int, remaining: int, cards: array<int, array{field: string, label: string, value: string, visits: int, caught: int}>}
     *         remaining は、カードが出るまであと何件か（出せるときは 0）
     */
    public function insights(Collection $trips): array
    {
        $tripCount = $trips->count();
        $remaining = max(0, self::INSIGHT_MIN_TRIPS - $tripCount);
        if ($remaining > 0) {
            return ['tripCount' => $tripCount, 'remaining' => $remaining, 'cards' => []];
        }

        $cards = [];
        foreach (self::INSIGHT_FIELDS as $field => $label) {
            $order = array_flip(config(self::FIELD_OPTIONS[$field]));

            $best = $trips
                // 空欄の釣行は、この条件の計算に入れない
                ->filter(fn($trip) => $trip->{$field} !== null)
                ->groupBy($field)
                ->map(fn(Collection $group, string $value) => [
                    'field' => $field,
                    'label' => $label,
                    'value' => $value,
                    'visits' => $group->count(),
                    'caught' => $group->where('caught', true)->count(),
                ])
                // 2回以上行って、1回でも釣れた条件だけ
                ->filter(fn(array $row) => $row['visits'] >= self::INSIGHT_MIN_VISITS && $row['caught'] > 0)
                // 割合が高い順 → 同じなら行った回数が多い順 → 同じなら config の順
                ->sort(fn(array $a, array $b) => [$b['caught'] / $b['visits'], $b['visits'], $order[$a['value']] ?? PHP_INT_MAX]
                    <=> [$a['caught'] / $a['visits'], $a['visits'], $order[$b['value']] ?? PHP_INT_MAX])
                ->first();

            if ($best) {
                $cards[] = $best;
            }
        }

        return ['tripCount' => $tripCount, 'remaining' => 0, 'cards' => $cards];
    }
}
