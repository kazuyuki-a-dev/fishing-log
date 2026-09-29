<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class PhotoStorer
{
    /**
     * 写真を、Exif（撮影日時・位置など）を消して保存する（NF-01・FN-08）
     * 保存した場所（storage/app/public から見たパス）を返す
     */
    public function store(UploadedFile $file): string
    {
        // strip: true で、画像の中の情報（Exif など）を書き出さない
        $manager = ImageManager::usingDriver(GdDriver::class, strip: true);

        // 読み込むときに、Exif の「向き」の情報を使って、正しい向きに回してくれる
        $image = $manager->decodePath($file->getRealPath());

        // 長い辺が 1600px より大きければ縮める（小さい写真は大きくしない）
        $image->scaleDown(width: 1600, height: 1600);

        $path = 'catches/' . Str::uuid() . '.jpg';
        Storage::disk('public')->makeDirectory('catches');
        $image->encodeUsingFormat(Format::JPEG, quality: 85)
            ->save(Storage::disk('public')->path($path));

        return $path;
    }

    /**
     * 使わなくなった写真のファイルを消す
     */
    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
