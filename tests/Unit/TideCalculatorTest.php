<?php

namespace Tests\Unit;

use App\Services\TideCalculator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class TideCalculatorTest extends TestCase
{
    public function test_tide_names_for_known_dates(): void
    {
        $calculator = new TideCalculator();

        // 2024年1月11日は新月（旧暦12月1日）。そこからの日数で潮を確かめる
        $expected = [
            '2024-01-11' => '大潮',  // 旧暦1日（新月）
            '2024-01-14' => '中潮',  // 旧暦4日
            '2024-01-17' => '小潮',  // 旧暦7日
            '2024-01-20' => '長潮',  // 旧暦10日
            '2024-01-21' => '若潮',  // 旧暦11日
            '2024-01-25' => '大潮',  // 旧暦15日（満月のころ）
            '2024-02-03' => '長潮',  // 旧暦24日
            '2024-02-04' => '若潮',  // 旧暦25日
        ];

        foreach ($expected as $date => $tide) {
            $this->assertSame(
                $tide,
                $calculator->tideFor(Carbon::parse($date, 'Asia/Tokyo')),
                "{$date} の潮が違います"
            );
        }
    }

    public function test_lunar_day_on_new_moon_is_1(): void
    {
        $calculator = new TideCalculator();

        $this->assertSame(1, $calculator->lunarDay(Carbon::parse('2024-01-11', 'Asia/Tokyo')));
        $this->assertSame(1, $calculator->lunarDay(Carbon::parse('2024-09-03', 'Asia/Tokyo')));
    }

    public function test_next_date_with_tide(): void
    {
        $calculator = new TideCalculator();

        // 2024年1月17日（小潮）から探すと、次の大潮は1月24日（旧暦14日）
        $this->assertSame('2024-01-24', $calculator->nextDateWithTide(Carbon::parse('2024-01-17', 'Asia/Tokyo'), '大潮')->format('Y-m-d'));

        // 探し始めの日がすでに大潮なら、その日を返す
        $this->assertSame('2024-01-11', $calculator->nextDateWithTide(Carbon::parse('2024-01-11', 'Asia/Tokyo'), '大潮')->format('Y-m-d'));

        // 大潮以外でも探せる
        $this->assertSame('2024-01-21', $calculator->nextDateWithTide(Carbon::parse('2024-01-17', 'Asia/Tokyo'), '若潮')->format('Y-m-d'));
    }
}
