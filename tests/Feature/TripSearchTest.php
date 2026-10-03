<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 釣行一覧の条件検索（FN-03・PG10）
 */
class TripSearchTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private User $other;
    private Spot $spot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->me = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->other = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->spot = Spot::factory()->create(['name' => '北の堤防', 'prefecture' => '秋田県', 'visibility' => 'public']);
    }

    /**
     * 釣行を作る。$catches は [魚種, 釣り方] の並び（空なら坊主）
     */
    private function trip(array $attributes = [], array $catches = [['アジ', 'エサ']]): Trip
    {
        $trip = Trip::factory()->create(array_merge([
            'user_id' => $this->me->id,
            'spot_id' => $this->spot->id,
            'went_at' => '2026-05-03 06:00',
            'time_of_day' => '朝マズメ',
            'tide' => '大潮',
            'weather' => '晴れ',
            'visibility' => 'private',
        ], $attributes));

        foreach ($catches as [$species, $method]) {
            FishCatch::factory()->create(['trip_id' => $trip->id, 'fish_species' => $species, 'method' => $method]);
        }

        return $trip;
    }

    // 一覧に出た釣行の番号
    private function ids(string $query, ?User $user = null): array
    {
        return $this->actingAs($user ?? $this->me)->get("/trips{$query}")
            ->assertOk()
            ->viewData('trips')->pluck('id')->sort()->values()->all();
    }

    public function test_each_condition_narrows_the_list(): void
    {
        $base = $this->trip();
        $otherSpot = Spot::factory()->create(['visibility' => 'public']);

        $cases = [
            'spot_id' => [['spot_id' => $otherSpot->id], "?spot_id={$otherSpot->id}"],
            'species' => [[], '?species=サバ'],
            'tide' => [['tide' => '小潮'], '?tide=小潮'],
            'weather' => [['weather' => '雨'], '?weather=雨'],
            'time_of_day' => [['time_of_day' => '夜'], '?time_of_day=夜'],
            'from' => [['went_at' => '2026-07-01 06:00'], '?from=2026-06-01'],
            'to' => [['went_at' => '2026-01-01 06:00'], '?to=2026-02-01'],
        ];

        foreach ($cases as $field => [$attributes, $query]) {
            $catches = $field === 'species' ? [['サバ', 'エサ']] : [['アジ', 'エサ']];
            $hit = $this->trip($attributes, $catches);

            $this->assertSame([$hit->id], $this->ids($query), "{$field} で絞り込めない");
        }

        // 条件なしなら全部
        $this->assertContains($base->id, $this->ids(''));
    }

    public function test_conditions_are_combined(): void
    {
        $hit = $this->trip(['tide' => '大潮', 'weather' => '曇り', 'time_of_day' => '朝マズメ'], [['アジ', 'ルアー']]);
        $this->trip(['tide' => '大潮', 'weather' => '晴れ', 'time_of_day' => '朝マズメ'], [['アジ', 'ルアー']]);
        $this->trip(['tide' => '大潮', 'weather' => '曇り', 'time_of_day' => '夜'], [['アジ', 'ルアー']]);

        $this->assertSame([$hit->id], $this->ids('?tide=大潮&weather=曇り&time_of_day=朝マズメ&method=ルアー'));
    }

    public function test_species_and_method_must_match_the_same_fish(): void
    {
        $hit = $this->trip([], [['アジ', 'ルアー']]);
        // アジ（エサ）とサバ（ルアー）：同じ1匹ではないので当てはまらない
        $this->trip([], [['アジ', 'エサ'], ['サバ', 'ルアー']]);

        $this->assertSame([$hit->id], $this->ids('?species=アジ&method=ルアー'));
    }

    public function test_bozu_trips_are_included_only_without_species_and_method(): void
    {
        $bozu = $this->trip([], []);
        $caught = $this->trip();

        $this->assertSame([$bozu->id, $caught->id], $this->ids('?tide=大潮'));
        $this->assertSame([$caught->id], $this->ids('?species=アジ'));
        $this->assertSame([$caught->id], $this->ids('?method=エサ'));
    }

    public function test_mine_lists_only_my_trips_including_private(): void
    {
        $mine = $this->trip(['visibility' => 'private']);
        $this->trip(['user_id' => $this->other->id, 'visibility' => 'public']);

        $this->assertSame([$mine->id], $this->ids(''));
    }

    public function test_public_lists_public_and_spot_hidden_and_hides_spot_name(): void
    {
        $public = $this->trip(['user_id' => $this->other->id, 'visibility' => 'public']);
        $hidden = $this->trip(['user_id' => $this->other->id, 'visibility' => 'spot_hidden']);
        $this->trip(['user_id' => $this->other->id, 'visibility' => 'private']);
        $this->trip(['visibility' => 'private']); // 自分の非公開も「みんな」には出ない

        $this->assertSame([$public->id, $hidden->id], $this->ids('?scope=public'));

        // 「釣り場だけ隠す」は釣り場名を伏せる（フィードと同じカード）
        $this->actingAs($this->me)->get('/trips?scope=public&tide=大潮')
            ->assertSee('釣り場は非公開（秋田県）');
    }

    public function test_public_with_spot_excludes_spot_hidden_trips(): void
    {
        $public = $this->trip(['user_id' => $this->other->id, 'visibility' => 'public']);
        $this->trip(['user_id' => $this->other->id, 'visibility' => 'spot_hidden']);
        // 釣行は全体公開でも、釣り場が非公開なら「釣り場だけ隠す」として扱う
        $privateSpot = Spot::factory()->create(['visibility' => 'private', 'created_by' => $this->me->id]);
        $this->trip(['visibility' => 'public', 'spot_id' => $privateSpot->id]);

        // 釣り場を指定すると、その釣り場での釣行だと分かってしまうので「釣り場だけ隠す」は出さない
        $this->assertSame([$public->id], $this->ids("?scope=public&spot_id={$this->spot->id}"));
        $this->assertSame([], $this->ids("?scope=public&spot_id={$privateSpot->id}"));
    }

    public function test_public_is_filtered_by_home_prefecture_by_default(): void
    {
        $aomori = Spot::factory()->create(['prefecture' => '青森県', 'visibility' => 'public']);
        $akitaTrip = $this->trip(['user_id' => $this->other->id, 'visibility' => 'public']);
        $aomoriTrip = $this->trip(['user_id' => $this->other->id, 'visibility' => 'public', 'spot_id' => $aomori->id]);

        $this->assertSame([$akitaTrip->id], $this->ids('?scope=public'));
        $this->assertSame([$aomoriTrip->id], $this->ids('?scope=public&prefecture=青森県'));
        $this->assertSame([$akitaTrip->id, $aomoriTrip->id], $this->ids('?scope=public&prefecture=all'));
    }

    public function test_others_private_spot_cannot_be_used_as_a_filter(): void
    {
        $secret = Spot::factory()->create(['visibility' => 'private', 'created_by' => $this->other->id]);
        $mine = $this->trip();

        // 選べない釣り場は「指定なし」として扱う
        $this->assertSame([$mine->id], $this->ids("?spot_id={$secret->id}"));
    }

    public function test_summary_shows_visits_catches_and_fish_by_method(): void
    {
        $this->trip([], [['アジ', 'エサ'], ['アジ', 'エサ'], ['サバ', 'ルアー']]);
        $this->trip([], [['アジ', 'ルアー']]);
        $this->trip([], []); // 坊主

        $this->actingAs($this->me)->get('/trips')
            ->assertSee('全部で')
            ->assertSee('3回行って 2回釣れた')
            ->assertSee('エサ 2匹・ルアー 2匹');

        // 魚種で絞ると、その魚だけを数える
        $this->actingAs($this->me)->get('/trips?species=アジ')
            ->assertSee('この条件で')
            ->assertSee('2回行って 2回釣れた')
            ->assertSee('エサ 2匹・ルアー 1匹');
    }

    public function test_conditions_stay_in_pagination_links_and_can_be_cleared(): void
    {
        foreach (range(1, 21) as $i) {
            $this->trip(['went_at' => '2026-05-03 06:00']);
        }

        $this->actingAs($this->me)->get('/trips?tide=大潮')
            ->assertSee('tide=%E5%A4%A7%E6%BD%AE&amp;page=2', false)
            ->assertSee('条件をクリア');
    }
}
