<?php

namespace App\Http\Controllers;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // 自分の釣果だけを取り出す「型」。使うたびに新しく作る
        $myCatches = fn() => FishCatch::whereHas('trip', fn($trip) => $trip->where('user_id', $user->id));

        // サマリー指標（FN-06）
        $summary = [
            'catchCount' => $myCatches()->count(),
            'speciesCount' => $myCatches()->distinct()->count('fish_species'),
            'tripDays' => (int) $user->trips()->selectRaw('COUNT(DISTINCT DATE(went_at)) as days')->value('days'),
            'biggest' => $myCatches()->whereNotNull('length_cm')->orderByDesc('length_cm')->first(),
        ];

        // 直近の釣行
        $recentTrips = $user->trips()
            ->with(['spot', 'catches'])
            ->latest('went_at')
            ->latest('id')
            ->take(5)
            ->get();

        // 自分の釣り場カルテ（行った回数の多い順）
        $mySpots = Spot::visibleTo($user)
            ->whereHas('trips', fn($trip) => $trip->where('user_id', $user->id))
            ->withCount([
                'trips as visits' => fn($trip) => $trip->where('user_id', $user->id),
                'trips as caught' => fn($trip) => $trip->where('user_id', $user->id)->has('catches'),
            ])
            ->orderByDesc('visits')
            ->take(5)
            ->get();

        // 公開釣果の新着（住んでいる県）
        $feedTrips = Trip::forFeed()
            ->whereHas('spot', fn($spot) => $spot->where('prefecture', $user->home_prefecture))
            ->with(['spot', 'user:id,name', 'catches'])
            ->latest('went_at')
            ->latest('id')
            ->take(5)
            ->get();

        return view('dashboard', compact('summary', 'recentTrips', 'mySpots', 'feedTrips'));
    }
}
