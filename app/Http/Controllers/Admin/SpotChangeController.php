<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SpotLocalInfoChange;
use App\Services\LocalInfoHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 現地の情報の変更を、前の値に戻す（#200）
 * 戻せるのは管理者だけ（ルートの admin ミドルウェアで確かめる。ほかの人には 404）
 */
class SpotChangeController extends Controller
{
    public function revert(Request $request, SpotLocalInfoChange $change, LocalInfoHistory $history): RedirectResponse
    {
        $history->revert($change, $request->user());

        return redirect()
            ->route('spots.history', $change->spot_id)
            ->with('status', LocalInfoHistory::FIELDS[$change->field] . 'を、変更の前の値（' . LocalInfoHistory::display($change->field, $change->old_value) . '）に戻しました。');
    }
}
