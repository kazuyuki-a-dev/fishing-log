<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 釣行を記録する画面の県と釣り場の選択（#142）
 * 県を選ぶと釣り場が入れかわる動き（ブラウザの中）は画面で確かめる。ここでは、最初に選ばれる県と、渡す釣り場を確かめる
 */
class TripFormSpotPickerTest extends TestCase
{
    use RefreshDatabase;

    private User $me;
    private Spot $aomori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->me = User::factory()->create(['home_prefecture' => '秋田県']);
        $this->aomori = Spot::factory()->create(['name' => '青森の港', 'prefecture' => '青森県', 'visibility' => 'public']);
    }

    // 県の選択（id="spot_prefecture"）で、どの県が選ばれているか
    private function assertPrefectureSelected(string $html, string $prefecture): void
    {
        $this->assertMatchesRegularExpression(
            '/id="spot_prefecture"[\s\S]*?<option value="' . $prefecture . '"\s+selected/u',
            $html,
        );
    }

    public function test_new_trip_starts_with_home_prefecture(): void
    {
        $html = $this->actingAs($this->me)->get('/trips/create')->assertOk()->getContent();

        $this->assertPrefectureSelected($html, '秋田県');
    }

    public function test_coming_from_a_karte_starts_with_that_spot_prefecture(): void
    {
        // カルテの「この釣り場で釣行を記録」（?spot=…）から来たとき
        $html = $this->actingAs($this->me)->get("/trips/create?spot={$this->aomori->id}")->assertOk()->getContent();

        $this->assertPrefectureSelected($html, '青森県');
    }

    public function test_editing_starts_with_the_trip_spot_prefecture(): void
    {
        $trip = Trip::factory()->create(['user_id' => $this->me->id, 'spot_id' => $this->aomori->id]);

        $html = $this->actingAs($this->me)->get("/trips/{$trip->id}/edit")->assertOk()->getContent();

        $this->assertPrefectureSelected($html, '青森県');
    }

    public function test_only_selectable_spots_are_passed_to_the_picker(): void
    {
        // ほかの人の非公開の釣り場は、選べる釣り場の一覧に入れない（NF-01）
        $other = User::factory()->create();
        $secret = Spot::factory()->create(['name' => '他人の秘密の磯', 'visibility' => 'private', 'created_by' => $other->id]);

        $this->actingAs($this->me)->get('/trips/create')
            ->assertSee('青森の港')
            ->assertDontSee('他人の秘密の磯')
            // 釣り場の一覧は JavaScript 用に書き出す（引用符は \u0022 になる）
            ->assertSee('\u0022id\u0022:' . $this->aomori->id . ',', false)
            ->assertDontSee('\u0022id\u0022:' . $secret->id . ',', false);
    }

    public function test_bulk_form_gets_home_prefecture_and_selectable_spots(): void
    {
        $this->actingAs($this->me)->get('/trips/create?mode=bulk')
            ->assertOk()
            ->assertSee('bulkRows(', false)
            ->assertSee('青森の港')
            ->assertSee('秋田県');
    }
}
