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
 * シーズンヒートマップ（FN-02・PG16・FN-10）
 */
class SeasonHeatmapTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private User $other;
    private Spot $akita;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->other = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->akita = Spot::factory()->create(['prefecture' => '秋田県', 'visibility' => 'public']);
    }

    /**
     * 釣行を1件作り、同じ魚を $fish 匹釣ったことにする
     */
    private function trip(array $attributes = [], string $species = 'アジ', int $fish = 1): Trip
    {
        $trip = Trip::factory()->create(array_merge([
            'user_id' => $this->other->id,
            'spot_id' => $this->akita->id,
            'went_at' => '2026-05-03 06:00',
            'visibility' => 'public',
        ], $attributes));

        FishCatch::factory()->count($fish)->create(['trip_id' => $trip->id, 'fish_species' => $species]);

        return $trip;
    }

    // 表（魚種 => 月 => マス）だけを取り出す
    private function build(string $scope = 'public', string $prefecture = '秋田県'): array
    {
        return app(SeasonHeatmap::class)->build($this->me, $scope, $prefecture)['rows'];
    }

    public function test_fish_and_trips_are_counted_separately(): void
    {
        $this->trip([], 'アジ', 3);

        // 1回の釣行で3匹：匹数は3、回数は1
        $this->assertSame(3, $this->build()['アジ'][5]['fish']);
        $this->assertSame(1, $this->build()['アジ'][5]['trips']);
        // 元になった釣行の数も1件
        $this->assertSame(1, app(SeasonHeatmap::class)->build($this->me, 'public', '秋田県')['tripCount']);
    }

    public function test_public_counts_public_and_spot_hidden_but_not_private(): void
    {
        $this->trip(['visibility' => 'public']);
        $this->trip(['visibility' => 'spot_hidden']);
        $this->trip(['visibility' => 'private']);
        // 自分の非公開も「みんな」には入れない
        $this->trip(['visibility' => 'private', 'user_id' => $this->me->id]);

        $this->assertSame(2, $this->build('public')['アジ'][5]['trips']);
    }

    public function test_mine_counts_own_private_trips_and_not_others(): void
    {
        $this->trip(['visibility' => 'private', 'user_id' => $this->me->id]);
        $this->trip(['visibility' => 'public']);

        $this->assertSame(1, $this->build('mine')['アジ'][5]['trips']);
    }

    public function test_other_prefectures_are_counted_only_for_all(): void
    {
        $aomori = Spot::factory()->create(['prefecture' => '青森県', 'visibility' => 'public']);
        $this->trip(['spot_id' => $aomori->id]);

        $this->assertSame([], $this->build('public', '秋田県'));
        $this->assertSame(1, $this->build('public', 'all')['アジ'][5]['trips']);
    }

    public function test_color_follows_fish_count_and_empty_months_are_white(): void
    {
        // 5月：1回の釣行で20匹（群れが入った）
        $this->trip(['went_at' => '2026-05-03 06:00'], 'アジ', 20);
        // 6月：3回の釣行で1匹ずつ
        foreach (['01', '02', '03'] as $day) {
            $this->trip(['went_at' => "2026-06-{$day} 06:00"]);
        }

        $row = $this->build()['アジ'];
        // 匹数の多い5月がいちばん濃い。回数は1回と添える
        $this->assertSame(['fish' => 20, 'trips' => 1, 'level' => 4], $row[5]);
        // 3匹の6月は薄い色（1匹以上なら色は付く）
        $this->assertSame(['fish' => 3, 'trips' => 3, 'level' => 1], $row[6]);
        // 記録のない月は白（0）
        $this->assertSame(['fish' => 0, 'trips' => 0, 'level' => 0], $row[1]);
    }

    public function test_only_species_with_records_are_listed_in_config_order(): void
    {
        $this->trip([], 'サバ');
        $this->trip([], 'アジ');

        $this->assertSame(['アジ', 'サバ'], array_keys($this->build()));
    }

    public function test_page_shows_public_of_home_prefecture_by_default(): void
    {
        $this->trip(['visibility' => 'public']);
        $this->trip(['visibility' => 'private', 'user_id' => $this->me->id], 'サバ');

        $this->actingAs($this->me)->get('/analysis/heatmap')
            ->assertOk()
            ->assertSee('aria-current="true"', false)
            ->assertSeeInOrder(['aria-current="true"', 'みんな'], false)
            ->assertSee('<option value="秋田県" selected', false)
            ->assertSee('アジ')
            ->assertSee('この表は釣行 <span class="font-bold">1</span> 件から作っています', false)
            ->assertSee('1回')
            // 自分の非公開の釣果は「みんな」に出ない
            ->assertDontSee('サバ');
    }

    public function test_page_can_switch_to_mine(): void
    {
        $this->trip(['visibility' => 'private', 'user_id' => $this->me->id], 'サバ');

        $this->actingAs($this->me)->get('/analysis/heatmap?scope=mine')
            ->assertOk()
            ->assertSee('サバ');
    }

    public function test_empty_page_guides_to_record_and_all_prefectures(): void
    {
        $this->actingAs($this->me)->get('/analysis/heatmap')
            ->assertOk()
            ->assertSee('まだこの条件の記録がありません')
            ->assertSee('全国を見る');
    }

    public function test_dashboard_has_a_link_and_guest_is_sent_to_login(): void
    {
        $this->actingAs($this->me)->get('/dashboard')
            ->assertSee(route('analysis.heatmap'));

        auth()->logout();
        $this->get('/analysis/heatmap')->assertRedirect('/login');
    }
}
