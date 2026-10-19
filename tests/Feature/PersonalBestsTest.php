<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Services\PersonalBests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 魚種ごとの自己ベスト（#170）
 */
class PersonalBestsTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = User::factory()->create();
        $this->spot = Spot::factory()->create(['created_by' => $this->me->id, 'name' => '岬の港', 'latitude' => null, 'longitude' => null]);
    }

    /** 1匹だけ釣れた釣行を作り、その釣果を返す */
    private function catchOf(User $user, string $fish, ?float $length, string $wentAt = '2026-05-01 06:00', string $visibility = 'private'): FishCatch
    {
        $trip = Trip::factory()->create(['user_id' => $user->id, 'spot_id' => $this->spot->id, 'went_at' => $wentAt, 'visibility' => $visibility]);

        return FishCatch::factory()->create(['trip_id' => $trip->id, 'fish_species' => $fish, 'length_cm' => $length]);
    }

    public function test_best_of_each_species_in_size_order(): void
    {
        $this->catchOf($this->me, 'シーバス', 60.0);
        $bestSeabass = $this->catchOf($this->me, 'シーバス', 73.5);
        $bestAji = $this->catchOf($this->me, 'アジ', 25.0);
        $this->catchOf($this->me, 'メバル', null); // 長さがない釣果は数えない

        $bests = app(PersonalBests::class)->forUser($this->me);

        $this->assertSame([$bestSeabass->id, $bestAji->id], $bests->pluck('id')->all());
        $this->assertSame(2, app(PersonalBests::class)->speciesCount($this->me));
    }

    public function test_same_length_keeps_the_one_caught_first(): void
    {
        $later = $this->catchOf($this->me, 'クロダイ', 48.0, '2026-06-01 06:00');
        $first = $this->catchOf($this->me, 'クロダイ', 48.0, '2025-06-01 06:00');

        $this->assertSame([$first->id], app(PersonalBests::class)->forUser($this->me)->pluck('id')->all());
        $this->assertNotSame($later->id, $first->id);
    }

    public function test_only_my_catches_including_private(): void
    {
        $mine = $this->catchOf($this->me, 'シーバス', 50.0, visibility: 'private');
        $this->catchOf(User::factory()->create(), 'シーバス', 90.0, visibility: 'public'); // ほかの人の大物は入らない

        $this->assertSame([$mine->id], app(PersonalBests::class)->forUser($this->me)->pluck('id')->all());
    }

    public function test_dashboard_links_the_biggest_and_shows_top_three(): void
    {
        $biggest = $this->catchOf($this->me, 'シーバス', 73.5);
        $this->catchOf($this->me, 'クロダイ', 48.2);
        $this->catchOf($this->me, 'アイナメ', 41.0);
        $this->catchOf($this->me, 'アジ', 25.0);

        $this->actingAs($this->me)->get('/dashboard')
            ->assertOk()
            // 「自分の最大サイズ」を押すと、その釣果の釣行詳細へ
            ->assertSee('href="' . route('trips.show', $biggest->trip_id) . '"', false)
            ->assertSeeInOrder(['自己ベスト', 'すべて見る（4種類）', 'シーバス', '73.5 cm', 'クロダイ', 'アイナメ'])
            ->assertSee('href="' . route('bests.index') . '"', false);
    }

    public function test_dashboard_hides_bests_without_sizes(): void
    {
        $this->actingAs($this->me)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('すべて見る（');
    }

    public function test_bests_page_lists_every_species_with_links(): void
    {
        $seabass = $this->catchOf($this->me, 'シーバス', 73.5);
        $aji = $this->catchOf($this->me, 'アジ', 25.0, '2026-08-03 06:00');

        $this->actingAs($this->me)->get('/bests')
            ->assertOk()
            ->assertSeeInOrder(['シーバスの自己ベスト', '73.5', 'アジの自己ベスト', '25.0'])
            ->assertSee('2026/08/03・岬の港')
            ->assertSee('href="' . route('trips.show', $seabass->trip_id) . '"', false)
            ->assertSee('href="' . route('trips.show', $aji->trip_id) . '"', false);
    }

    public function test_bests_page_without_sizes_explains_what_to_do(): void
    {
        $this->actingAs($this->me)->get('/bests')
            ->assertOk()
            ->assertSee('サイズを入れた釣果がまだありません');
    }

    public function test_crown_on_dashboard_and_bests_page(): void
    {
        // 王冠（#174）
        $this->catchOf($this->me, 'シーバス', 73.5);

        $this->actingAs($this->me)->get('/dashboard')->assertSee('crayon-crown', false);
        $this->actingAs($this->me)->get('/bests')->assertSee('crayon-crown', false);
    }

    public function test_trip_detail_marks_only_the_personal_best(): void
    {
        $best = $this->catchOf($this->me, 'シーバス', 73.5);
        $smaller = $this->catchOf($this->me, 'シーバス', 60.0);

        // 自己ベストの釣果には「自己ベスト」の印、小さいほうには出ない
        $this->actingAs($this->me)->get("/trips/{$best->trip_id}")->assertSee('自己ベスト')->assertSee('crayon-crown', false);
        $this->actingAs($this->me)->get("/trips/{$smaller->trip_id}")->assertDontSee('自己ベスト')->assertDontSee('crayon-crown', false);
    }

    public function test_others_see_the_mark_on_a_public_trip(): void
    {
        // 公開の釣行なら、ほかの人が見ても印が出る（釣った本人の自己ベストで決める）
        $this->spot->update(['visibility' => 'public']);
        $best = $this->catchOf($this->me, 'クロダイ', 48.0, visibility: 'public');
        $viewer = User::factory()->create();
        $this->catchOf($viewer, 'クロダイ', 55.0); // 見る人の記録は関係ない

        $this->actingAs($viewer)->get("/trips/{$best->trip_id}")->assertOk()->assertSee('自己ベスト');
    }

    public function test_guest_cannot_see_bests_page(): void
    {
        $this->get('/bests')->assertRedirect('/login');
    }
}
