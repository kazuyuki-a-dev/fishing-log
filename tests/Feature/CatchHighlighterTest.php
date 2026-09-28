<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Services\CatchHighlighter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatchHighlighterTest extends TestCase
{
    use RefreshDatabase;

    /** 釣行を1件作り、釣果（[魚種, サイズ] の組）を付ける */
    private function tripWith(User $user, Spot $spot, array $catches = []): Trip
    {
        $trip = Trip::factory()->create(['user_id' => $user->id, 'spot_id' => $spot->id]);
        foreach ($catches as [$species, $length]) {
            FishCatch::factory()->create(['trip_id' => $trip->id, 'fish_species' => $species, 'length_cm' => $length]);
        }

        return $trip->fresh();
    }

    private function highlightsFor(Trip $trip): string
    {
        return implode("\n", app(CatchHighlighter::class)->for($trip));
    }

    public function test_first_catch_gets_all_three_highlights(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create();

        $result = $this->highlightsFor($this->tripWith($me, $spot, [['マゴチ', 45.5]]));

        $this->assertStringContainsString('自分の最大サイズを更新！ マゴチ 45.5 cm', $result);
        $this->assertStringContainsString('初めて釣った魚：マゴチ', $result);
        $this->assertStringContainsString('この釣り場で初めて釣れました！', $result);
    }

    public function test_only_my_records_are_compared(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create();

        // ほかの人のもっと大きいマゴチは、比べる相手にならない
        $this->tripWith(User::factory()->create(), $spot, [['マゴチ', 80.0]]);
        $this->tripWith($me, $spot, [['マゴチ', 30.0]]);

        $result = $this->highlightsFor($this->tripWith($me, $spot, [['マゴチ', 45.5]]));

        $this->assertStringContainsString('自分の最大サイズを更新！ マゴチ 45.5 cm', $result);
        $this->assertStringNotContainsString('初めて釣った魚', $result);
        $this->assertStringNotContainsString('この釣り場で初めて', $result);
    }

    public function test_ordinary_catch_gets_the_spot_record(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create();

        // これまで：アジ 30cm の釣行と、坊主の釣行
        $this->tripWith($me, $spot, [['アジ', 30.0]]);
        $this->tripWith($me, $spot);

        // 今回：アジ 20cm（大きくない・知っている魚・ここでも釣ったことがある）
        $result = $this->highlightsFor($this->tripWith($me, $spot, [['アジ', 20.0]]));

        $this->assertSame('この釣り場で 2 回目の釣果です（3 回行って 2 回釣れた）。', $result);
    }

    public function test_bozu_gets_encouragement_with_visit_count(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create();
        $this->tripWith($me, $spot, [['アジ', 30.0]]);

        $result = $this->highlightsFor($this->tripWith($me, $spot));

        $this->assertSame('記録しました。坊主も大事な判断材料です（この釣り場 2 回目）。', $result);
    }

    public function test_highlight_is_shown_right_after_recording(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['visibility' => 'public']);

        $this->actingAs($me)
            ->followingRedirects()
            ->post('/trips', [
                'spot_id' => $spot->id,
                'went_at' => '2026-09-20T06:00',
                'time_of_day' => '朝マズメ',
                'visibility' => 'private',
                'catches' => [
                    ['fish_species' => 'アジ', 'method' => 'エサ'],
                ],
            ])
            ->assertOk()
            ->assertSee('初めて釣った魚：アジ');
    }
}
