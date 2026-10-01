<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 過去の釣行のまとめて登録（PG15）の入力チェック
 * 1行＝釣った魚1匹。魚種が空の行は坊主
 */
class BulkTripRequest extends FormRequest
{
    // 1回に登録できる行の数。PHP が1回に受け取れるファイルの数（初期値 20）に合わせる
    public const MAX_ROWS = 20;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            // 公開範囲はまとめて1つ
            'visibility' => ['required', Rule::in(config('fishing.trip_visibility'))],

            'rows' => ['required', 'array', 'max:' . self::MAX_ROWS],
            'rows.*.spot_id' => [
                'required',
                // 公開の釣り場か、自分が登録した釣り場だけ（NF-01）
                Rule::exists('spots', 'id')->where(function ($query) use ($userId) {
                    $query->where('visibility', 'public')->orWhere('created_by', $userId);
                }),
            ],
            'rows.*.went_at' => ['required', 'date'],
            'rows.*.time_of_day' => ['required', Rule::in(config('fishing.times_of_day'))],
            // 魚種は空でもよい（坊主）。選んだときだけ釣り方が必要
            'rows.*.fish_species' => ['nullable', Rule::in([...config('fishing.fish_species'), 'その他'])],
            'rows.*.fish_species_other' => ['nullable', 'required_if:rows.*.fish_species,その他', 'string', 'max:100'],
            'rows.*.method' => ['nullable', 'required_with:rows.*.fish_species', Rule::in(config('fishing.methods'))],
            'rows.*.length_cm' => ['nullable', 'numeric', 'min:0', 'max:9999.9'],
            'rows.*.photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.required' => '写真を選ぶか「行を足す」で、1行以上入れてください。',
            'rows.max' => '1回に登録できるのは' . self::MAX_ROWS . '行までです。',
        ];
    }

    public function attributes(): array
    {
        return [
            'visibility' => '公開範囲',
            'rows.*.spot_id' => ':position行目の釣り場',
            'rows.*.went_at' => ':position行目の釣行日時',
            'rows.*.time_of_day' => ':position行目の時間帯',
            'rows.*.fish_species' => ':position行目の魚種',
            'rows.*.fish_species_other' => ':position行目の魚種（その他）',
            'rows.*.method' => ':position行目の釣り方',
            'rows.*.length_cm' => ':position行目のサイズ',
            'rows.*.photo' => ':position行目の写真',
        ];
    }
}
