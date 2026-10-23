<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 釣り場の現地の情報の変更の履歴（#200）
 * みんなで直せる情報なので、だれが・いつ・どの項目を・何から何に変えたかを、項目ごとに1行ずつ残す
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spot_local_info_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spot_id')->constrained()->cascadeOnDelete();
            // 退会したら空にする（「退会したユーザー」と表示する）
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('field', 40);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            // create（登録）／edit（編集）／revert（管理者が戻した）
            $table->string('action', 10);
            $table->foreignId('reverted_change_id')->nullable()->constrained('spot_local_info_changes')->nullOnDelete();
            // 書きかえない記録なので、作った日時だけ持つ
            $table->timestamp('created_at')->useCurrent();

            $table->index(['spot_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spot_local_info_changes');
    }
};
