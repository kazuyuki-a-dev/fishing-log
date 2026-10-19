<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 起動時の処理が、複数のインスタンスで同時に走らないようにする（#172）
 */
class StartupLockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // テストでは長く待たない
        config(['fishing.startup_lock_wait' => 1]);
    }

    public function test_seed_waits_while_another_instance_is_working(): void
    {
        // ほかのインスタンスがお試しデータの作業中（ロックを持っている）
        $other = Cache::lock('demo-data', 900);
        $this->assertTrue($other->get());

        // 終わるのを待つが、終わらなければ何もしないで止まる（2回入れない）
        try {
            $this->artisan('app:seed-if-empty')->run();
            $this->fail('ロックを待たずに動いてしまった');
        } catch (LockTimeoutException) {
            $this->assertSame(0, User::count());
        } finally {
            $other->release();
        }
    }

    public function test_seed_releases_the_lock_when_done(): void
    {
        $this->artisan('app:seed-if-empty')->assertSuccessful();

        // 終わったらロックを返している（次の起動で待たされない）
        $this->assertGreaterThan(0, User::count());
        $this->assertTrue(Cache::lock('demo-data', 1)->get());
        $this->assertTrue(Cache::lock('sample-photos', 1)->get());
    }

    public function test_reset_also_waits_for_the_same_lock(): void
    {
        config(['fishing.demo_reseed_token' => 'reseed-1']);
        $other = Cache::lock('demo-data', 900);
        $other->get();
        $someone = User::factory()->create();

        try {
            $this->artisan('app:reset-demo-if-requested')->run();
            $this->fail('ロックを待たずに動いてしまった');
        } catch (LockTimeoutException) {
            // 作業中のあいだは、データを消さない
            $this->assertNotNull($someone->fresh());
        } finally {
            $other->release();
        }
    }

    public function test_migrate_runs_isolated_on_startup(): void
    {
        // 公開環境の起動の手順で、マイグレーションは同時に1つだけ動く
        $this->assertStringContainsString('php artisan migrate --force --isolated', file_get_contents(base_path('docker/start.sh')));
    }
}
