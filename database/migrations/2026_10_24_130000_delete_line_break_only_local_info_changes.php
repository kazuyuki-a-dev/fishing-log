<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * 現地の情報の変更の履歴のうち、改行の文字（\n と \r\n）が変わっただけのものを消す（#223）
     * 入力欄から保存し直すと改行が \r\n になり、文が同じでも「変わった」と記録されていた。本当の変更ではないので残さない
     */
    public function up(): void
    {
        $normalize = fn(?string $value) => $value === null || $value === '' ? null : str_replace(["\r\n", "\r"], "\n", $value);

        DB::table('spot_local_info_changes')
            ->where(fn($query) => $query->where('old_value', 'like', "%\r%")->orWhere('new_value', 'like', "%\r%"))
            ->orderBy('id')
            ->each(function (object $change) use ($normalize) {
                if ($normalize($change->old_value) === $normalize($change->new_value)) {
                    DB::table('spot_local_info_changes')->where('id', $change->id)->delete();
                }
            });
    }

    public function down(): void
    {
        // 消した履歴は戻さない（本当の変更ではないため）
    }
};
