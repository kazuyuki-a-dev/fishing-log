<?php

namespace App\Services;

use App\Models\Spot;
use App\Models\SpotLocalInfoChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * 現地の情報の変更の履歴を残す・戻す（#200）
 * 記録するのは、みんなで直せる現地の情報だけ。釣り場名・位置などの基本の情報と、本人だけのメモは記録しない
 */
class LocalInfoHistory
{
    /** 記録する項目と、画面の表示名（カルテの「現地の情報」と同じ言葉） */
    public const FIELDS = [
        'caution_type' => '注意区分',
        'parking_type' => '駐車場',
        'parking_note' => '駐車場のメモ',
        'toilet_available' => 'トイレ',
        'toilet_note' => 'トイレのメモ',
        'convenience_distance_m' => 'コンビニまで',
        'facility_note' => '現地のメモ',
    ];

    /** 保存する前の値を取っておく（保存したあとの record() で比べる） */
    public function snapshot(Spot $spot): array
    {
        return $spot->only(array_keys(self::FIELDS));
    }

    /**
     * 保存する前の値 $before と、保存したあとの値を比べ、変わった項目だけ1行ずつ残す
     * 登録（create）では、$before はすべて空として扱う
     */
    public function record(Spot $spot, ?User $user, array $before, string $action, ?int $revertedChangeId = null): void
    {
        foreach (array_keys(self::FIELDS) as $field) {
            $old = self::normalize($before[$field] ?? null);
            $new = self::normalize($spot->getAttribute($field));
            if ($old === $new) {
                continue;
            }

            SpotLocalInfoChange::create([
                'spot_id' => $spot->id,
                'user_id' => $user?->id,
                'field' => $field,
                'old_value' => $old,
                'new_value' => $new,
                'action' => $action,
                'reverted_change_id' => $revertedChangeId,
            ]);
        }
    }

    /**
     * 管理者が、その変更の前の値に戻す。戻したことも履歴に残す
     */
    public function revert(SpotLocalInfoChange $change, User $admin): void
    {
        DB::transaction(function () use ($change, $admin) {
            $spot = $change->spot()->lockForUpdate()->firstOrFail();
            $before = $this->snapshot($spot);

            $spot->setAttribute($change->field, $change->old_value);
            $spot->updated_by = $admin->id;
            $spot->save();

            $this->record($spot, $admin, $before, 'revert', $change->id);
        });
    }

    /** 画面に出す値の形（空は「未入力」、距離は「〇〇 m」） */
    public static function display(string $field, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '未入力';
        }

        return $field === 'convenience_distance_m' ? $value . ' m' : $value;
    }

    /** メモのように長くなる項目（履歴の一覧では短くし、押すと全文を見せる。#223） */
    public const LONG_FIELDS = ['parking_note', 'toilet_note', 'facility_note'];

    /** 一覧で短くするときの文字数 */
    public const SHORT_LENGTH = 30;

    /** 一覧に出す短い形。長いメモは始めの数文字だけにする（#223） */
    public static function short(string $field, ?string $value): string
    {
        $text = self::display($field, $value);

        if (! in_array($field, self::LONG_FIELDS, true)) {
            return $text;
        }
        // 文字の数で切る（Str::limit は文字の幅で数え、日本語だと半分で切れるため）
        $oneLine = str_replace("\n", ' ', $text);

        return mb_strlen($oneLine) > self::SHORT_LENGTH ? mb_substr($oneLine, 0, self::SHORT_LENGTH) . '…' : $oneLine;
    }

    /**
     * 比べやすいように、空は null、それ以外は文字にそろえる（数値の 300 と文字の "300" を同じにする）
     * 改行は \n にそろえる。入力欄から保存すると \r\n になり、文が同じでも「変わった」と記録されていた（#223）
     */
    public static function normalize(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : str_replace(["\r\n", "\r"], "\n", (string) $value);
    }
}
