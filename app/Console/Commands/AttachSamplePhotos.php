<?php

namespace App\Console\Commands;

use App\Models\FishCatch;
use App\Models\User;
use App\Services\PersonalBests;
use App\Services\PhotoStorer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * お試しデータの釣果に、サンプルの魚のイラストを付ける（#148）
 * イラストは自分で描いたもの（database/seeders/data/sample-photos。絵の中に「サンプル」のスタンプ入り）
 * 手順は2つで、それぞれ別に確かめる。公開環境では起動のたびに動かすので、もう付いているときは付け直さない
 * 1. 全体公開の釣果の10件まで
 * 2. お試し用アカウントの魚種ごとの自己ベスト（自己ベストのページを写真つきで見せるため。#176）
 */
#[Signature('app:attach-sample-photos')]
#[Description('全体公開の釣果のいくつかと、お試し用アカウントの自己ベストに、サンプルの魚のイラストを付ける')]
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

    // 自己ベストのうち、この魚にはイラストを付けない（写真がないときの見え方＝魚のアイコンの例として残す。#176）
    public const NO_PHOTO_EXAMPLE = 'アイナメ';

    // お試し用アカウント（公開環境は demo@、開発環境は test@）
    private const DEV_MAIN_EMAIL = 'test@example.com';

    public function handle(): int
    {
        // 複数のインスタンスが同時に起動しても2回付けないように、ロックを取ってから確かめる（#172）
        // シーダーの中からも呼ばれるので、お試しデータのロック（demo-data）とは別の名前にする
        return Cache::lock('sample-photos', 300)->block(config('fishing.startup_lock_wait'), function () {
            $this->attachToPublicCatches();
            $this->attachToPersonalBests();

            return self::SUCCESS;
        });
    }

    /**
     * 1. 全体公開の釣果の10件まで（#148）。サンプルのイラストが1枚でもあれば、もう付けたので何もしない
     */
    private function attachToPublicCatches(): void
    {
        if (FishCatch::where('image_path', 'like', self::PREFIX . '%')->exists()) {
            $this->info('サンプルのイラストは、もう付いています。');

            return;
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

            $this->attachTo($catch);

            $counts[$species] = ($counts[$species] ?? 0) + 1;
            $usedTrips[$catch->trip_id] = true;
            $attached++;
        }

        $this->info("サンプルのイラストを {$attached} 件の釣果に付けました。");
    }

    /**
     * 2. お試し用アカウントの魚種ごとの自己ベストのうち、写真がない釣果に付ける（#176）
     * アイナメは、写真がないときの見え方の例として付けない。写真がある自己ベストには付けないので、何度動かしても2回付けない
     */
    private function attachToPersonalBests(): void
    {
        $users = User::whereIn('email', [config('fishing.demo_email'), self::DEV_MAIN_EMAIL])->get();
        $attached = 0;
        foreach ($users as $user) {
            foreach (app(PersonalBests::class)->forUser($user) as $best) {
                if ($best->image_path !== null || $best->fish_species === self::NO_PHOTO_EXAMPLE || ! isset(self::PHOTOS[$best->fish_species])) {
                    continue;
                }
                // fromSub で取り出した釣果なので、更新は番号で取り出し直してから行う
                $this->attachTo(FishCatch::findOrFail($best->id));
                $attached++;
            }
        }

        if ($attached > 0) {
            $this->info("自己ベストの {$attached} 件の釣果に、サンプルのイラストを付けました。");
        }
    }

    /**
     * 釣果1件に、その魚のイラストを付ける。釣果ごとに別のファイルにする（1件削除しても、ほかの釣果の絵は消えない）
     */
    private function attachTo(FishCatch $catch): void
    {
        $path = self::PREFIX . Str::uuid() . '.jpg';
        $saved = Storage::disk(PhotoStorer::disk())->put($path, file_get_contents(database_path('seeders/data/sample-photos/' . self::PHOTOS[$catch->fish_species] . '.jpg')));
        // 保存に失敗しても put はエラーを出さず false を返すだけなので、ここで止める（ファイルがないのに場所だけ入らないように。#176）
        if (! $saved) {
            throw new RuntimeException("サンプルのイラストを保存できませんでした（{$path}）。保存先のフォルダの書き込みの権限を確かめてください。");
        }
        $catch->update(['image_path' => $path]);
    }
}
