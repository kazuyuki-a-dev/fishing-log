<?php

namespace App\Console\Commands;

use App\Services\PhotoStorer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 合図があったときだけ、お試しデータを消して入れ直す（#160）
 * 公開環境のデータは全部お試しデータ（ダミー）なので、シーダーを直したあとに入れ直せるようにする
 * 合図は config/fishing.php の demo_reseed_token（環境変数 DEMO_RESEED_TOKEN）。
 * 入れ直した合図を覚えておくので、環境変数を入れたままにしても、再起動のたびに消えることはない
 */
#[Signature('app:reset-demo-if-requested')]
#[Description('合図があったときだけ、お試しデータと写真を消して入れ直す')]
class ResetDemoIfRequested extends Command
{
    // 入れ直した合図を覚えておくキャッシュの名前
    public const CACHE_KEY = 'demo_reseed_token';

    // 中身を消す表。ほかの表から参照されているもの（users・spots）は最後に消す
    private const TABLES = ['reports', 'notifications', 'catches', 'trips', 'spots', 'sessions', 'password_reset_tokens', 'users'];

    public function handle(): int
    {
        $token = (string) config('fishing.demo_reseed_token');
        if ($token === '') {
            return self::SUCCESS;
        }

        // 複数のインスタンスが同時に起動しても、入れ直しが2回走らないように、ロックを取ってから確かめる（#172）
        // app:seed-if-empty と同じロック。ほかが作業中なら、終わるまで待つ
        return Cache::lock('demo-data', 900)->block(config('fishing.startup_lock_wait'), fn() => $this->reset($token));
    }

    private function reset(string $token): int
    {
        if (Cache::get(self::CACHE_KEY) === $token) {
            $this->info('この合図では、もう入れ直しています。');

            return self::SUCCESS;
        }

        // 写真のファイル（R2）を消す。データベースの行を消す前に、場所が分かっているうちに消す
        Storage::disk(PhotoStorer::disk())->deleteDirectory('catches');

        // 表の中身を消す（表そのものは残す）
        foreach (self::TABLES as $table) {
            DB::table($table)->delete();
        }

        // シーダーでお試しデータを入れ直す（サンプルのイラストもシーダーの最後で付く）
        $this->call('db:seed', ['--force' => true]);

        Cache::forever(self::CACHE_KEY, $token);
        $this->info('お試しデータを入れ直しました。');

        return self::SUCCESS;
    }
}
