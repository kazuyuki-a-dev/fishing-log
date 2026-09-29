<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NearbySpotsTest extends TestCase
{
    use RefreshDatabase;

    // ピンを置く位置
    private const LAT = 39.7300000;
    private const LNG = 140.0700000;

    private function spotNear(array $attributes, float $northMeters = 110): Spot
    {
        // 緯度 0.001 度 ≒ 111m。北へ $northMeters ずらした位置に釣り場を置く
        return Spot::factory()->create($attributes + [
            'latitude' => self::LAT + $northMeters / 111000,
            'longitude' => self::LNG,
        ]);
    }

    private function nearby(User $user)
    {
        return $this->actingAs($user)->getJson('/spots/nearby?lat=' . self::LAT . '&lng=' . self::LNG);
    }

    public function test_public_and_my_spots_nearby_are_suggested(): void
    {
        $me = User::factory()->create();
        $this->spotNear(['name' => 'みんなの堤防', 'visibility' => 'public']);
        $this->spotNear(['name' => '自分の磯', 'visibility' => 'private', 'created_by' => $me->id]);

        $this->nearby($me)
            ->assertOk()
            ->assertJsonFragment(['name' => 'みんなの堤防'])
            ->assertJsonFragment(['name' => '自分の磯']);
    }

    public function test_others_private_spot_is_not_suggested(): void
    {
        $this->spotNear(['name' => 'ひみつの岩場', 'visibility' => 'private', 'created_by' => User::factory()->create()->id]);

        $this->nearby(User::factory()->create())
            ->assertOk()
            ->assertJsonMissing(['name' => 'ひみつの岩場']);
    }

    public function test_spots_farther_than_300m_are_not_suggested(): void
    {
        $this->spotNear(['name' => 'となりの港', 'visibility' => 'public'], 1000);

        $this->nearby(User::factory()->create())
            ->assertOk()
            ->assertJsonMissing(['name' => 'となりの港']);
    }

    public function test_location_is_not_returned_and_distance_is_rounded(): void
    {
        $spot = $this->spotNear(['name' => 'みんなの堤防', 'visibility' => 'public'], 130);

        $response = $this->nearby(User::factory()->create())
            ->assertJsonStructure(['spots' => [['name', 'distance', 'url']]])
            ->assertDontSee((string) $spot->fresh()->latitude);

        // 130m → 約 150m（50m 単位）
        $this->assertSame(150, $response->json('spots.0.distance'));
    }

    public function test_guest_cannot_use_it(): void
    {
        $this->getJson('/spots/nearby?lat=' . self::LAT . '&lng=' . self::LNG)
            ->assertUnauthorized();
    }

    public function test_the_create_form_uses_it_but_the_edit_form_does_not(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $me->id]);

        $this->actingAs($me)->get('/spots/create')->assertSee('\/spots\/nearby', false);
        $this->actingAs($me)->get("/spots/{$spot->id}/edit")->assertDontSee('\/spots\/nearby', false);
    }
}
