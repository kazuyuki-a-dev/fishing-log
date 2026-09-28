<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotJudgeTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private Spot $spot;

    // どのテストでも使う「自分」と「釣り場」と「3回の釣行」を用意する
    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create();
        $this->spot = Spot::factory()->create(['visibility' => 'public']);

        // 大潮の日に1回（釣れた）
        $bigTide = Trip::factory()->create([
            'user_id' => $this->me->id,
            'spot_id' => $this->spot->id,
            'went_at' => '2024-01-11 06:00',
            'time_of_day' => '朝マズメ',
        ]);
        FishCatch::factory()->create(['trip_id' => $bigTide->id, 'fish_species' => 'サバ', 'method' => 'ルアー']);

        // 小潮の日に2回（1回は釣れた、1回は坊主）
        $smallTide = Trip::factory()->create([
            'user_id' => $this->me->id,
            'spot_id' => $this->spot->id,
            'went_at' => '2024-01-17 06:00',
            'time_of_day' => '朝マズメ',
        ]);
        FishCatch::factory()->create(['trip_id' => $smallTide->id, 'fish_species' => 'アジ', 'method' => 'エサ']);

        Trip::factory()->create([
            'user_id' => $this->me->id,
            'spot_id' => $this->spot->id,
            'went_at' => '2024-01-18 20:00',
            'time_of_day' => '夜',
        ]);
    }

    public function test_shows_tide_lunar_day_and_next_big_tide_for_the_chosen_date(): void
    {
        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=2024-01-19")
            ->assertSee('小潮')
            ->assertSee('旧暦9日')
            ->assertSee('次の大潮：1月24日');
    }

    public function test_counts_only_trips_with_the_same_tide(): void
    {
        // 小潮の日を選ぶと、小潮だった2回だけが数えられる（大潮の1回は入らない）
        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=2024-01-19")
            ->assertSee('2回行って 1回釣れた')
            ->assertSee('エサ 1匹')
            ->assertSee('アジ 1匹')
            ->assertDontSee('サバ 1匹');
    }

    public function test_time_of_day_narrows_the_match(): void
    {
        // 小潮・朝マズメにすると、朝マズメの1回だけになる
        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=2024-01-19&time_of_day=朝マズメ")
            ->assertSee('1回行って 1回釣れた');
    }

    public function test_invalid_date_falls_back_to_today(): void
    {
        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=abc")
            ->assertOk()
            ->assertSee(now('Asia/Tokyo')->format('Y年n月j日'));
    }
}
