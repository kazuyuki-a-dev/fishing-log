<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * お知らせを送った日時（FN-18）。空ならまだ送っていない
     * 公開と非公開を何度切り替えても、お知らせは1回だけにするための印
     */
    public function up(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('notes');
        });

        Schema::table('spots', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });

        Schema::table('spots', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
