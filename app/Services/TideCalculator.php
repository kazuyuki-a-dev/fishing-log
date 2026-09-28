<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * 日付から潮の名前を計算する（FN-08）
 *
 * 月齢から旧暦の日にちを近似で出して、潮の名前に当てはめる。
 * 本物の旧暦と1日ずれることがある（docs/decisions.md 参照）。
 */
class TideCalculator
{
    // 基準にする新月の日時（2000年1月6日 18:14 世界時）
    private const NEW_MOON_EPOCH = '2000-01-06 18:14:00';

    // 月の満ち欠けの周期（日）
    private const SYNODIC_MONTH = 29.530588853;

    // 旧暦の日にち（1〜30）ごとの潮の名前
    private const TIDE_BY_LUNAR_DAY = [
        1 => '大潮',
        2 => '大潮',
        3 => '大潮',
        4 => '中潮',
        5 => '中潮',
        6 => '中潮',
        7 => '小潮',
        8 => '小潮',
        9 => '小潮',
        10 => '長潮',
        11 => '若潮',
        12 => '中潮',
        13 => '中潮',
        14 => '大潮',
        15 => '大潮',
        16 => '大潮',
        17 => '大潮',
        18 => '中潮',
        19 => '中潮',
        20 => '中潮',
        21 => '小潮',
        22 => '小潮',
        23 => '小潮',
        24 => '長潮',
        25 => '若潮',
        26 => '中潮',
        27 => '中潮',
        28 => '中潮',
        29 => '中潮',
        30 => '大潮',
    ];

    /**
     * 旧暦の日にち（1〜30）を返す
     */
    public function lunarDay(CarbonInterface $date): int
    {
        // その日の終わり（日本時間の翌日0時）の時点で、最後の新月から何日たったか
        $endOfDay = Carbon::parse($date->format('Y-m-d'), 'Asia/Tokyo')->addDay();
        $epoch = Carbon::parse(self::NEW_MOON_EPOCH, 'UTC');

        $days = $epoch->diffInSeconds($endOfDay) / 86400;
        $age = fmod($days, self::SYNODIC_MONTH);
        if ($age < 0) {
            $age += self::SYNODIC_MONTH;
        }

        return (int) floor($age) + 1;
    }

    /**
     * 潮の名前（大潮・中潮・小潮・長潮・若潮）を返す
     */
    public function tideFor(CarbonInterface $date): string
    {
        return self::TIDE_BY_LUNAR_DAY[$this->lunarDay($date)];
    }

    /**
     * 指定した日から数えて、最初にその潮になる日を返す（その日も含む）
     */
    public function nextDateWithTide(CarbonInterface $from, string $tide): CarbonInterface
    {
        $date = $from->copy()->startOfDay();

        // 潮は約30日でひと回りするので、31日以内に必ず見つかる
        for ($i = 0; $i <= 31; $i++) {
            if ($this->tideFor($date) === $tide) {
                return $date;
            }
            $date = $date->addDay();
        }

        throw new \InvalidArgumentException("潮「{$tide}」が見つかりません");
    }
}
