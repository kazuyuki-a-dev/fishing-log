<?php

namespace App\Console\Commands;

use App\Models\Spot;
use App\Models\User;
use App\Services\TideCalculator;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * 負荷を確かめるために、大量のダミーの釣行を1つの釣り場に足す（開発環境だけ。#166）
 * 1件ずつモデルで作ると遅いので、まとめて INSERT する
 * 元に戻すときは、バックアップから戻すか、app:reset-demo-if-requested で入れ直す
 * 例：sail artisan app:add-load-test-trips 30000
 */
#[Signature('app:add-load-test-trips {count=30000 : 足す釣行の数} {--spot=秋田港 : 足す釣り場の名前}')]
#[Description('負荷の確かめ用に、大量のダミーの釣行を足す（開発環境だけ）')]
class AddLoadTestTrips extends Command
{
    // ダミーで使う魚（シーダーと同じ、港でよく釣れる魚）
    private const FISH = ['アジ', 'サバ', 'イワシ', 'カサゴ', 'メバル', 'クロダイ', 'シーバス', 'アイナメ'];

    public function handle(TideCalculator $tides): int
    {
        if (app()->environment('production')) {
            $this->error('公開環境では使えません。');

            return self::FAILURE;
        }

        $spot = Spot::where('name', $this->option('spot'))->first();
        if (! $spot) {
            $this->error('その名前の釣り場が見つかりませんでした。');

            return self::FAILURE;
        }

        // 釣行の持ち主：その県の人たち（自分の記録も、ほかの人の記録も増える）
        $userIds = User::where('home_prefecture', $spot->prefecture)->pluck('id')->all();
        if ($userIds === []) {
            $this->error('その県の人がいません。先にシーダーを動かしてください。');

            return self::FAILURE;
        }

        $count = (int) $this->argument('count');
        $now = now();
        $bar = $this->output->createProgressBar($count);

        // 1000件ずつ、釣行を入れて、その釣行の釣果を入れる
        foreach (array_chunk(range(1, $count), 1000) as $chunk) {
            DB::transaction(function () use ($chunk, $spot, $userIds, $tides, $now) {
                $trips = [];
                foreach ($chunk as $_) {
                    $wentAt = Carbon::now('Asia/Tokyo')->subMinutes(random_int(60, 60 * 24 * 365 * 3));
                    $trips[] = [
                        'user_id' => $userIds[array_rand($userIds)],
                        'spot_id' => $spot->id,
                        'went_at' => $wentAt->format('Y-m-d H:i:s'),
                        'time_of_day' => config('fishing.times_of_day')[array_rand(config('fishing.times_of_day'))],
                        'tide' => $tides->tideFor($wentAt),
                        'weather' => config('fishing.weathers')[array_rand(config('fishing.weathers'))],
                        // 多くは全体公開（公開データを扱う画面の負荷を見るため）
                        'visibility' => random_int(1, 10) <= 7 ? 'public' : 'private',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('trips')->insert($trips);

                // いま入れた釣行の番号（最後に入れた番号から数える）
                $lastId = (int) DB::table('trips')->max('id');
                $catches = [];
                for ($id = $lastId - count($trips) + 1; $id <= $lastId; $id++) {
                    // 3割は坊主、残りは1〜3匹
                    $fish = random_int(1, 10) <= 3 ? 0 : random_int(1, 3);
                    for ($n = 0; $n < $fish; $n++) {
                        $catches[] = [
                            'trip_id' => $id,
                            'fish_species' => self::FISH[array_rand(self::FISH)],
                            'method' => config('fishing.methods')[array_rand(config('fishing.methods'))],
                            'length_cm' => random_int(120, 750) / 10,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
                foreach (array_chunk($catches, 1000) as $part) {
                    DB::table('catches')->insert($part);
                }
            });
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
        $this->info("{$spot->name} に釣行を {$count} 件足しました。");

        return self::SUCCESS;
    }
}
