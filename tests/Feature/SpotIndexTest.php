<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_spots_and_own_spots_are_listed_but_others_private_spots_are_not(): void
    {
        $me = User::factory()->create(['home_prefecture' => '秋田県']);
        $other = User::factory()->create(['home_prefecture' => '秋田県']);

        Spot::factory()->create(['name' => 'みんなの公開の釣り場', 'prefecture' => '秋田県', 'visibility' => 'public', 'created_by' => $other->id]);
        Spot::factory()->create(['name' => '自分の非公開の釣り場', 'prefecture' => '秋田県', 'visibility' => 'private', 'created_by' => $me->id]);
        Spot::factory()->create(['name' => '他人の秘密の釣り場', 'prefecture' => '秋田県', 'visibility' => 'private', 'created_by' => $other->id]);

        $response = $this->actingAs($me)->get('/spots');

        $response->assertOk();
        $response->assertSee('みんなの公開の釣り場');
        $response->assertSee('自分の非公開の釣り場');
        $response->assertDontSee('他人の秘密の釣り場');
    }

    public function test_spots_are_filtered_by_home_prefecture_by_default(): void
    {
        $me = User::factory()->create(['home_prefecture' => '秋田県']);

        Spot::factory()->create(['name' => '秋田の釣り場', 'prefecture' => '秋田県', 'visibility' => 'public']);
        Spot::factory()->create(['name' => '青森の釣り場', 'prefecture' => '青森県', 'visibility' => 'public']);

        $this->actingAs($me)->get('/spots')
            ->assertSee('秋田の釣り場')
            ->assertDontSee('青森の釣り場');

        $this->actingAs($me)->get('/spots?prefecture=all')
            ->assertSee('秋田の釣り場')
            ->assertSee('青森の釣り場');
    }

    /**
     * 並べ替えの確かめ用（#128）。名前の順と、最後に行った日の順と、釣果数の順がばらばらになるようにする
     * - あ港：行っていない
     * - い港：2026-05-01 に行って 3匹
     * - う港：2026-09-01 に行って 1匹
     */
    private function spotsForSorting(User $me): void
    {
        Spot::factory()->create(['name' => 'あ港', 'prefecture' => '秋田県', 'visibility' => 'public']);
        $i = Spot::factory()->create(['name' => 'い港', 'prefecture' => '秋田県', 'visibility' => 'public']);
        $u = Spot::factory()->create(['name' => 'う港', 'prefecture' => '秋田県', 'visibility' => 'public']);

        $trip = Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $i->id, 'went_at' => '2026-05-01 06:00:00']);
        FishCatch::factory()->count(3)->create(['trip_id' => $trip->id]);
        $trip = Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $u->id, 'went_at' => '2026-09-01 06:00:00']);
        FishCatch::factory()->create(['trip_id' => $trip->id]);
    }

    public function test_my_catch_count_is_shown_without_others_catches(): void
    {
        $me = User::factory()->create(['home_prefecture' => '秋田県']);
        $other = User::factory()->create(['home_prefecture' => '秋田県']);
        $spot = Spot::factory()->create(['name' => '秋田港', 'prefecture' => '秋田県', 'visibility' => 'public']);

        $mine = Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $spot->id]);
        FishCatch::factory()->count(2)->create(['trip_id' => $mine->id]);
        // 坊主の釣行も1回に数える
        Trip::factory()->create(['user_id' => $me->id, 'spot_id' => $spot->id]);
        // ほかの人の釣果は数えない
        $others = Trip::factory()->create(['user_id' => $other->id, 'spot_id' => $spot->id, 'visibility' => 'public']);
        FishCatch::factory()->count(5)->create(['trip_id' => $others->id]);

        $this->actingAs($me)->get('/spots')
            ->assertOk()
            ->assertSeeText('自分の釣行 2 回・釣果 2 匹');
    }

    public function test_spots_can_be_sorted_by_last_visit_and_by_catches(): void
    {
        $me = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->spotsForSorting($me);

        // 初めは名前順
        $this->actingAs($me)->get('/spots')->assertSeeInOrder(['あ港', 'い港', 'う港']);
        // 最後に行った日の新しい順。行っていない釣り場は後ろ
        $this->actingAs($me)->get('/spots?sort=last')->assertSeeInOrder(['う港', 'い港', 'あ港']);
        // 釣果数の多い順
        $this->actingAs($me)->get('/spots?sort=catches')->assertSeeInOrder(['い港', 'う港', 'あ港']);
    }

    public function test_unknown_sort_and_guests_use_name_order(): void
    {
        $me = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->spotsForSorting($me);

        $this->actingAs($me)->get('/spots?sort=nonsense')->assertSeeInOrder(['あ港', 'い港', 'う港']);

        // ゲストには自分の記録がないので、並べ替えは出さず、名前順だけ
        auth()->logout();
        $this->get('/spots?prefecture=%E7%A7%8B%E7%94%B0%E7%9C%8C&sort=catches')
            ->assertOk()
            ->assertSeeInOrder(['あ港', 'い港', 'う港'])
            ->assertDontSeeText('釣果数順');
    }
}
