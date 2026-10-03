<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripShowTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $viewer;
    private Spot $publicSpot;
    private Spot $privateSpot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => '持ち主さん']);
        $this->viewer = User::factory()->create();
        $this->publicSpot = Spot::factory()->create(['name' => 'みんなの堤防', 'prefecture' => '秋田県', 'visibility' => 'public']);
        $this->privateSpot = Spot::factory()->create(['name' => '秘密の磯', 'prefecture' => '秋田県', 'visibility' => 'private', 'created_by' => $this->owner->id]);
    }

    // 持ち主の釣行を1つ作る。メモには場所が分かることを書いておく
    private function tripAt(Spot $spot, string $visibility): Trip
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->owner->id,
            'spot_id' => $spot->id,
            'visibility' => $visibility,
            'notes' => '赤灯台の下で釣れた',
        ]);
        FishCatch::factory()->create([
            'trip_id' => $trip->id,
            'method_detail' => 'サビキ',
            'notes' => '足元で掛かった',
        ]);

        return $trip;
    }

    public function test_owner_sees_everything_including_notes(): void
    {
        $trip = $this->tripAt($this->publicSpot, 'private');

        $this->actingAs($this->owner)->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('みんなの堤防')
            ->assertSee('赤灯台の下で釣れた')
            ->assertSee('足元で掛かった');
    }

    public function test_others_can_see_public_trip_but_not_notes(): void
    {
        $trip = $this->tripAt($this->publicSpot, 'public');

        $this->actingAs($this->viewer)->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('みんなの堤防')
            ->assertSee('持ち主さん')
            ->assertSee('サビキ')
            ->assertDontSee('赤灯台の下で釣れた')
            ->assertDontSee('足元で掛かった');
    }

    public function test_spot_hidden_trip_does_not_contain_spot_name_or_link(): void
    {
        // NF-06 ②：画面で隠すだけでなく、返す HTML に釣り場名もリンクも入っていない
        $trip = $this->tripAt($this->publicSpot, 'spot_hidden');

        $this->actingAs($this->viewer)->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertSee('秋田県')
            ->assertSee('サビキ')
            ->assertDontSee('みんなの堤防')
            ->assertDontSee(route('spots.show', $this->publicSpot));
    }

    public function test_public_trip_on_private_spot_is_treated_as_spot_hidden(): void
    {
        // NF-06 ⑥：釣り場が非公開なら、全体公開の釣行でも釣り場名を出さない
        $trip = $this->tripAt($this->privateSpot, 'public');

        $this->actingAs($this->viewer)->get("/trips/{$trip->id}")
            ->assertOk()
            ->assertDontSee('秘密の磯')
            ->assertDontSee(route('spots.show', $this->privateSpot));
    }

    public function test_private_trip_returns_404_to_others(): void
    {
        // NF-06 ①
        $trip = $this->tripAt($this->publicSpot, 'private');

        $this->actingAs($this->viewer)->get("/trips/{$trip->id}")->assertNotFound();
    }

    public function test_guest_is_sent_to_login(): void
    {
        // NF-06 ④：ゲストは釣行の詳細を見られず、ログイン画面に案内される
        $trip = $this->tripAt($this->publicSpot, 'public');

        $this->get("/trips/{$trip->id}")->assertRedirect(route('login'));
    }

    public function test_index_lists_only_my_trips(): void
    {
        $others = $this->tripAt($this->publicSpot, 'public');
        $mySpot = Spot::factory()->create(['name' => '自分の港', 'visibility' => 'public']);
        $mine = Trip::factory()->create(['user_id' => $this->viewer->id, 'spot_id' => $mySpot->id]);

        // 釣り場の選択肢には公開の釣り場も出るので、画面の文字ではなく一覧に渡された釣行で確かめる
        $trips = $this->actingAs($this->viewer)->get('/trips')
            ->assertOk()
            ->assertSee('自分の港')
            ->viewData('trips');

        $this->assertSame([$mine->id], $trips->pluck('id')->all());
        $this->assertNotContains($others->id, $trips->pluck('id')->all());
    }
}
