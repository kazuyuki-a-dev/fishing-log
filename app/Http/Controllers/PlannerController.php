<?php

namespace App\Http\Controllers;

use App\Models\Spot;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Trip;
use App\Models\FishCatch;
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

        // 県（最初はメインフィールド。ゲストはメインフィールドがないので、選んでもらう）（FN-17・#209）
        $prefecture = $request->query('prefecture');
        if ($prefecture !== 'all' && ! in_array($prefecture, config('prefectures'), true)) {
            $prefecture = $user?->home_prefecture;
        }

        // 時間帯（選ばれていなければ「指定なし」）
        $timeOfDay = in_array($request->query('time_of_day'), config('fishing.times_of_day'), true)
            ? $request->query('time_of_day')
            : null;

        // 集計に使う記録：all = 自分＋みんなの公開実績、mine = 自分の実績だけ（PG22）。ゲストはみんなの公開実績だけ（#209）
        $scope = $user && $request->query('scope') === 'mine' ? 'mine' : 'all';

        $tide = $tides->tideFor($date);

        // ゲストが県をまだ選んでいなければ、釣り場は並べずに県を選んでもらう
        if ($prefecture === null) {
            return view('planner.index', [
                'date' => $date,
                'tide' => $tide,
                'prefecture' => null,
                'timeOfDay' => $timeOfDay,
                'scope' => $scope,
                'plans' => collect(),
            ]);
        }

        // 並べる釣り場：見てよい釣り場のうち、選んだ県のもの
        $spots = Spot::query()
            ->visibleTo($user)
            ->when($prefecture !== 'all', fn($query) => $query->where('prefecture', $prefecture))
            ->get();

        // 数えてよい釣行：その釣り場の、見てよい釣行（mine なら自分のものだけ）。読み込まずクエリのまま使う（#168）
        $trips = Trip::query()
            ->whereIn('trips.spot_id', $spots->pluck('id'))
            ->visibleWithSpotTo($user)
            ->when($scope === 'mine', fn($query) => $query->where('trips.user_id', $user->id));

        // 釣り場ごとに、条件に合う釣行をデータベースで数える。釣れた記録がなければ、条件を少しずつゆるめる（FN-16）
        $matches = $matcher->matchBySpot($trips, $spots->pluck('id')->all(), $date, $tide, $timeOfDay);
        $details = $this->details($matcher, $trips, $matches, $date, $tide, $timeOfDay);
        // 記録の内訳（時間帯・潮・年ごと）。「記録の内訳を見る」を押すと開く（#211）
        $breakdowns = $matcher->breakdownBySpot($trips, $matches, $date, $tide, $timeOfDay);

        $plans = $spots->map(fn(Spot $spot) => $matches[$spot->id] + [
            'spot' => $spot,
            'methods' => $details[$spot->id]['methods'] ?? collect(),
            'species' => $details[$spot->id]['species'] ?? collect(),
            'maxSize' => $details[$spot->id]['maxSize'] ?? null,
            'breakdown' => $breakdowns[$spot->id] ?? [],
        ])
            // 一致のレベルが先（ぴったり → 時期と時間帯 → 時期だけ → 記録なし）。同じレベルなら釣れた回数 → 行った回数の多い順
            ->sortBy([['rank', 'asc'], ['caught', 'desc'], ['visits', 'desc']])
            ->values();

        return view('planner.index', [
            'date' => $date,
            'tide' => $tide,
            'prefecture' => $prefecture,
            'timeOfDay' => $timeOfDay,
            'scope' => $scope,
            'plans' => $plans,
        ]);
    }

    /**
     * 釣り場ごとの、使うレベルの釣行の釣果：釣り方ごとの匹数・多い魚3種・最大サイズ（データベースで数える。#168）
     * レベルごとに、そのレベルを使う釣り場をまとめて1回ずつ数える
     *
     * @return array<int, array{methods: \Illuminate\Support\Collection, species: \Illuminate\Support\Collection, maxSize: ?float}>
     */
    private function details(ConditionMatcher $matcher, Builder $trips, array $matches, Carbon $date, string $tide, ?string $timeOfDay): array
    {
        $details = [];
        foreach (array_keys(ConditionMatcher::LABELS) as $level) {
            $spotIds = array_keys(array_filter($matches, fn(array $match) => $match['level'] === $level && $match['caught'] > 0));
            if ($spotIds === []) {
                continue;
            }
            $catches = fn() => FishCatch::query()
                ->join('trips', 'trips.id', '=', 'catches.trip_id')
                ->whereIn('catches.trip_id', $matcher->matchedQuery($trips, $level, $date, $tide, $timeOfDay)
                    ->whereIn('trips.spot_id', $spotIds)->reorder()->select('trips.id'));

            foreach ($catches()->selectRaw('trips.spot_id, catches.method, COUNT(*) as fish')
                ->groupBy('trips.spot_id', 'catches.method')->orderBy('catches.method')->toBase()->get() as $row) {
                $details[$row->spot_id]['methods'] ??= collect();
                $details[$row->spot_id]['methods'][$row->method] = (int) $row->fish;
            }
            foreach ($catches()->selectRaw('trips.spot_id, catches.fish_species, COUNT(*) as fish')
                ->groupBy('trips.spot_id', 'catches.fish_species')->orderByDesc('fish')->orderBy('catches.fish_species')->toBase()->get() as $row) {
                $details[$row->spot_id]['species'] ??= collect();
                if ($details[$row->spot_id]['species']->count() < 3) {
                    $details[$row->spot_id]['species'][$row->fish_species] = (int) $row->fish;
                }
            }
            foreach ($catches()->selectRaw('trips.spot_id, MAX(catches.length_cm) as max_size')
                ->groupBy('trips.spot_id')->toBase()->get() as $row) {
                $details[$row->spot_id]['maxSize'] = $row->max_size;
            }
        }

        return $details;
    }
}
