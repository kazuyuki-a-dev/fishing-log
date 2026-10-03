<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Services\SeasonHeatmap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 釣り場カルテの月別×魚種の小さな表（PG07・FN-09）
 */
class SpotSeasonTableTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private User $other;
    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create();
        $this->other = User::factory()->create();
        // 位置なし（天気予報は取りに行かない）
        $this->spot = Spot::factory()->create(['visibility' => 'public', 'latitude' => null, 'longitude' => null]);
    }

    /**
     * この釣り場での釣行を1件作り、魚を $fish 匹釣ったことにする
     */
    private function trip(User $user, string $visibility, string $species, int $fish = 1, string $wentAt = '2026-05-03 06:00'): Trip
    {
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'spot_id' => $this->spot->id,
            'went_at' => $wentAt,
            'visibility' => $visibility,
        ]);
        FishCatch::factory()->count($fish)->create(['trip_id' => $trip->id, 'fish_species' => $species]);

        return $trip;
    }

    // カルテに渡された表
    private function season(?User $user, string $query = '?date=2026-05-10'): array
    {
        $request = $user ? $this->actingAs($user) : $this;

        return $request->get("/spots/{$this->spot->id}{$query}")->assertOk()->viewData('season');
    }

    public function test_table_uses_the_same_trips_as_the_history(): void
    {
        $this->trip($this->me, 'private', 'アジ');        // 自分の非公開 → 数える
        $this->trip($this->other, 'public', 'サバ');      // ほかの人の全体公開 → 数える
        $this->trip($this->other, 'spot_hidden', 'メバル'); // 釣り場だけ隠す → 数えない（この釣り場だと分かってしまう）
        $this->trip($this->other, 'private', 'クロダイ');  // ほかの人の非公開 → 数えない

        $season = $this->season($this->me);

        $this->assertSame(['アジ', 'サバ'], array_keys($season['rows']));
        $this->assertSame(2, $season['tripCount']);
    }

    public function test_guest_sees_only_public_trips(): void
    {
        $this->trip($this->me, 'private', 'アジ');
        $this->trip($this->other, 'public', 'サバ');

        $this->assertSame(['サバ'], array_keys($this->season(null)['rows']));
    }

    public function test_cells_follow_the_same_rules_as_the_heatmap(): void
    {
        // 5月：1回で20匹／6月：3回で1匹ずつ
        $this->trip($this->me, 'public', 'アジ', 20);
        foreach (['01', '02', '03'] as $day) {
            $this->trip($this->me, 'public', 'アジ', 1, "2026-06-{$day} 06:00");
        }

        $row = $this->season($this->me)['rows']['アジ'];

        $this->assertSame(['fish' => 20, 'trips' => 1, 'level' => 4], $row[5]);
        $this->assertSame(['fish' => 3, 'trips' => 3, 'level' => 1], $row[6]);
        $this->assertSame(['fish' => 0, 'trips' => 0, 'level' => 0], $row[1]);
    }

    public function test_same_result_as_heatmap_for_the_same_trips(): void
    {
        $this->trip($this->me, 'public', 'アジ', 2);
        $this->trip($this->me, 'public', 'サバ', 1, '2026-08-01 06:00');

        // カルテの表と、ヒートマップ（自分・全国）が同じ表になる
        $heatmap = app(SeasonHeatmap::class)->build($this->me, 'mine', 'all');

        $this->assertSame($heatmap, $this->season($this->me));
    }

    public function test_month_of_the_chosen_date_is_marked(): void
    {
        $this->trip($this->me, 'public', 'アジ');

        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=2026-05-10")
            ->assertSee('5月<span class="sr-only">（選んだ日の月）</span>', false)
            ->assertSee('オレンジの枠＝選んだ日の月');

        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=2026-08-10")
            ->assertSee('8月<span class="sr-only">（選んだ日の月）</span>', false)
            ->assertDontSee('5月<span class="sr-only">', false);
    }

    public function test_shows_trip_count_and_message_when_empty(): void
    {
        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=2026-05-10")
            ->assertSee('まだ釣果の記録がありません');

        // 坊主の釣行だけでは、表は作らない
        Trip::factory()->create(['user_id' => $this->me->id, 'spot_id' => $this->spot->id, 'visibility' => 'public']);
        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=2026-05-10")
            ->assertSee('まだ釣果の記録がありません');

        $this->trip($this->me, 'public', 'アジ');
        $this->actingAs($this->me)->get("/spots/{$this->spot->id}?date=2026-05-10")
            ->assertSee('この表は釣行 <span class="font-bold">1</span> 件から作っています', false);
    }
}
