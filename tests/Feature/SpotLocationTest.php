<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotLocationTest extends TestCase
{
    use RefreshDatabase;

    private function spotAt(User $owner): Spot
    {
        return Spot::factory()->create([
            'created_by' => $owner->id,
            'visibility' => 'public',
            'latitude' => 39.7234567,
            'longitude' => 140.0987654,
        ]);
    }

    public function test_owner_sees_the_exact_location(): void
    {
        $owner = User::factory()->create();
        $spot = $this->spotAt($owner);

        $this->actingAs($owner)->get("/spots/{$spot->id}")
            ->assertSee('39.7234567')
            ->assertSee('140.0987654');
    }

    public function test_others_see_only_the_rounded_location(): void
    {
        $spot = $this->spotAt(User::factory()->create());

        $this->actingAs(User::factory()->create())->get("/spots/{$spot->id}")
            ->assertSee('39.72')
            ->assertSee('140.09')
            ->assertDontSee('39.7234567')
            ->assertDontSee('140.0987654');
    }

    public function test_guests_see_only_the_rounded_location(): void
    {
        $spot = $this->spotAt(User::factory()->create());

        $this->get("/spots/{$spot->id}")
            ->assertSee('39.72')
            ->assertSee('140.09')
            ->assertDontSee('39.7234567')
            ->assertDontSee('140.0987654');
    }

    public function test_location_is_cut_down_not_rounded_up(): void
    {
        $spot = new Spot(['latitude' => 39.7299999, 'longitude' => 140.0999999]);
        $spot->created_by = null;

        $location = $spot->locationFor(null);

        // 四捨五入なら 39.73 になるが、切り捨てなので 39.72
        $this->assertSame(39.72, $location['lat']);
        $this->assertSame(140.09, $location['lng']);
        $this->assertFalse($location['exact']);
    }

    public function test_owner_can_set_and_clear_the_location(): void
    {
        $owner = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $owner->id]);
        $basic = ['name' => $spot->name, 'prefecture' => $spot->prefecture, 'visibility' => $spot->visibility];

        $this->actingAs($owner)->put("/spots/{$spot->id}", $basic + ['latitude' => '39.7234567', 'longitude' => '140.0987654']);
        $this->assertEquals(39.7234567, (float) $spot->fresh()->latitude);

        // 「位置を消す」で空にして保存すると、位置なしに戻る
        $this->actingAs($owner)->put("/spots/{$spot->id}", $basic + ['latitude' => '', 'longitude' => '']);
        $this->assertNull($spot->fresh()->latitude);
    }

    public function test_others_cannot_change_the_location(): void
    {
        $owner = User::factory()->create();
        $spot = $this->spotAt($owner);

        // 本人以外が、開発者ツールなどで位置を送りつけても変わらない
        $this->actingAs(User::factory()->create())->put("/spots/{$spot->id}", [
            'latitude' => '35.0000000',
            'longitude' => '135.0000000',
        ]);

        $this->assertEquals(39.7234567, (float) $spot->fresh()->latitude);
    }

    public function test_latitude_alone_is_rejected(): void
    {
        $owner = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $owner->id]);

        $this->actingAs($owner)->put("/spots/{$spot->id}", [
            'name' => $spot->name,
            'prefecture' => $spot->prefecture,
            'visibility' => $spot->visibility,
            'latitude' => '39.7234567',
        ])->assertSessionHasErrors('longitude');
    }
}
