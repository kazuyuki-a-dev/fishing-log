<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * 外部キーの列に索引を足す（PostgreSQL のときだけ。#241）
 *
 * MySQL は外部キーを作ると、その列の索引も自動で作る。PostgreSQL は作らないので、
 * 釣果から釣行を探すときなどに表を毎回はじめから全部見ることになり、とても遅くなる。
 * MySQL にはもう同じ索引があるので、二重にならないように何もしない。
 */
return new class extends Migration
{
    /** 表 => 索引を足す列 */
    private const COLUMNS = [
        'spots' => ['created_by', 'updated_by'],
        'trips' => ['user_id', 'spot_id'],
        'catches' => ['trip_id'],
        'reports' => ['reporter_id', 'trip_id', 'spot_id'],
        'spot_local_info_changes' => ['user_id', 'reverted_change_id'],
    ];

    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function ($blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->index($column);
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function ($blueprint) use ($columns) {
                foreach ($columns as $column) {
                    $blueprint->dropIndex([$column]);
                }
            });
        }
    }
};
