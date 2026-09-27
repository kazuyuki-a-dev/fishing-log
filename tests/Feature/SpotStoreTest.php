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

        $response->assertRedirect(route('spots.index', ['prefecture' => '秋田県']));
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
}
