<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Report;
use App\Models\Spot;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    /**
     * 不適切な投稿を報告する（PG21・NF-04）
     * 送ったら元の画面に戻して、完了メッセージを出す
     */
    public function store(ReportRequest $request): RedirectResponse
    {
        $user = $request->user();

        // 対象を探す。ない番号なら 404
        $target = $request->filled('trip_id')
            ? Trip::findOrFail($request->integer('trip_id'))
            : Spot::findOrFail($request->integer('spot_id'));

        // 報告してよいか（ほかの人の投稿で、見られるものだけ）。ボタンを隠すだけでなく、ここでも確かめる
        Gate::authorize('report', $target);

        // 同じ人が同じ投稿に、未対応のまま2回報告しない
        $column = $target instanceof Trip ? 'trip_id' : 'spot_id';
        $alreadyOpen = $user->reports()
            ->where($column, $target->id)
            ->where('status', 'open')
            ->exists();
        if ($alreadyOpen) {
            return back()->with('status', 'この投稿は、すでに報告を受け付けています。確認するまでお待ちください。');
        }

        $report = new Report($request->validated());
        $report->reporter_id = $user->id;
        $report->{$column} = $target->id;
        $report->save();

        return back()->with('status', '報告を送りました。ご協力ありがとうございます。');
    }
}
