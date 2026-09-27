<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'prefecture' => ['required', Rule::in(config('prefectures'))],
            'visibility' => ['required', Rule::in(config('fishing.spot_visibility'))],
            'caution_type' => ['nullable', Rule::in(config('fishing.caution_types'))],
            'parking_type' => ['nullable', Rule::in(config('fishing.parking_types'))],
            'parking_note' => ['nullable', 'string', 'max:255'],
            'toilet_available' => ['nullable', Rule::in(config('fishing.toilet_available'))],
            'toilet_note' => ['nullable', 'string', 'max:255'],
            'convenience_distance_m' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'facility_note' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
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
