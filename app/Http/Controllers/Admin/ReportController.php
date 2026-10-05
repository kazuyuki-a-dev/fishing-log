<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * 報告を確かめて対応する管理画面（NF-04）
 * 入れるのは管理者だけ（ルートの admin ミドルウェアで確かめる）
 */
class ReportController extends Controller
{
    /** 一覧の切り替え。最初は「未対応」だけ */
    private const FILTERS = ['open', 'reviewed', 'closed', 'all'];

    public function index(Request $request): View
    {
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'open';

        $reports = Report::query()
            ->when($filter !== 'all', fn($query) => $query->where('status', $filter))
            ->with([
                'reporter:id,name',
                'trip.spot',
                'trip.catches',
                'trip.user:id,name',
                'spot.creator:id,name',
            ])
            ->latest()
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // その投稿への報告が全部で何件あるか（状態は問わない）
        $countsFor = fn(string $column) => Report::whereIn($column, $reports->pluck($column)->filter()->unique())
            ->selectRaw("{$column} as target_id, COUNT(*) as total")
            ->groupBy($column)
            ->pluck('total', 'target_id');

        return view('admin.reports.index', [
            'reports' => $reports,
            'filter' => $filter,
            // 切り替えのボタンに出す件数
            'statusCounts' => Report::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'tripCounts' => $countsFor('trip_id'),
            'spotCounts' => $countsFor('spot_id'),
        ]);
    }

    /**
     * 対応状況を変える（未対応／確認済み／対応完了）
     */
    public function update(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(config('fishing.report_statuses')))],
        ]);

        $report->status = $validated['status'];
        $report->save();

        return back()->with('status', "報告 #{$report->id} を「" . config('fishing.report_statuses')[$report->status] . '」にしました。');
    }

    /**
     * 報告された投稿を非表示にする
     * 公開範囲を非公開（private）にする。今ある公開範囲のチェックがそのまま効くので、ほかの画面から消える
     * その投稿への報告は、まとめて「対応完了」にする
     */
    public function hide(Report $report): RedirectResponse
    {
        $target = $report->trip ?? $report->spot;
        $column = $report->trip_id ? 'trip_id' : 'spot_id';

        // 釣り場の「最終更新」の日付が、管理者の操作で変わらないようにする
        $target->timestamps = false;
        $target->visibility = 'private';
        $target->save();

        Report::where($column, $target->id)
            ->whereIn('status', ['open', 'reviewed'])
            ->update(['status' => 'closed']);

        return back()->with('status', ($report->trip_id ? '釣行' : '釣り場') . 'を非公開にし、この投稿への報告を「対応完了」にしました。');
    }
}
