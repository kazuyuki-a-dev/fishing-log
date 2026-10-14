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
     * 保存した場所（保存先の中のパス）を返す
     * 保存先は config('fishing.photo_disk')。開発はフォルダ、公開環境は S3 の形の保存先（#136）
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
        // いったん JPEG の中身（文字列）にしてから、保存先に置く。フォルダでも S3 の形の保存先でも同じ書き方で済む
        // 「公開」の指定はしない。Cloudflare R2 はファイルごとの公開の指定を受け付けず、保存が失敗するため（公開はバケットの設定で行う。#140）
        // 開発のフォルダ（public）は、config/filesystems.php の設定でもともと公開になっている
        $jpeg = $image->encodeUsingFormat(Format::JPEG, quality: 85)->toString();
        Storage::disk(self::disk())->put($path, $jpeg);

        return $path;
    }

    /**
     * 使わなくなった写真のファイルを消す
     */
    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::disk())->delete($path);
        }
    }

    /**
     * 写真を保存する場所の名前（config/filesystems.php の disks のどれか）
     */
    public static function disk(): string
    {
        return config('fishing.photo_disk');
    }
}
