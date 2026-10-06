<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlannerTest extends TestCase
{
    use RefreshDatabase;

    private User $me;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['home_prefecture' => '秋田県']);
        $other = User::factory()->create();

        // 釣り場A：自分が小潮の日に2回行って2回釣れた。注意区分あり
        $spotA = Spot::factory()->create(['name' => '大漁の堤防', 'prefecture' => '秋田県', 'visibility' => 'public', 'caution_type' => '立入注意']);
        $this->caughtTrip($this->me, $spotA, '2024-01-17 06:00');
        $this->caughtTrip($this->me, $spotA, '2024-01-18 06:00');

        // 釣り場B：自分が2回（1回は坊主）＋ほかの人の全体公開が2回（釣れた）＋ほかの人の「釣り場だけ隠す」が1回（数えてはいけない）
        $spotB = Spot::factory()->create(['name' => 'ぼちぼちの港', 'prefecture' => '秋田県', 'visibility' => 'public', 'caution_type' => null]);
        $this->caughtTrip($this->me, $spotB, '2024-01-17 06:00');
        Trip::factory()->create(['user_id' => $this->me->id, 'spot_id' => $spotB->id, 'went_at' => '2024-01-18 06:00']);
        $this->caughtTrip($other, $spotB, '2024-01-17 06:00', 'public');
        $this->caughtTrip($other, $spotB, '2024-01-18 06:00', 'public');
        $this->caughtTrip($other, $spotB, '2024-01-19 06:00', 'spot_hidden');

        // ほかの県の釣り場と、ほかの人の非公開の釣り場
        Spot::factory()->create(['name' => '青森の釣り場', 'prefecture' => '青森県', 'visibility' => 'public']);
        Spot::factory()->create(['name' => '他人の秘密', 'prefecture' => '秋田県', 'visibility' => 'private', 'created_by' => $other->id]);
    }

    // 釣果1匹つきの釣行を作る、テスト用の手伝い
    private function caughtTrip(User $user, Spot $spot, string $wentAt, string $visibility = 'private'): void
    {
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'spot_id' => $spot->id,
            'went_at' => $wentAt,
            'visibility' => $visibility,
        ]);
        FishCatch::factory()->create(['trip_id' => $trip->id]);
    }

    public function test_shows_the_tide_without_the_lunar_day(): void
    {
        // 旧暦の日にちは近似でずれることがあるので出さない。潮の名前は出す（#130）
        $this->actingAs($this->me)->get('/planner?date=2024-01-19')
            ->assertOk()
            ->assertSee('2024年1月19日')
            ->assertSee('小潮')
            ->assertDontSee('旧暦');
    }

    public function test_spots_are_ordered_by_catches_including_others_public_trips(): void
    {
        // みんなの公開記録も使うと、Bは「4回行って3回釣れた」でAより上（釣り場だけ隠すの1回は数えない）
        $this->actingAs($this->me)->get('/planner?date=2024-01-19')
            ->assertOk()
            ->assertSee('小潮')
            ->assertSeeInOrder(['ぼちぼちの港', '大漁の堤防'])
            ->assertSee('4回行って 3回釣れた')
            ->assertSee('2回行って 2回釣れた');
    }

    public function test_mine_scope_uses_only_my_trips(): void
    {
        // 自分の記録だけにすると、Aは「2回行って2回」、Bは「2回行って1回」でAが上
        $this->actingAs($this->me)->get('/planner?date=2024-01-19&scope=mine')
            ->assertSeeInOrder(['大漁の堤防', 'ぼちぼちの港'])
            ->assertSee('2回行って 1回釣れた');
    }

    public function test_only_visible_spots_in_the_chosen_prefecture_are_listed(): void
    {
        $this->actingAs($this->me)->get('/planner?date=2024-01-19')
            ->assertDontSee('青森の釣り場')
            ->assertDontSee('他人の秘密');

        $this->actingAs($this->me)->get('/planner?date=2024-01-19&prefecture=all')
            ->assertSee('青森の釣り場')
            ->assertDontSee('他人の秘密');
    }

    public function test_caution_and_link_to_the_karte_with_the_same_date(): void
    {
        // 注意のしるしは注意区分だけ（「注意：」は付けない。#110）
        $this->actingAs($this->me)->get('/planner?date=2024-01-19')
            ->assertSee('<span class="crayon-caution-tag">立入注意</span>', false)
            ->assertDontSee('注意：')
            ->assertSee('date=2024-01-19', false);
    }

    public function test_first_spot_is_marked_only_when_it_has_a_catch(): void
    {
        // 釣れた記録がある県では、1位の釣り場名に黄色のマーカー（#110）
        $this->actingAs($this->me)->get('/planner?date=2024-01-19')
            ->assertSee('<span class="crayon-marker">ぼちぼちの港</span>', false);

        // 釣れた記録がない県では、1位にもマーカーを付けない
        $this->actingAs($this->me)->get('/planner?date=2024-01-19&prefecture=' . urlencode('青森県'))
            ->assertSee('青森の釣り場')
            ->assertDontSee('crayon-marker');
    }

    public function test_exact_match_comes_before_relaxed_matches(): void
    {
        // 釣り場C：小潮では坊主だけ、ほかの1月の日に3回釣れた → 月だけ一致
        $spotC = Spot::factory()->create(['name' => '月だけの磯', 'prefecture' => '秋田県', 'visibility' => 'public']);
        Trip::factory()->create(['user_id' => $this->me->id, 'spot_id' => $spotC->id, 'went_at' => '2024-01-19 06:00']);
        foreach (['2024-01-10 06:00', '2024-01-11 06:00', '2024-01-12 06:00'] as $wentAt) {
            $this->caughtTrip($this->me, $spotC, $wentAt);
        }

        // 釣れた回数は C の3回が多いが、ぴったり一致の A・B が上
        $this->actingAs($this->me)->get('/planner?date=2024-01-19&scope=mine')
            ->assertSeeInOrder(['大漁の堤防', 'ぼちぼちの港', '月だけの磯'])
            ->assertSee('ぴったり一致')
            ->assertSee('月だけ一致')
            ->assertSee('1月（潮は問わない）の日：')
            // ゆるめたときも、ぴったりの条件の坊主の記録を出す
            ->assertSee('ぴったりの条件（小潮）では 1回行って 0回釣れた');
    }

    public function test_spot_without_any_catch_is_listed_last(): void
    {
        Spot::factory()->create(['name' => '記録なしの浜', 'prefecture' => '秋田県', 'visibility' => 'public']);

        $this->actingAs($this->me)->get('/planner?date=2024-01-19')
            ->assertSeeInOrder(['ぼちぼちの港', '大漁の堤防', '記録なしの浜'])
            ->assertSee('この条件で釣れた記録はまだありません');
    }
}
