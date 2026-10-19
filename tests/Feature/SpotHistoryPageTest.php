<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * カルテの釣行の履歴は10件ずつ（#166。全部は読み込まない）
 */
class SpotHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_is_shown_ten_trips_per_page(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $me->id, 'visibility' => 'public', 'latitude' => null, 'longitude' => null]);
        // 12件。日付の新しい順に並ぶ（2024-01-12 がいちばん新しい）
        foreach (range(1, 12) as $day) {
            Trip::factory()->create([
                'user_id' => $me->id,
                'spot_id' => $spot->id,
                'went_at' => sprintf('2024-01-%02d 06:00', $day),
            ]);
        }

        // 1ページ目：新しい10件。実績は12件全部から数える
        $this->actingAs($me)->get("/spots/{$spot->id}")
            ->assertSee('2024/01/12')
            ->assertSee('2024/01/03')
            ->assertDontSee('2024/01/02')
            ->assertSee('12回行って');

        // 2ページ目：残りの2件。日付などの条件は URL に残る
        $this->actingAs($me)->get("/spots/{$spot->id}?date=2024-01-19&page=2")
            ->assertSee('2024/01/02')
            ->assertSee('2024/01/01')
            ->assertDontSee('2024/01/03');
    }

    public function test_page_links_keep_the_chosen_date(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $me->id, 'visibility' => 'public', 'latitude' => null, 'longitude' => null]);
        Trip::factory()->count(11)->create(['user_id' => $me->id, 'spot_id' => $spot->id]);

        $this->actingAs($me)->get("/spots/{$spot->id}?date=2024-01-19")
            ->assertSee('date=2024-01-19&amp;page=2', false);
    }
}
