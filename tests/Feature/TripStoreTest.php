<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripStoreTest extends TestCase
{
    use RefreshDatabase;

    // 毎回同じ「釣行の基本の入力」を用意する
    private function tripInput(Spot $spot, array $overrides = []): array
    {
        return array_merge([
            'spot_id' => $spot->id,
            'went_at' => '2026-09-20 06:00',
            'time_of_day' => '朝マズメ',
            'visibility' => 'private',
            'weather' => '曇り',
        ], $overrides);
    }

    public function test_user_can_record_a_trip_with_catches(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['visibility' => 'public']);

        $this->actingAs($me)->post('/trips', $this->tripInput($spot, [
            'catches' => [
                ['fish_species' => 'アジ', 'method' => 'エサ', 'length_cm' => '21.5'],
                ['fish_species' => 'その他', 'fish_species_other' => 'ハゼ', 'method' => 'ルアー'],
            ],
        ]))->assertRedirect();

        $trip = Trip::first();
        $this->assertSame($me->id, $trip->user_id);
        $this->assertSame(2, $trip->catches()->count());
        $this->assertDatabaseHas('catches', ['trip_id' => $trip->id, 'fish_species' => 'アジ', 'length_cm' => 21.5]);
        $this->assertDatabaseHas('catches', ['trip_id' => $trip->id, 'fish_species' => 'ハゼ', 'method' => 'ルアー']);
        $this->assertSame('小潮', $trip->tide);
    }

    public function test_trip_without_catches_is_recorded_as_bozu(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['visibility' => 'public']);

        $this->actingAs($me)->post('/trips', $this->tripInput($spot))
            ->assertSessionHas('status', '釣行を記録しました（坊主）。');

        $this->assertSame(1, Trip::count());
        $this->assertSame(0, Trip::first()->catches()->count());
    }

    public function test_others_private_spot_cannot_be_chosen(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $secret = Spot::factory()->create(['visibility' => 'private', 'created_by' => $other->id]);

        $this->actingAs($me)->post('/trips', $this->tripInput($secret))
            ->assertSessionHasErrors('spot_id');

        $this->assertSame(0, Trip::count());
    }

    public function test_method_is_required_for_each_catch(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['visibility' => 'public']);

        $this->actingAs($me)->post('/trips', $this->tripInput($spot, [
            'catches' => [
                ['fish_species' => 'アジ', 'method' => 'エサ'],
                ['fish_species' => 'サバ'],
            ],
        ]))->assertSessionHasErrors(['catches.1.method' => '2匹目の釣り方は必須項目です。']);

        $this->assertSame(0, Trip::count());
    }
}
