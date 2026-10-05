<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 不適切な投稿の報告（テーブル5・NF-04・PG21）
     * 対象は釣行か釣り場のどちらか一方（trip_id と spot_id のどちらかを必ず入れる）
     */
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            // 報告した人・対象が消えたら、報告も消す
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trip_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('spot_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('reason', 50)->index();
            $table->text('detail')->nullable();
            // open：未対応／reviewed：確認済み／closed：対応完了
            $table->string('status', 20)->default('open')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
