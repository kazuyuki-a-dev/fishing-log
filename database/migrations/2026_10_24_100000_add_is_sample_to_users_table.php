<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ダミーの人の印（#211）。シーダーで作った釣り人の釣行は、画面で「ダミー」と分かるようにする
     * 最初は false（自分で会員登録した人は本物）。今あるデータは、シーダーで作った人のメールアドレスで印を付ける
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_sample')->default(false)->after('role');
        });

        // シーダーで作るダミーの釣り人（お試し用アカウント・開発用のテストユーザー・秋田の3人・神奈川の1人・各県の12人）
        $emails = [
            config('fishing.demo_email'),
            'test@example.com',
            'minato@example.com',
            'surf@example.com',
            'iso@example.com',
            'wanoku@example.com',
            ...array_map(fn(int $n) => "angler{$n}@example.com", range(1, 12)),
        ];
        DB::table('users')->whereIn('email', $emails)->update(['is_sample' => true]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_sample');
        });
    }
};
