<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_entry_links_and_footer(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('会員登録（無料）')
            ->assertSee(route('login'))
            ->assertSee(route('terms'))
            ->assertSee(route('privacy'));
    }

    public function test_member_is_redirected_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_only_public_and_spot_hidden_trips_are_in_the_latest(): void
    {
        $spot = Spot::factory()->create(['name' => 'ひみつの岩場', 'visibility' => 'public']);

        foreach (['public' => '公開さん', 'spot_hidden' => '隠すさん', 'private' => '非公開さん'] as $visibility => $name) {
            $user = User::factory()->create(['name' => $name]);
            Trip::factory()->create(['user_id' => $user->id, 'spot_id' => $spot->id, 'visibility' => $visibility]);
        }

        $this->get('/')
            ->assertSee('公開さん')
            ->assertSee('隠すさん')
            ->assertDontSee('非公開さん');
    }

    public function test_spot_hidden_trip_does_not_contain_spot_name(): void
    {
        $spot = Spot::factory()->create(['name' => 'ひみつの岩場', 'visibility' => 'public']);
        Trip::factory()->create(['spot_id' => $spot->id, 'visibility' => 'spot_hidden']);

        $this->get('/')
            ->assertSee('釣り場は非公開')
            ->assertDontSee('ひみつの岩場');
    }

    public function test_only_the_latest_five_trips_are_shown(): void
    {
        $spot = Spot::factory()->create(['visibility' => 'public']);

        // 1日目が一番古い。6件のうち、一番古い1件だけが出ないはず
        foreach (range(1, 6) as $day) {
            $user = User::factory()->create(['name' => "{$day}日目さん"]);
            Trip::factory()->create([
                'user_id' => $user->id,
                'spot_id' => $spot->id,
                'visibility' => 'public',
                'went_at' => "2026-09-0{$day} 06:00:00",
            ]);
        }

        $this->get('/')
            ->assertSee('6日目さん')
            ->assertSee('2日目さん')
            ->assertDontSee('1日目さん');
    }

    public function test_terms_and_privacy_pages_have_the_menu(): void
    {
        $this->get('/terms')->assertOk()->assertSee('会員登録');
        $this->get('/privacy')->assertOk()->assertSee('会員登録');
    }
}
