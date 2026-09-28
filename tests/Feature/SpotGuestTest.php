<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotGuestTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_asked_to_choose_a_prefecture_first(): void
    {
        $this->get('/spots')
            ->assertOk()
            ->assertSee('見たい都道府県を選んでください。')
            ->assertDontSee('釣り場を登録');
    }

    public function test_guest_sees_only_public_spots_in_the_chosen_prefecture(): void
    {
        Spot::factory()->create(['name' => '公開の磯', 'prefecture' => '秋田県', 'visibility' => 'public']);
        Spot::factory()->create(['name' => '秘密の堤防', 'prefecture' => '秋田県', 'visibility' => 'private']);

        $this->get('/spots?prefecture=秋田県')
            ->assertOk()
            ->assertSee('公開の磯')
            ->assertDontSee('秘密の堤防')
            ->assertDontSee('自分の釣行');
    }

    public function test_guest_gets_404_on_a_private_spot(): void
    {
        $spot = Spot::factory()->create(['visibility' => 'private']);

        $this->get("/spots/{$spot->id}")->assertNotFound();
    }

    public function test_guest_sees_only_public_trips_on_the_karte(): void
    {
        $spot = Spot::factory()->create(['visibility' => 'public']);

        foreach (['public' => '公開さん', 'spot_hidden' => '隠すさん', 'private' => '非公開さん'] as $visibility => $name) {
            $user = User::factory()->create(['name' => $name]);
            Trip::factory()->create(['user_id' => $user->id, 'spot_id' => $spot->id, 'visibility' => $visibility]);
        }

        $this->get("/spots/{$spot->id}")
            ->assertOk()
            ->assertSee('公開さん')
            ->assertDontSee('隠すさん')
            ->assertDontSee('非公開さん')
            ->assertDontSee('自分の実績')
            ->assertSee('会員登録する');
    }

    public function test_guest_cannot_open_the_spot_form(): void
    {
        $this->get('/spots/create')->assertRedirect('/login');
    }
}
