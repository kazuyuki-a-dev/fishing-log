<?php

namespace Tests\Feature;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 負荷を確かめる道具（#166。開発環境だけで使う）
 */
class LoadTestToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_trips_to_the_spot(): void
    {
        $spot = Spot::factory()->create(['name' => '負荷の港', 'prefecture' => '秋田県']);
        User::factory()->create(['home_prefecture' => '秋田県']);

        $this->artisan('app:add-load-test-trips', ['count' => 50, '--spot' => '負荷の港'])->assertSuccessful();

        $this->assertSame(50, Trip::where('spot_id', $spot->id)->count());
        // 潮は日時から計算して入れてある
        $this->assertSame(0, Trip::whereNull('tide')->count());
    }

    public function test_cannot_be_used_in_production(): void
    {
        $this->app['env'] = 'production';
        Spot::factory()->create(['name' => '負荷の港']);

        $this->artisan('app:add-load-test-trips', ['count' => 10, '--spot' => '負荷の港'])->assertFailed();
        $this->artisan('app:measure-pages', ['email' => 'a@example.com', 'paths' => ['/']])->assertFailed();

        $this->assertSame(0, Trip::count());
    }

    public function test_measures_pages(): void
    {
        $me = User::factory()->create(['email' => 'measure@example.com']);

        $this->artisan('app:measure-pages', ['email' => 'measure@example.com', 'paths' => ['/dashboard']])
            ->expectsOutputToContain('/dashboard')
            ->assertSuccessful();
    }
}
