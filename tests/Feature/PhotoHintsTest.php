<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhotoHintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_nearby_returns_spot_id_but_not_location(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create([
            'visibility' => 'public',
            'latitude' => 39.7310000,
            'longitude' => 140.0700000,
        ]);

        $this->actingAs($me)->getJson('/spots/nearby?lat=39.73&lng=140.07')
            ->assertJsonPath('spots.0.id', $spot->id)
            ->assertDontSee('39.731');
    }

    public function test_trip_form_accepts_heic_and_reads_photo_hints(): void
    {
        $me = User::factory()->create();
        Spot::factory()->create(['created_by' => $me->id]);

        $this->actingAs($me)->get('/trips/create')
            ->assertSee('image/heic', false)
            ->assertSee('pickPhoto($event)', false)
            ->assertSee('catchRows(', false);
    }

    public function test_spot_form_reads_location_from_photo_without_sending_it(): void
    {
        $me = User::factory()->create();

        $html = $this->actingAs($me)->get('/spots/create')
            ->assertSee('写真から位置を読み取る')
            ->getContent();

        // 位置を読むための写真の欄には name がない（フォームと一緒に送られない）
        $this->assertMatchesRegularExpression('/<input type="file" accept="image\/\*,\.heic,\.heif" @change="readLocationFrom\(\$event\)"/', $html);
        $this->assertStringNotContainsString('enctype="multipart/form-data"', $html);
    }
}
