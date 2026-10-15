<?php

namespace Tests\Feature;

use App\Console\Commands\AttachSamplePhotos;
use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * お試しデータの釣果に、サンプルの魚のイラストを付ける（#148）
 */
class SamplePhotosTest extends TestCase
{
    use RefreshDatabase;

    /** 釣行1件に釣果1匹 */
    private function catchOn(string $visibility, string $fish = 'アジ', string $spotVisibility = 'public', ?string $wentAt = null): FishCatch
    {
        $user = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $user->id, 'visibility' => $spotVisibility]);
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'spot_id' => $spot->id,
            'visibility' => $visibility,
            ...($wentAt ? ['went_at' => $wentAt] : []),
        ]);

        return FishCatch::factory()->for($trip)->create(['fish_species' => $fish, 'image_path' => null]);
    }

    public function test_illustration_is_attached_only_to_public_catches(): void
    {
        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');
        $public = $this->catchOn('public', 'アジ');
        $hidden = $this->catchOn('spot_hidden', 'アジ');
        $private = $this->catchOn('private', 'アジ');
        $privateSpot = $this->catchOn('public', 'アジ', 'private');   // 釣り場が非公開なら「釣り場だけ隠す」の扱い
        $other = $this->catchOn('public', 'マダイ');                   // イラストがない魚

        $this->artisan('app:attach-sample-photos')->assertSuccessful();

        $path = $public->fresh()->image_path;
        $this->assertStringStartsWith(AttachSamplePhotos::PREFIX, $path);
        $disk->assertExists($path);
        $this->assertSame(file_get_contents(database_path('seeders/data/sample-photos/aji.jpg')), $disk->get($path));
        foreach ([$hidden, $private, $privateSpot, $other] as $catch) {
            $this->assertNull($catch->fresh()->image_path);
        }
    }

    public function test_each_catch_gets_its_own_file_and_limits_are_kept(): void
    {
        // アジを12件。同じ魚は2件まで → アジは2件だけ。ほかの魚も足して、全部で10件まで
        for ($i = 0; $i < 12; $i++) {
            $this->catchOn('public', 'アジ');
        }
        foreach (array_keys(AttachSamplePhotos::PHOTOS) as $fish) {
            $this->catchOn('public', $fish);
            $this->catchOn('public', $fish);
        }

        $this->artisan('app:attach-sample-photos')->assertSuccessful();

        $samples = FishCatch::whereNotNull('image_path')->get();
        $this->assertCount(10, $samples);
        $this->assertSame(2, $samples->where('fish_species', 'アジ')->count());
        // 釣果ごとに別のファイル（1件削除しても、ほかの絵は消えない）
        $this->assertCount(10, $samples->pluck('image_path')->unique());
    }

    public function test_only_one_illustration_per_trip(): void
    {
        $catch = $this->catchOn('public', 'アジ');
        FishCatch::factory()->for($catch->trip)->create(['fish_species' => 'サバ', 'image_path' => null]);

        $this->artisan('app:attach-sample-photos')->assertSuccessful();

        $this->assertSame(1, FishCatch::whereNotNull('image_path')->count());
    }

    public function test_newer_trips_come_first(): void
    {
        $old = $this->catchOn('public', 'アジ', wentAt: '2025-01-01 06:00:00');
        $new = $this->catchOn('public', 'アジ', wentAt: '2026-09-01 06:00:00');
        $newer = $this->catchOn('public', 'アジ', wentAt: '2026-10-01 06:00:00');

        $this->artisan('app:attach-sample-photos')->assertSuccessful();

        // アジは2件まで。新しい2件に付く
        $this->assertNotNull($newer->fresh()->image_path);
        $this->assertNotNull($new->fresh()->image_path);
        $this->assertNull($old->fresh()->image_path);
    }

    public function test_does_nothing_when_illustrations_are_already_attached(): void
    {
        // 公開環境では起動のたびに動かすので、2回目は何もしない
        $first = $this->catchOn('public', 'アジ');
        $this->artisan('app:attach-sample-photos')->assertSuccessful();
        $second = $this->catchOn('public', 'サバ');

        $this->artisan('app:attach-sample-photos')
            ->expectsOutputToContain('もう付いています')
            ->assertSuccessful();

        $this->assertNotNull($first->fresh()->image_path);
        $this->assertNull($second->fresh()->image_path);
    }

    public function test_illustration_goes_to_the_configured_disk(): void
    {
        // 公開環境の写真の保存先（PHOTO_DISK=s3、Cloudflare R2）にも置ける
        config(['fishing.photo_disk' => 's3']);
        /** @var \Illuminate\Filesystem\FilesystemAdapter $s3 */
        $s3 = Storage::fake('s3');
        $catch = $this->catchOn('public', 'クロダイ');

        $this->artisan('app:attach-sample-photos')->assertSuccessful();

        $s3->assertExists($catch->fresh()->image_path);
        Storage::disk('public')->assertMissing($catch->fresh()->image_path);
    }

    public function test_seeder_attaches_illustrations(): void
    {
        $this->seed();

        $samples = FishCatch::where('image_path', 'like', AttachSamplePhotos::PREFIX . '%')->with('trip.spot')->get();
        $this->assertGreaterThan(0, $samples->count());
        $this->assertLessThanOrEqual(10, $samples->count());
        $this->assertTrue($samples->every(fn($catch) => $catch->trip->effectiveVisibility() === 'public'));
    }
}
