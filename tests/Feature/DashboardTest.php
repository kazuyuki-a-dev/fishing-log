<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_counts_only_my_catches_species_and_days(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['visibility' => 'public']);

        // 9月1日に2回（朝と夕方）、9月5日に1回（坊主）→ 釣行した日は「2日」
        $morning = Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $spot->id, 'went_at' => '2026-09-01 06:00:00']);
        $evening = Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $spot->id, 'went_at' => '2026-09-01 17:00:00']);
        Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $spot->id, 'went_at' => '2026-09-05 06:00:00']);

        // 朝3匹・夕方2匹 → 累計「5匹」。アジ・マゴチ・ハゼ（その他）→「3種類」
        FishCatch::factory()->create(['trip_id' => $morning->id, 'fish_species' => 'アジ', 'length_cm' => 20.0]);
        FishCatch::factory()->create(['trip_id' => $morning->id, 'fish_species' => 'アジ', 'length_cm' => 25.0]);
        FishCatch::factory()->create(['trip_id' => $morning->id, 'fish_species' => 'マゴチ', 'length_cm' => 40.5]);
        FishCatch::factory()->create(['trip_id' => $evening->id, 'fish_species' => 'ハゼ', 'length_cm' => null]);
        FishCatch::factory()->create(['trip_id' => $evening->id, 'fish_species' => 'アジ', 'length_cm' => 18.0]);

        // ほかの人の大物は数えない（非公開にして、新着にも出ないようにする）
        $otherTrip = Trip::factory()->create(['spot_id' => $spot->id, 'visibility' => 'private']);
        FishCatch::factory()->create(['trip_id' => $otherTrip->id, 'fish_species' => 'ブリ', 'length_cm' => 100.0]);

        $this->actingAs($me)->get('/dashboard')
            ->assertOk()
            ->assertSeeText('5匹')
            ->assertSeeText('3種類')
            ->assertSeeText('2日')
            ->assertSeeText('40.5cm')
            ->assertDontSee('100.0');
    }

    public function test_my_spots_show_visits_and_catches(): void
    {
        $me = User::factory()->create();
        $often = Spot::factory()->create(['visibility' => 'public']);
        $once = Spot::factory()->create(['visibility' => 'public']);

        $first = Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $often->id]);
        Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $often->id]);
        Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $once->id]);
        FishCatch::factory()->create(['trip_id' => $first->id]);

        // 行った回数の多い順に並ぶ
        $this->actingAs($me)->get('/dashboard')
            ->assertSeeInOrder(['2回行って 1回釣れた', '1回行って 0回釣れた']);
    }

    public function test_new_user_sees_zeros_and_guidance(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')
            ->assertOk()
            ->assertSeeText('0匹')
            ->assertSeeText('0種類')
            ->assertSeeText('0日')
            ->assertSee('記録なし')
            ->assertSee('まだ釣行の記録がありません');
    }
}
