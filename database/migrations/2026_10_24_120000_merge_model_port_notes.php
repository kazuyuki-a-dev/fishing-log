<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * モデル港の現地のメモで「港の管理者の案内を確かめてください」が2回入っていたのを、1つの文にまとめる（#223）
     * シーダーが入れたままのメモだけを直す（利用者が書きかえたメモは、形が合わないので変えない）
     * 画面の入力欄から保存し直すと改行が \r\n になるので、改行の文字の違いだけなら同じ文として扱う
     */
    private const OLD_HEAD = "港の中は立入禁止・釣り禁止の場所が多いです。釣りができる場所は、現地の表示や港の管理者の案内を確かめてください。\n国の「釣り文化振興モデル港」です";

    private const OLD_TAIL = '。開いている日や時間は、港の管理者の案内を確かめてください。';

    private const NEW_TAIL = '。港の中は立入禁止・釣り禁止の場所が多いので、釣りができる場所と開いている日や時間は、現地の表示や港の管理者の案内を確かめてください。';

    public function up(): void
    {
        $head = str_replace("\n", '\r?\n', preg_quote(self::OLD_HEAD, '/'));
        $pattern = '/^' . $head . '(（釣りができる場所：[^）]*）)?' . preg_quote(self::OLD_TAIL, '/') . '$/u';

        DB::table('spots')->where('facility_note', 'like', '%開いている日や時間は、港の管理者の案内を確かめてください。')
            ->orderBy('id')
            ->each(function (object $spot) use ($pattern) {
                if (preg_match($pattern, $spot->facility_note, $matches)) {
                    $place = $matches[1] ?? '';
                    // 現地の情報の更新日（updated_at）は変えない（だれかが直したように見せないため）
                    DB::table('spots')->where('id', $spot->id)->update([
                        'facility_note' => '国の「釣り文化振興モデル港」です' . $place . self::NEW_TAIL,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // 文の直しなので戻さない
    }
};
