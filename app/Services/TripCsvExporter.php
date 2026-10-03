<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

/**
 * 釣行・釣果の CSV（FN-04・PG17）
 * 釣れた魚1匹＝1行。坊主の釣行は、魚の欄を空にして1行
 * 緯度・経度と写真は入れない（ファイルは持ち出しやすいので、正確な位置が一緒に出回らないように）
 */
class TripCsvExporter
{
    public const HEADERS = [
        '釣行日時', '時間帯', '釣り場', '県', '潮', '天候', '公開範囲', '釣行メモ',
        '魚種', '釣り方', '釣り方詳細', 'サイズ(cm)', '重さ(g)', '釣果メモ',
    ];

    /**
     * CSV の行を、1行ずつ返す（全部をいっぺんに作らないので、件数が多くてもメモリを使いすぎない）
     *
     * @param  Builder  $trips  出してよい釣行（呼ぶ側で「自分の釣行」に絞っておく）。catches は条件で絞って読み込んでおく
     * @return \Generator<int, array<int, string|null>>
     */
    public function rows(Builder $trips): \Generator
    {
        foreach ($trips->lazy(200) as $trip) {
            $tripColumns = [
                $trip->went_at->format('Y-m-d H:i'),
                $trip->time_of_day,
                $trip->spot->name,
                $trip->spot->prefecture,
                $trip->tide,
                $trip->weather,
                config('fishing.visibility_labels')[$trip->visibility] ?? $trip->visibility,
                $trip->notes,
            ];

            // 坊主の釣行は、魚の欄を空にして1行
            if ($trip->catches->isEmpty()) {
                yield $this->safe([...$tripColumns, null, null, null, null, null, null]);

                continue;
            }

            foreach ($trip->catches as $catch) {
                yield $this->safe([
                    ...$tripColumns,
                    $catch->fish_species,
                    $catch->method,
                    $catch->method_detail,
                    $catch->length_cm,
                    $catch->weight_g,
                    $catch->notes,
                ]);
            }
        }
    }

    /**
     * Excel が「式」として動かさないようにする
     * = + - @ で始まる文字は、Excel で開いたときに計算式として実行されることがあるので、先頭に ' を付ける
     */
    private function safe(array $row): array
    {
        return array_map(
            fn($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value,
            $row
        );
    }
}
