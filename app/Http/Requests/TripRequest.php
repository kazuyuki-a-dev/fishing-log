<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TripRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;
        // 編集のときは、今その釣行に付いている釣り場（登録のときは null）
        $currentSpotId = $this->route('trip')?->spot_id;

        return [
            // 釣行
            'spot_id' => [
                'required',
                // 公開の釣り場か、自分が登録した釣り場。編集のときは今の釣り場も（NF-01）
                Rule::exists('spots', 'id')->where(function ($query) use ($userId, $currentSpotId) {
                    $query->where('visibility', 'public')->orWhere('created_by', $userId);
                    if ($currentSpotId) {
                        $query->orWhere('id', $currentSpotId);
                    }
                }),
            ],
            'went_at' => ['required', 'date'],
            'time_of_day' => ['required', Rule::in(config('fishing.times_of_day'))],
            'visibility' => ['required', Rule::in(config('fishing.trip_visibility'))],
            'weather' => ['nullable', Rule::in(config('fishing.weathers'))],
            'notes' => ['nullable', 'string', 'max:2000'],

            // 釣果（空なら坊主）
            'catches' => ['nullable', 'array'],
            'catches.*.fish_species' => ['required', Rule::in([...config('fishing.fish_species'), 'その他'])],
            'catches.*.fish_species_other' => ['nullable', 'required_if:catches.*.fish_species,その他', 'string', 'max:100'],
            'catches.*.method' => ['required', Rule::in(config('fishing.methods'))],
            'catches.*.method_detail' => ['nullable', 'string', 'max:255'],
            'catches.*.length_cm' => ['nullable', 'numeric', 'min:0', 'max:9999.9'],
            'catches.*.weight_g' => ['nullable', 'integer', 'min:0'],
            'catches.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'spot_id' => '釣り場',
            'went_at' => '釣行日時',
            'time_of_day' => '時間帯',
            'visibility' => '公開範囲',
            'weather' => '天候',
            'notes' => 'メモ',
            'catches.*.fish_species' => ':position匹目の魚種',
            'catches.*.fish_species_other' => ':position匹目の魚種（その他）',
            'catches.*.method' => ':position匹目の釣り方',
            'catches.*.method_detail' => ':position匹目の仕掛け・ルアーなど',
            'catches.*.length_cm' => ':position匹目のサイズ',
            'catches.*.weight_g' => ':position匹目の重さ',
            'catches.*.notes' => ':position匹目のメモ',
        ];
    }
}
