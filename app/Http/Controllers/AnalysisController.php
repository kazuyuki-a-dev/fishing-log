<?php

namespace App\Http\Controllers;

use App\Services\SeasonHeatmap;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalysisController extends Controller
{
    /**
     * シーズンヒートマップ（PG16・FN-02）
     * 最初は「みんな」の、メインフィールドの県
     */
    public function heatmap(Request $request, SeasonHeatmap $heatmap): View
    {
        $user = $request->user();

        // みんな（public）か自分（mine）か。ほかの値は「みんな」にする
        $scope = $request->query('scope') === 'mine' ? 'mine' : 'public';

        // 県：選ばれていなければメインフィールド。一覧にないものもメインフィールドに戻す（FN-17）
        $prefecture = $request->query('prefecture', $user->home_prefecture);
        if ($prefecture !== 'all' && ! in_array($prefecture, config('prefectures'), true)) {
            $prefecture = $user->home_prefecture;
        }

        // rows（表）と tripCount（元になった釣行の数）
        return view('analysis.heatmap', [
            'scope' => $scope,
            'prefecture' => $prefecture,
            ...$heatmap->build($user, $scope, $prefecture),
        ]);
    }
}
