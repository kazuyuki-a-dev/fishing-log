<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_public_and_spot_hidden_trips_are_listed(): void
    {
        $spot = Spot::factory()->create(['visibility' => 'public']);

        foreach (['public' => '公開さん', 'spot_hidden' => '隠すさん', 'private' => '非公開さん'] as $visibility => $name) {
            $user = User::factory()->create(['name' => $name]);
            Trip::factory()->create(['user_id' => $user->id, 'spot_id' => $spot->id, 'visibility' => $visibility]);
        }

        $this->get('/feed?prefecture=all')
            ->assertOk()
            ->assertSee('公開さん')
            ->assertSee('隠すさん')
            ->assertDontSee('非公開さん');
    }

    public function test_spot_hidden_trip_does_not_contain_spot_name(): void
    {
        $spot = Spot::factory()->create(['name' => 'ひみつの岩場', 'visibility' => 'public']);
        Trip::factory()->create(['spot_id' => $spot->id, 'visibility' => 'spot_hidden']);

        $this->get('/feed?prefecture=all')
            ->assertSee('釣り場は非公開')
            ->assertDontSee('ひみつの岩場')
            ->assertDontSee(route('spots.show', $spot));
    }

    public function test_public_trip_on_private_spot_does_not_contain_spot_name(): void
    {
        $spot = Spot::factory()->create(['name' => 'ひみつの岩場', 'visibility' => 'private']);
        Trip::factory()->create(['spot_id' => $spot->id, 'visibility' => 'public']);

        $this->get('/feed?prefecture=all')
            ->assertSee('釣り場は非公開')
            ->assertDontSee('ひみつの岩場');
    }

    public function test_member_sees_home_prefecture_by_default_and_no_emails(): void
    {
        $me = User::factory()->create(['home_prefecture' => '秋田県']);
        $akitaUser = User::factory()->create(['name' => '秋田さん']);
        $aomoriUser = User::factory()->create(['name' => '青森さん']);

        Trip::factory()->create([
            'user_id' => $akitaUser->id,
            'spot_id' => Spot::factory()->create(['prefecture' => '秋田県', 'visibility' => 'public'])->id,
            'visibility' => 'public',
        ]);
        Trip::factory()->create([
            'user_id' => $aomoriUser->id,
            'spot_id' => Spot::factory()->create(['prefecture' => '青森県', 'visibility' => 'public'])->id,
            'visibility' => 'public',
        ]);

        $this->actingAs($me)->get('/feed')
            ->assertSee('秋田さん')
            ->assertDontSee('青森さん')
            ->assertDontSee($akitaUser->email);
    }

    public function test_guest_is_asked_to_choose_a_prefecture_first(): void
    {
        $this->get('/feed')
            ->assertOk()
            ->assertSee('見たい都道府県を選んでください。');
    }

    public function test_species_filter_shows_only_trips_with_that_fish(): void
    {
        $spot = Spot::factory()->create(['visibility' => 'public']);
        $magochiUser = User::factory()->create(['name' => 'マゴチ狙いさん']);
        $tachiuoUser = User::factory()->create(['name' => 'タチウオ狙いさん']);

        $magochiTrip = Trip::factory()->create(['user_id' => $magochiUser->id, 'spot_id' => $spot->id, 'visibility' => 'public']);
        $tachiuoTrip = Trip::factory()->create(['user_id' => $tachiuoUser->id, 'spot_id' => $spot->id, 'visibility' => 'public']);
        FishCatch::factory()->create(['trip_id' => $magochiTrip->id, 'fish_species' => 'マゴチ']);
        FishCatch::factory()->create(['trip_id' => $tachiuoTrip->id, 'fish_species' => 'タチウオ']);

        $this->get('/feed?prefecture=all&species=マゴチ')
            ->assertSee('マゴチ狙いさん')
            ->assertDontSee('タチウオ狙いさん');
    }

    public function test_empty_feed_shows_first_poster_button(): void
    {
        $this->get('/feed?prefecture=沖縄県')
            ->assertOk()
            ->assertSee('最初の投稿者になる');
    }
}
