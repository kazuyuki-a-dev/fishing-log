<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * 今いる人を管理者にする（NF-04）
 * 画面からは管理者になれないようにして、サーバーを触れる人だけがこのコマンドで決める
 * 例：sail artisan app:make-admin someone@example.com
 */
#[Signature('app:make-admin {email : 管理者にする人のメールアドレス}')]
#[Description('メールアドレスで指定した人を管理者にする')]
class MakeAdmin extends Command
{
    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('そのメールアドレスの人は見つかりませんでした。');

            return self::FAILURE;
        }

        $user->role = 'admin';
        $user->save();

        $this->info("{$user->name}（{$user->email}）を管理者にしました。");

        return self::SUCCESS;
    }
}
