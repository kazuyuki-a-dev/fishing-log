<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $other;
    private Spot $spot;
    private Trip $trip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create();
        $this->other = User::factory()->create();
        $this->spot = Spot::factory()->create(['visibility' => 'public']);

        // 2024年1月17日（小潮）に、アジを1匹
        $this->trip = Trip::factory()->create([
            'user_id' => $this->owner->id,
            'spot_id' => $this->spot->id,
            'went_at' => '2024-01-17 06:00',
        ]);
        FishCatch::factory()->create(['trip_id' => $this->trip->id, 'fish_species' => 'アジ']);
    }

    // 更新のときに送る入力
    private function input(array $overrides = []): array
    {
        return array_merge([
            'spot_id' => $this->spot->id,
            'went_at' => '2024-01-11 06:00',
            'time_of_day' => '朝マズメ',
            'visibility' => 'private',
            'catches' => [
                ['fish_species' => 'サバ', 'method' => 'ルアー'],
                ['fish_species' => 'サバ', 'method' => 'ルアー'],
            ],
        ], $overrides);
    }

    public function test_owner_can_update_trip_and_catches_are_replaced(): void
    {
        $this->actingAs($this->owner)->put("/trips/{$this->trip->id}", $this->input())
            ->assertRedirect(route('trips.show', $this->trip));

        $this->trip->refresh();
        // 日時を 1月11日（新月）に直したので、潮も大潮に計算し直されている
        $this->assertSame('大潮', $this->trip->tide);
        // 釣果は入れ直されて、サバ2匹だけになっている（元のアジは残っていない）
        $this->assertSame(2, $this->trip->catches()->count());
        $this->assertSame(0, $this->trip->catches()->where('fish_species', 'アジ')->count());
    }

    public function test_others_cannot_open_edit_or_update(): void
    {
        $this->actingAs($this->other)->get("/trips/{$this->trip->id}/edit")->assertNotFound();
        $this->actingAs($this->other)->put("/trips/{$this->trip->id}", $this->input())->assertNotFound();

        // 何も変わっていない
        $this->assertSame('小潮', $this->trip->refresh()->tide);
        $this->assertSame(1, $this->trip->catches()->count());
    }

    public function test_owner_can_delete_trip_with_its_catches(): void
    {
        $this->actingAs($this->owner)->delete("/trips/{$this->trip->id}")
            ->assertRedirect(route('trips.index'));

        $this->assertSame(0, Trip::count());
        $this->assertSame(0, FishCatch::count());
    }

    public function test_others_cannot_delete(): void
    {
        $this->actingAs($this->other)->delete("/trips/{$this->trip->id}")->assertNotFound();

        $this->assertSame(1, Trip::count());
    }

    public function test_current_spot_can_be_kept_even_after_it_became_private(): void
    {
        // 友だちの公開の釣り場で記録したあと、友だちが釣り場を非公開にした
        $friendsSpot = Spot::factory()->create(['visibility' => 'public', 'created_by' => $this->other->id]);
        $this->trip->update(['spot_id' => $friendsSpot->id]);
        $friendsSpot->update(['visibility' => 'private']);

        // 今の釣り場のままなら、編集画面も開けて、保存もできる
        $this->actingAs($this->owner)->get("/trips/{$this->trip->id}/edit")->assertOk();
        $this->actingAs($this->owner)->put("/trips/{$this->trip->id}", $this->input(['spot_id' => $friendsSpot->id]))
            ->assertSessionHasNoErrors();

        // でも、ほかの人の非公開の釣り場に「新しく」付け替えることはできない
        $anotherSecret = Spot::factory()->create(['visibility' => 'private', 'created_by' => $this->other->id]);
        $this->actingAs($this->owner)->put("/trips/{$this->trip->id}", $this->input(['spot_id' => $anotherSecret->id]))
            ->assertSessionHasErrors('spot_id');
    }
}
