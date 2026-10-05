<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * 報告の入力チェック（PG21）
 * 対象は釣行（trip_id）か釣り場（spot_id）のどちらか一方。見てよいかはコントローラで確かめる
 */
class ReportRequest extends FormRequest
{
    // エラーをほかのフォームと分ける（モーダルを開き直すときの目印にする）
    protected $errorBag = 'report';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trip_id' => ['nullable', 'integer', 'required_without:spot_id', 'prohibits:spot_id'],
            'spot_id' => ['nullable', 'integer', 'required_without:trip_id'],
            'reason' => ['required', Rule::in(config('fishing.report_reasons'))],
            'detail' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => '報告の理由',
            'detail' => '詳しい内容',
        ];
    }
}
