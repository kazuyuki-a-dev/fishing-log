<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * ページを開いたときの時間・メモリ・SQL の数を測る（開発環境だけ。#166）
 * ブラウザを通さず、アプリの中でページを開く。それぞれ3回開いて、真ん中の値を出す
 * 例：sail artisan app:measure-pages test@example.com /spots/160 /trips "/trips?scope=public"
 */
#[Signature('app:measure-pages {email : ログインする人のメールアドレス} {paths* : 測るページ}')]
#[Description('ページの表示時間・メモリ・SQL の数を測る（開発環境だけ）')]
class MeasurePages extends Command
{
    public function handle(Kernel $kernel): int
    {
        if (app()->environment('production')) {
            $this->error('公開環境では使えません。');

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->firstOrFail();
        $rows = [];

        foreach ($this->argument('paths') as $path) {
            $results = [];
            foreach (range(1, 3) as $_) {
                Auth::guard('web')->setUser($user);
                $queries = 0;
                DB::listen(function () use (&$queries) {
                    $queries++;
                });
                memory_reset_peak_usage();
                $before = memory_get_usage();
                $start = microtime(true);

                $response = $kernel->handle(Request::create($path));

                $results[] = [
                    'status' => $response->getStatusCode(),
                    'ms' => (microtime(true) - $start) * 1000,
                    'mb' => (memory_get_peak_usage() - $before) / 1024 / 1024,
                    'queries' => $queries,
                ];
                DB::flushQueryLog();
                app('events')->forget(\Illuminate\Database\Events\QueryExecuted::class);
            }

            // 3回のうち、真ん中の時間の回を出す
            usort($results, fn($a, $b) => $a['ms'] <=> $b['ms']);
            $mid = $results[1];
            $rows[] = [$path, $mid['status'], number_format($mid['ms']) . ' ms', number_format($mid['mb'], 1) . ' MB', $mid['queries']];
        }

        $this->table(['ページ', '状態', '時間', 'メモリ（増えた分）', 'SQL の数'], $rows);

        return self::SUCCESS;
    }
}
