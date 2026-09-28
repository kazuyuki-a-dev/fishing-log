<?php

namespace App\Http\Controllers;

use App\Models\Spot;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // 県：選ばれていなければ住んでいる県。ゲストは null（毎回選んでもらう）
        $prefecture = $request->query('prefecture', $user?->home_prefecture);
        if ($prefecture !== 'all' && ! in_array($prefecture, config('prefectures'), true)) {
            $prefecture = $user?->home_prefecture;
        }

        // 魚種：一覧にないものは「指定なし」にする
        $species = $request->query('species');
        if (! in_array($species, config('fishing.fish_species'), true)) {
            $species = null;
        }

        if ($prefecture === null) {
            return view('feed.index', [
                'prefecture' => null,
                'species' => $species,
                'trips' => collect(),
                'spots' => collect(),
            ]);
        }

        $trips = Trip::query()
            ->whereIn('visibility', ['public', 'spot_hidden'])
            ->when($prefecture !== 'all', fn($query) => $query->whereHas(
                'spot',
                fn($spot) => $spot->where('prefecture', $prefecture)
            ))
            ->when($species, fn($query) => $query->whereHas(
                'catches',
                fn($catch) => $catch->where('fish_species', $species)
            ))
            ->with(['spot', 'user:id,name', 'catches'])
            ->latest('went_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // 0件のときに見せる、公開の釣り場の現地情報
        $spots = $trips->isEmpty()
            ? Spot::where('visibility', 'public')
            ->when($prefecture !== 'all', fn($query) => $query->where('prefecture', $prefecture))
            ->latest('updated_at')
            ->take(5)
            ->get()
            : collect();

        return view('feed.index', compact('prefecture', 'species', 'trips', 'spots'));
    }
}
