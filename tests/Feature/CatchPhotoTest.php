<?php

namespace Tests\Feature;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Filesystem\FilesystemAdapter;

class CatchPhotoTest extends TestCase
{
    use RefreshDatabase;

    private FilesystemAdapter $disk;

    protected function setUp(): void
    {
        parent::setUp();
        // 本物の保存場所を汚さないように、テスト用の一時的な保存場所を使う
        Storage::fake('public');
        $this->disk = Storage::disk('public');
    }

    /**
     * Exif（「FishingLogSecret」という文字）を埋め込んだ JPEG を作る
     */
    private function jpegWithExif(int $width = 40, int $height = 30): UploadedFile
    {
        $image = imagecreatetruecolor($width, $height);
        ob_start();
        imagejpeg($image);
        $jpeg = ob_get_clean();

        // Exif の中身：画像の説明（ImageDescription）に秘密の文字を入れる
        $secret = "FishingLogSecret\0";
        $tiff = "II*\0" . pack('V', 8)                       // 並び方の宣言と、最初の表の場所
            . pack('v', 1)                                     // 表の項目は1つ
            . pack('vvVV', 0x010E, 2, strlen($secret), 26)     // 項目：画像の説明（文字）、26 バイト目から
            . pack('V', 0)                                     // 次の表はない
            . $secret;
        $payload = "Exif\0\0" . $tiff;
        $exifBlock = "\xFF\xE1" . pack('n', strlen($payload) + 2) . $payload;

        // JPEG の先頭（FF D8）のすぐ後ろに、Exif のかたまりを差し込む
        $path = tempnam(sys_get_temp_dir(), 'fish') . '.jpg';
        file_put_contents($path, substr($jpeg, 0, 2) . $exifBlock . substr($jpeg, 2));

        return new UploadedFile($path, 'IMG_0001.jpg', 'image/jpeg', null, true);
    }

    private function tripPayload(Spot $spot, array $catches): array
    {
        return [
            'spot_id' => $spot->id,
            'went_at' => '2026-09-20T06:00',
            'time_of_day' => '朝マズメ',
            'visibility' => 'private',
            'catches' => $catches,
        ];
    }

    public function test_exif_is_removed_from_the_saved_photo(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $me->id]);
        $photo = $this->jpegWithExif();

        // 作った写真に、本当に秘密の文字が入っていることを先に確かめる
        $this->assertStringContainsString('FishingLogSecret', file_get_contents($photo->getRealPath()));

        $this->actingAs($me)->post('/trips', $this->tripPayload($spot, [
            ['fish_species' => 'アジ', 'method' => 'エサ', 'photo' => $photo],
        ]));

        $catch = FishCatch::firstOrFail();
        $this->disk->assertExists($catch->image_path);

        $saved = $this->disk->get($catch->image_path);
        $this->assertStringNotContainsString('FishingLogSecret', $saved);
        $this->assertStringNotContainsString('Exif', $saved);
        $this->assertStringNotContainsString('IMG_0001', $catch->image_path);   // 元のファイル名も使わない
    }

    public function test_large_photo_is_scaled_down_to_1600px(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $me->id]);

        $this->actingAs($me)->post('/trips', $this->tripPayload($spot, [
            ['fish_species' => 'アジ', 'method' => 'エサ', 'photo' => $this->jpegWithExif(3200, 2400)],
        ]));

        [$width, $height] = getimagesizefromstring($this->disk->get(FishCatch::firstOrFail()->image_path));
        $this->assertSame(1600, $width);
        $this->assertSame(1200, $height);
    }

    public function test_non_image_file_is_rejected(): void
    {
        $me = User::factory()->create();
        $spot = Spot::factory()->create(['created_by' => $me->id]);

        $this->actingAs($me)->post('/trips', $this->tripPayload($spot, [
            ['fish_species' => 'アジ', 'method' => 'エサ', 'photo' => UploadedFile::fake()->create('memo.pdf', 100, 'application/pdf')],
        ]))->assertSessionHasErrors('catches.0.photo');

        $this->assertSame(0, FishCatch::count());
    }

    /** 写真つきの釣果が1匹ある、自分の釣行 */
    private function tripWithPhoto(User $user, string $path): Trip
    {
        $trip = Trip::factory()->create([
            'user_id' => $user->id,
            'spot_id' => Spot::factory()->create(['created_by' => $user->id])->id,
        ]);
        FishCatch::factory()->create(['trip_id' => $trip->id, 'image_path' => $path]);
        $this->disk->put($path, 'photo');

        return $trip;
    }

    public function test_current_photo_can_be_kept_when_editing(): void
    {
        $me = User::factory()->create();
        $trip = $this->tripWithPhoto($me, 'catches/mine.jpg');

        $this->actingAs($me)->put("/trips/{$trip->id}", $this->tripPayload($trip->spot, [
            ['fish_species' => 'アジ', 'method' => 'エサ', 'keep_photo' => 'catches/mine.jpg'],
        ]));

        $this->assertSame('catches/mine.jpg', $trip->catches()->first()->image_path);
        $this->disk->assertExists('catches/mine.jpg');
    }

    public function test_others_photo_cannot_be_taken_over(): void
    {
        $me = User::factory()->create();
        $mine = $this->tripWithPhoto($me, 'catches/mine.jpg');
        $this->tripWithPhoto(User::factory()->create(), 'catches/others.jpg');

        // 開発者ツールで、ほかの人の写真の場所に書き換えて送っても
        $this->actingAs($me)->put("/trips/{$mine->id}", $this->tripPayload($mine->spot, [
            ['fish_species' => 'アジ', 'method' => 'エサ', 'keep_photo' => 'catches/others.jpg'],
        ]));

        $this->assertNull($mine->catches()->first()->image_path);
        $this->disk->assertExists('catches/others.jpg');   // ほかの人の写真は消えない
    }

    public function test_removed_photo_file_is_deleted(): void
    {
        $me = User::factory()->create();
        $trip = $this->tripWithPhoto($me, 'catches/mine.jpg');

        // 「この写真を外す」を押して更新
        $this->actingAs($me)->put("/trips/{$trip->id}", $this->tripPayload($trip->spot, [
            ['fish_species' => 'アジ', 'method' => 'エサ', 'keep_photo' => ''],
        ]));

        $this->assertNull($trip->catches()->first()->image_path);
        $this->disk->assertMissing('catches/mine.jpg');
    }

    public function test_photo_files_are_deleted_with_the_trip(): void
    {
        $me = User::factory()->create();
        $trip = $this->tripWithPhoto($me, 'catches/mine.jpg');

        $this->actingAs($me)->delete("/trips/{$trip->id}");

        $this->disk->assertMissing('catches/mine.jpg');
    }
}
