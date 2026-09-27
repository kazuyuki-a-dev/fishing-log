<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_others_private_spot_returns_404_but_own_private_spot_can_be_opened(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $mySecret = Spot::factory()->create(['visibility' => 'private', 'created_by' => $me->id]);
        $othersSecret = Spot::factory()->create(['visibility' => 'private', 'created_by' => $other->id]);

        $this->actingAs($me)->get("/spots/{$mySecret->id}")->assertOk();
        $this->actingAs($me)->get("/spots/{$othersSecret->id}")->assertNotFound();
    }

    public function test_only_others_public_trips_are_shown_on_a_public_spot(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['visibility' => 'public']);

        // ほかの人の釣行を、公開範囲ごとに1件ずつ。名前で見分ける
        foreach (['public' => '公開さん', 'spot_hidden' => '隠すさん', 'private' => '非公開さん'] as $visibility => $name) {
            $user = User::factory()->create(['name' => $name]);
            Trip::factory()->create(['user_id' => $user->id, 'spot_id' => $spot->id, 'visibility' => $visibility]);
        }

        $this->actingAs($me)->get("/spots/{$spot->id}")
            ->assertSee('公開さん')
            ->assertDontSee('隠すさん')
            ->assertDontSee('非公開さん');
    }

    public function test_others_trips_are_not_shown_on_a_private_spot_even_if_public(): void
    {
        $me = User::factory()->create();
        $mySecret = Spot::factory()->create(['visibility' => 'private', 'created_by' => $me->id]);
        $other = User::factory()->create(['name' => '公開さん']);
        Trip::factory()->create(['user_id' => $other->id, 'spot_id' => $mySecret->id, 'visibility' => 'public']);

        $this->actingAs($me)->get("/spots/{$mySecret->id}")
            ->assertDontSee('公開さん');
    }

    public function test_my_record_shows_visits_and_catches_and_hides_emails(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['visibility' => 'public']);

        // 3回行って、2回釣れた（1回は坊主）
        $trips = Trip::factory(3)->create(['user_id' => $me->id, 'spot_id' => $spot->id]);
        FishCatch::factory()->create(['trip_id' => $trips[0]->id, 'length_cm' => 30.5]);
        FishCatch::factory()->create(['trip_id' => $trips[1]->id, 'length_cm' => 25.0]);

        $other = User::factory()->create();
        Trip::factory()->create(['user_id' => $other->id, 'spot_id' => $spot->id, 'visibility' => 'public']);

        $this->actingAs($me)->get("/spots/{$spot->id}")
            ->assertSee('3回行って 2回釣れた')
            ->assertSee('30.5 cm')
            ->assertSee($other->name)
            ->assertDontSee($other->email);
    }
}
