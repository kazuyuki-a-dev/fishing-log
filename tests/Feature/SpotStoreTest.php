<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_a_spot(): void
    {
        $me = User::factory()->create();

        $response = $this->actingAs($me)->post('/spots', [
            'name' => '新しい堤防',
            'prefecture' => '秋田県',
            'visibility' => 'private',
        ]);

        $spot = Spot::where('name', '新しい堤防')->firstOrFail();
        $response->assertRedirect(route('spots.show', $spot))
            ->assertSessionHas('registered', true);
        $this->assertDatabaseHas('spots', [
            'name' => '新しい堤防',
            'created_by' => $me->id,
            'updated_by' => $me->id,
        ]);
    }

    public function test_spot_name_error_uses_spot_name_label(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->post('/spots', [
            'name' => '',
            'prefecture' => '秋田県',
            'visibility' => 'private',
        ])->assertSessionHasErrors(['name' => '釣り場名は必須項目です。']);
    }

    public function test_created_by_cannot_be_set_from_the_form(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($me)->post('/spots', [
            'name' => 'なりすましの釣り場',
            'prefecture' => '秋田県',
            'visibility' => 'public',
            'created_by' => $other->id,
        ]);

        $this->assertSame($me->id, Spot::where('name', 'なりすましの釣り場')->first()->created_by);
    }

    public function test_karte_shows_trip_button_right_after_registering(): void
    {
        $this->actingAs(User::factory()->create())
            ->followingRedirects()
            ->post('/spots', ['name' => '新しい堤防', 'prefecture' => '秋田県', 'visibility' => 'private'])
            ->assertSee('釣り場を登録しました。')
            ->assertSee('この釣り場で釣行を記録する');
    }

    public function test_create_form_map_opens_at_home_prefecture(): void
    {
        $me = User::factory()->create(['home_prefecture' => '沖縄県']);

        $this->actingAs($me)->get('/spots/create')
            ->assertSee('[26.21,127.68]', false);
    }

    public function test_edit_form_map_opens_at_the_spot_prefecture(): void
    {
        $me = User::factory()->create(['home_prefecture' => '沖縄県']);
        $spot = Spot::factory()->create(['created_by' => $me->id, 'prefecture' => '北海道', 'latitude' => null, 'longitude' => null]);

        $this->actingAs($me)->get("/spots/{$spot->id}/edit")
            ->assertSee('[43.06,141.35]', false);
    }
}
