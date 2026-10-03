<?php

namespace App\Http\Controllers;

use App\Models\Spot;
use App\Services\ConditionMatcher;
use App\Services\TideCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlannerController extends Controller
{
    public function index(Request $request, TideCalculator $tides, ConditionMatcher $matcher): View
    {
        $user = $request->user();

        // 行く日（最初は今日）
        $date = $this->dateFromQuery($request->query('date'));

        // 県（最初はメインフィールド）（FN-17）
        $prefecture = $request->query('prefecture');
        if ($prefecture !== 'all' && ! in_array($prefecture, config('prefectures'), true)) {
            $prefecture = $user->home_prefecture;
        }

        // 時間帯（選ばれていなければ「指定なし」）
        $timeOfDay = in_array($request->query('time_of_day'), config('fishing.times_of_day'), true)
            ? $request->query('time_of_day')
            : null;

        // 集計に使う記録：all = 自分＋みんなの公開実績、mine = 自分の実績だけ（PG22）
        $scope = $request->query('scope') === 'mine' ? 'mine' : 'all';

        $tide = $tides->tideFor($date);

        // 見てよい釣り場と、数えてよい釣行を、まとめて取り出す
        $spots = Spot::query()
            ->visibleTo($user)
            ->when($prefecture !== 'all', fn($query) => $query->where('prefecture', $prefecture))
            ->with(['trips' => function ($query) use ($user, $scope) {
                $query->visibleWithSpotTo($user)
                    ->when($scope === 'mine', fn($query) => $query->where('user_id', $user->id))
                    ->with('catches');
            }])
            ->get();

        // 釣り場ごとに、条件に合う釣行を集計する。釣れた記録がなければ、条件を少しずつゆるめる（FN-16）
        $plans = $spots->map(function (Spot $spot) use ($matcher, $date, $tide, $timeOfDay) {
            $match = $matcher->match($spot->trips, $date, $tide, $timeOfDay);
            $caughtTrips = $match['matched']->filter(fn($trip) => $trip->catches->isNotEmpty());
            $catches = $match['matched']->flatMap->catches;

            return $match + [
                'spot' => $spot,
                'bestTimeOfDay' => $caughtTrips->countBy('time_of_day')->sortDesc()->keys()->first(),
                'methods' => $catches->countBy('method'),
                'species' => $catches->countBy('fish_species')->sortDesc()->take(3),
                'maxSize' => $catches->max('length_cm'),
            ];
        })
            // 一致のレベルが先（ぴったり → 潮だけ → 月だけ → 記録なし）。同じレベルなら釣れた回数 → 行った回数の多い順
            ->sortBy([['rank', 'asc'], ['caught', 'desc'], ['visits', 'desc']])
            ->values();

        return view('planner.index', [
            'date' => $date,
            'tide' => $tide,
            'lunarDay' => $tides->lunarDay($date),
            'prefecture' => $prefecture,
            'timeOfDay' => $timeOfDay,
            'scope' => $scope,
            'plans' => $plans,
        ]);
    }
}
