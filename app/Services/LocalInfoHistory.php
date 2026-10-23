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
            $old = $this->normalize($before[$field] ?? null);
            $new = $this->normalize($spot->getAttribute($field));
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

    /** 比べやすいように、空は null、それ以外は文字にそろえる（数値の 300 と文字の "300" を同じにする） */
    private function normalize(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : (string) $value;
    }
}
