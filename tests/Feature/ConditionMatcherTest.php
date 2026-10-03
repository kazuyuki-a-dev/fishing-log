<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Services\ConditionMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 条件を少しずつゆるめて探す（FN-16・FN-11）
 * 2024-01-17・18・19 は小潮、2024-01-11 は大潮
 */
class ConditionMatcherTest extends TestCase
{
    use RefreshDatabase;

    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spot = Spot::factory()->create(['visibility' => 'public']);
    }

    // 釣行を作る。$caught が true なら1匹釣れたことにする
    private function trip(string $wentAt, string $timeOfDay, bool $caught): void
    {
        $trip = Trip::factory()->create([
            'spot_id' => $this->spot->id,
            'went_at' => $wentAt,
            'time_of_day' => $timeOfDay,
        ]);
        if ($caught) {
            FishCatch::factory()->create(['trip_id' => $trip->id]);
        }
    }

    // 小潮の 2024-01-19 で照合する
    private function match(?string $timeOfDay): array
    {
        $trips = $this->spot->trips()->with('catches')->get();

        return app(ConditionMatcher::class)->match($trips, Carbon::parse('2024-01-19'), '小潮', $timeOfDay);
    }

    public function test_exact_match_is_used_when_it_has_a_catch(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', true);
        $this->trip('2024-01-18 20:00', '夜', true);

        $result = $this->match('朝マズメ');

        $this->assertSame('exact', $result['level']);
        $this->assertSame('ぴったり一致', $result['label']);
        $this->assertSame('小潮・朝マズメ', $result['condition']);
        $this->assertSame([1, 1, 0], [$result['visits'], $result['caught'], $result['rank']]);
    }

    public function test_relaxes_to_tide_when_exact_has_only_bozu(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', false); // ぴったりは坊主だけ
        $this->trip('2024-01-18 20:00', '夜', true);       // 小潮の夜は釣れた

        $result = $this->match('朝マズメ');

        $this->assertSame('tide', $result['level']);
        $this->assertSame('小潮（時間帯は問わない）', $result['condition']);
        $this->assertSame([2, 1, 1], [$result['visits'], $result['caught'], $result['rank']]);
        // ぴったりの条件の結果（坊主の記録）も残っている
        $this->assertSame(['condition' => '小潮・朝マズメ', 'visits' => 1, 'caught' => 0], $result['exact']);
    }

    public function test_relaxes_to_month_and_any_year_matches(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', false); // 小潮は坊主だけ
        $this->trip('2021-01-13 06:00', '夜', true);       // 別の年の1月（大潮）は釣れた
        $this->trip('2024-02-10 06:00', '夜', true);       // 2月（大潮）は入らない

        $result = $this->match('朝マズメ');

        $this->assertSame('month', $result['level']);
        $this->assertSame('1月（潮は問わない）', $result['condition']);
        $this->assertSame([2, 1, 2], [$result['visits'], $result['caught'], $result['rank']]);
    }

    public function test_without_time_of_day_tide_level_is_skipped(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', false);
        $this->trip('2024-01-11 06:00', '朝マズメ', true); // 大潮・1月

        $result = $this->match(null);

        // 時間帯なしでは、ぴったり＝潮だけなので、そのまま月だけへ
        $this->assertSame('month', $result['level']);
        $this->assertSame('小潮', $result['exact']['condition']);
    }

    public function test_no_catch_at_any_level_stays_exact_with_lowest_rank(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', false);

        $result = $this->match('朝マズメ');

        $this->assertSame('exact', $result['level']);
        $this->assertSame([1, 0], [$result['visits'], $result['caught']]);
        $this->assertSame(ConditionMatcher::NO_CATCH_RANK, $result['rank']);
    }

    public function test_no_trips_at_all(): void
    {
        $result = $this->match(null);

        $this->assertSame(['exact', 0, 0, ConditionMatcher::NO_CATCH_RANK], [$result['level'], $result['visits'], $result['caught'], $result['rank']]);
    }
}
