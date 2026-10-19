<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Services\ConditionMatcher;
use App\Services\TideCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * 条件を少しずつゆるめて探す（FN-16・FN-11）
 * 時期（選んだ日の月と前後1か月）はいつも条件に入れる（#158）。2024-01-19 の時期は 12〜2月
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
        // データベースで数える（#166・#168）
        return app(ConditionMatcher::class)->matchQuery(Trip::where('trips.spot_id', $this->spot->id), Carbon::parse('2024-01-19'), '小潮', $timeOfDay);
    }

    public function test_season_is_the_month_and_one_month_before_and_after(): void
    {
        $this->assertSame([12, 1, 2], ConditionMatcher::seasonMonths(Carbon::parse('2024-01-19')));
        $this->assertSame([7, 8, 9], ConditionMatcher::seasonMonths(Carbon::parse('2024-08-05')));
        $this->assertSame('11〜1月', ConditionMatcher::seasonLabel(Carbon::parse('2024-12-05')));
    }

    public function test_exact_match_is_used_when_it_has_a_catch(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', true);
        $this->trip('2024-01-18 20:00', '夜', true);

        $result = $this->match('朝マズメ');

        $this->assertSame('exact', $result['level']);
        $this->assertSame('ぴったり一致', $result['label']);
        $this->assertSame('12〜2月・朝マズメ・小潮', $result['condition']);
        $this->assertSame([1, 1, 0], [$result['visits'], $result['caught'], $result['rank']]);
        // ハンコは3つとも行く日と同じ（#158）
        $this->assertSame([
            ['name' => '時期', 'value' => '12〜2月', 'same' => true],
            ['name' => '時間帯', 'value' => '朝マズメ', 'same' => true],
            ['name' => '潮', 'value' => '小潮', 'same' => true],
        ], $result['stamps']);
    }

    public function test_relaxes_to_season_and_time_when_exact_has_only_bozu(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', false); // ぴったりは坊主だけ
        $this->trip('2024-01-11 06:00', '朝マズメ', true);  // 大潮の朝マズメは釣れた
        $this->trip('2024-01-18 20:00', '夜', true);       // 時間帯が違うので入らない

        $result = $this->match('朝マズメ');

        $this->assertSame('time', $result['level']);
        $this->assertSame('時期と時間帯が一致', $result['label']);
        $this->assertSame('12〜2月・朝マズメ（潮は問わない）', $result['condition']);
        $this->assertSame([2, 1, 1], [$result['visits'], $result['caught'], $result['rank']]);
        // ぴったりの条件の結果（坊主の記録）も残っている
        $this->assertSame(['condition' => '12〜2月・朝マズメ・小潮', 'names' => '時期・時間帯・潮', 'visits' => 1, 'caught' => 0], $result['exact']);
        // 潮のハンコは青で、釣れたときの潮（大潮）
        $this->assertSame(['name' => '潮', 'value' => '大潮', 'same' => false], $result['stamps'][2]);
    }

    public function test_blue_stamp_shows_the_most_common_value_among_caught_trips(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', false); // ぴったりは坊主だけ
        $this->trip('2024-01-11 06:00', '朝マズメ', true);  // 大潮で釣れた
        $this->trip('2024-01-12 06:00', '朝マズメ', true);  // 大潮で釣れた
        $this->trip('2024-01-28 06:00', '朝マズメ', true);  // 中潮で1回

        $result = $this->match('朝マズメ');

        // 釣れたときにいちばん多かった潮（坊主の小潮は数えない）
        $this->assertSame('大潮', $result['stamps'][2]['value']);
    }

    public function test_relaxes_to_season_and_any_year_matches(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', false); // 朝マズメは坊主だけ
        $this->trip('2021-02-10 20:00', '夜', true);       // 別の年の2月（時期に入る）は釣れた
        $this->trip('2024-03-10 20:00', '夜', true);       // 3月は時期の外なので入らない

        $result = $this->match('朝マズメ');

        $this->assertSame('season', $result['level']);
        $this->assertSame('時期だけ一致', $result['label']);
        $this->assertSame('12〜2月（時間帯・潮は問わない）', $result['condition']);
        $this->assertSame([2, 1, 2], [$result['visits'], $result['caught'], $result['rank']]);
        // 時間帯と潮のハンコは青で、釣れたときの値（2021-02-10 の夜）
        $this->assertSame([true, false, false], array_column($result['stamps'], 'same'));
        $this->assertSame('夜', $result['stamps'][1]['value']);
    }

    public function test_same_tide_in_another_season_is_not_counted(): void
    {
        // 夏の小潮・朝マズメに釣れていても、1月の計画には数えない（季節が違うと釣れる魚が違うため）
        $summer = Carbon::parse('2023-07-01 06:00');
        while (app(TideCalculator::class)->tideFor($summer) !== '小潮') {
            $summer->addDay();
        }
        $this->trip($summer->format('Y-m-d H:i'), '朝マズメ', true);

        $result = $this->match('朝マズメ');

        $this->assertSame([0, 0, ConditionMatcher::NO_CATCH_RANK], [$result['visits'], $result['caught'], $result['rank']]);
    }

    public function test_without_time_of_day_time_level_is_skipped(): void
    {
        $this->trip('2024-01-17 06:00', '朝マズメ', false);
        $this->trip('2024-01-11 06:00', '朝マズメ', true); // 大潮・1月

        $result = $this->match(null);

        // 時間帯なしでは、時期と時間帯＝時期だけなので、そのまま時期だけへ
        $this->assertSame('season', $result['level']);
        $this->assertSame('12〜2月・小潮', $result['exact']['condition']);
        // 時間帯のハンコは出さない。潮は青で大潮
        $this->assertSame(['時期', '潮'], array_column($result['stamps'], 'name'));
        $this->assertSame(['name' => '潮', 'value' => '大潮', 'same' => false], $result['stamps'][1]);
    }

    public function test_match_by_spot_chooses_the_level_for_each_spot(): void
    {
        // 釣り場ごとにデータベースでまとめて数える（プランナー用。#168）
        $this->trip('2024-01-17 06:00', '朝マズメ', true);              // この釣り場：ぴったり一致
        $other = Spot::factory()->create(['visibility' => 'public']);
        $otherTrip = Trip::factory()->create(['spot_id' => $other->id, 'went_at' => '2024-01-11 20:00', 'time_of_day' => '夜']);
        FishCatch::factory()->create(['trip_id' => $otherTrip->id]);    // ほかの釣り場：時期だけ一致（大潮の夜）
        $empty = Spot::factory()->create(['visibility' => 'public']);   // 記録なし

        $results = app(ConditionMatcher::class)->matchBySpot(
            Trip::query(),
            [$this->spot->id, $other->id, $empty->id],
            Carbon::parse('2024-01-19'),
            '小潮',
            '朝マズメ',
        );

        $this->assertSame(['exact', 0, '朝マズメ'], [$results[$this->spot->id]['level'], $results[$this->spot->id]['rank'], $results[$this->spot->id]['bestTimeOfDay']]);
        $this->assertSame(['season', 2], [$results[$other->id]['level'], $results[$other->id]['rank']]);
        // 時間帯と潮のハンコは青で、釣れたときの値（夜・大潮）
        $this->assertSame(['夜', '大潮'], [$results[$other->id]['stamps'][1]['value'], $results[$other->id]['stamps'][2]['value']]);
        $this->assertSame([0, ConditionMatcher::NO_CATCH_RANK], [$results[$empty->id]['visits'], $results[$empty->id]['rank']]);
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
