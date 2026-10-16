<?php

namespace Tests\Feature;

use App\Console\Commands\ResetDemoIfRequested;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 合図があったときだけ、お試しデータを入れ直す（#160）
 */
class ResetDemoTest extends TestCase
{
    use RefreshDatabase;

    public function test_does_nothing_without_a_token(): void
    {
        config(['fishing.demo_reseed_token' => null]);
        $someone = User::factory()->create(['email' => 'someone@example.com']);

        $this->artisan('app:reset-demo-if-requested')->assertSuccessful();

        $this->assertNotNull($someone->fresh());
        $this->assertNull(User::where('email', 'test@example.com')->first());
    }

    public function test_new_token_resets_data_and_photos(): void
    {
        config(['fishing.demo_reseed_token' => 'reseed-1']);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $disk->put('catches/old.jpg', 'photo');
        $someone = User::factory()->create(['email' => 'someone@example.com']);

        $this->artisan('app:reset-demo-if-requested')
            ->expectsOutputToContain('入れ直しました')
            ->assertSuccessful();

        // 前のデータと写真は消え、お試しデータが入っている
        $this->assertNull($someone->fresh());
        $disk->assertMissing('catches/old.jpg');
        $this->assertNotNull(User::where('email', 'test@example.com')->first());
        $this->assertSame('reseed-1', Cache::get(ResetDemoIfRequested::CACHE_KEY));
    }

    public function test_same_token_resets_only_once(): void
    {
        // 環境変数を入れたままでも、再起動のたびに消えない
        config(['fishing.demo_reseed_token' => 'reseed-1']);
        Cache::forever(ResetDemoIfRequested::CACHE_KEY, 'reseed-1');
        $someone = User::factory()->create(['email' => 'someone@example.com']);

        $this->artisan('app:reset-demo-if-requested')
            ->expectsOutputToContain('もう入れ直しています')
            ->assertSuccessful();

        $this->assertNotNull($someone->fresh());
    }
}
