<?php

namespace App\Console\Commands;

use App\Models\FishCatch;
use App\Services\PhotoStorer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * お試しデータの釣果に、サンプルの魚のイラストを付ける（#148）
 * イラストは自分で描いたもの（database/seeders/data/sample-photos。絵の中に「サンプル」のスタンプ入り）
 * 公開環境では起動のたびに動かすので、もう付いているときは何もしない
 */
#[Signature('app:attach-sample-photos')]
#[Description('全体公開の釣果のいくつかに、サンプルの魚のイラストを付ける')]
class AttachSamplePhotos extends Command
{
    // 魚の種類 → イラストのファイル名（シーダーのダミーの釣行で釣れる8種類）
    public const PHOTOS = [
        'アジ' => 'aji',
        'サバ' => 'saba',
        'イワシ' => 'iwashi',
        'カサゴ' => 'kasago',
        'メバル' => 'mebaru',
        'クロダイ' => 'kurodai',
        'シーバス' => 'seabass',
        'アイナメ' => 'ainame',
    ];

    // 付ける数と、同じ魚に付ける数の上限（いろいろな魚が並ぶように）。1つの釣行には1枚だけ（同じ絵が並ばないように）
    private const LIMIT = 10;
    private const PER_SPECIES = 2;

    // サンプルの写真のファイル名の頭（付いているかを見分けるため）
    public const PREFIX = 'catches/sample-';

    public function handle(): int
    {
        // 複数のインスタンスが同時に起動しても2回付けないように、ロックを取ってから確かめる（#172）
        // シーダーの中からも呼ばれるので、お試しデータのロック（demo-data）とは別の名前にする
        return Cache::lock('sample-photos', 300)->block(config('fishing.startup_lock_wait'), fn() => $this->attach());
    }

    private function attach(): int
    {
        if (FishCatch::where('image_path', 'like', self::PREFIX . '%')->exists()) {
            $this->info('サンプルのイラストは、もう付いています。');

            return self::SUCCESS;
        }

        // 全体公開の釣行（釣り場も公開）の、写真がない釣果。新しい順（フィードの上のほうに出るように）
        $catches = FishCatch::query()
            ->whereNull('catches.image_path')
            ->whereIn('catches.fish_species', array_keys(self::PHOTOS))
            ->whereHas('trip', fn($trip) => $trip->where('visibility', 'public')
                ->whereHas('spot', fn($spot) => $spot->where('visibility', 'public')))
            ->join('trips', 'trips.id', '=', 'catches.trip_id')
            ->orderByDesc('trips.went_at')
            ->orderBy('catches.id')
            ->select('catches.*')
            ->get();

        $disk = Storage::disk(PhotoStorer::disk());
        $counts = [];
        $usedTrips = [];
        $attached = 0;
        foreach ($catches as $catch) {
            if ($attached >= self::LIMIT) {
                break;
            }
            $species = $catch->fish_species;
            if (($counts[$species] ?? 0) >= self::PER_SPECIES || isset($usedTrips[$catch->trip_id])) {
                continue;
            }

            // 釣果ごとに別のファイルにする（1件削除しても、ほかの釣果の絵は消えない）
            $path = self::PREFIX . Str::uuid() . '.jpg';
            $disk->put($path, file_get_contents(database_path('seeders/data/sample-photos/' . self::PHOTOS[$species] . '.jpg')));
            $catch->update(['image_path' => $path]);

            $counts[$species] = ($counts[$species] ?? 0) + 1;
            $usedTrips[$catch->trip_id] = true;
            $attached++;
        }

        $this->info("サンプルのイラストを {$attached} 件の釣果に付けました。");

        return self::SUCCESS;
    }
}
