<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SpotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // 現地の情報：見られる人はみんな直せる（FN-14）
        $rules = [
            'caution_type' => ['nullable', Rule::in(config('fishing.caution_types'))],
            'parking_type' => ['nullable', Rule::in(config('fishing.parking_types'))],
            'parking_note' => ['nullable', 'string', 'max:255'],
            'toilet_available' => ['nullable', Rule::in(config('fishing.toilet_available'))],
            'toilet_note' => ['nullable', 'string', 'max:255'],
            'convenience_distance_m' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'facility_note' => ['nullable', 'string', 'max:2000'],
        ];

        // 釣り場名・県・公開設定・メモ：登録のとき、または登録した本人の編集のときだけ受け取る（PG09）
        if ($this->canEditBasic()) {
            $rules += [
                'name' => ['required', 'string', 'max:255'],
                'prefecture' => ['required', Rule::in(config('prefectures'))],
                'visibility' => ['required', Rule::in(config('fishing.spot_visibility'))],
                'notes' => ['nullable', 'string', 'max:2000'],
            ];
        }

        return $rules;
    }

    /**
     * 釣り場名などの基本の情報を扱ってよいか
     * 登録のとき（URL に {spot} がない）は、もちろん扱ってよい
     */
    public function canEditBasic(): bool
    {
        $spot = $this->route('spot');

        return $spot === null || $this->user()->can('updateBasic', $spot);
    }

    public function attributes(): array
    {
        return [
            'name' => '釣り場名',
            'prefecture' => '都道府県',
            'visibility' => '公開設定',
            'caution_type' => '注意区分',
            'parking_type' => '駐車場',
            'parking_note' => '駐車場のメモ',
            'toilet_available' => 'トイレ',
            'toilet_note' => 'トイレのメモ',
            'convenience_distance_m' => 'コンビニまでの距離',
            'facility_note' => '現地情報のメモ',
            'notes' => 'メモ',
        ];
    }
}
