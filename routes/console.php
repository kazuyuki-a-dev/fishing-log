<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 公開環境の起動のたびに呼ぶ（docker/start.sh。#136）。データがまだ空のとき（最初の1回）だけ、お試しデータを入れる
// 2回目からは、使う人が入れたデータを消さないように何もしない
// 複数のインスタンスが同時に起動しても2回入れないように、ロック（「今だれかが作業中」の印）を取ってから確かめる（#172）
// ほかが作業中なら、終わるまで待ってから確かめる（終わっていればデータがあるので、何もしない）
Artisan::command('app:seed-if-empty', function () {
    \Illuminate\Support\Facades\Cache::lock('demo-data', 900)->block(config('fishing.startup_lock_wait'), function () {
        if (\App\Models\User::query()->exists()) {
            $this->info('データがあるので、お試しデータは入れません。');

            return;
        }
        $this->call('db:seed', ['--force' => true]);
    });
})->purpose('データが空のときだけ、お試しデータを入れる');
