<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 県内新着のお知らせを受け取るかどうか（FN-18・PG18）。最初は受け取る
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_enabled')->default(true)->after('home_prefecture');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notify_enabled');
        });
    }
};
