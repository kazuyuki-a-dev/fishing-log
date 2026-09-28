<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Models\Spot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Services\TideCalculator;
use Illuminate\Support\Carbon;
use App\Models\Trip;
use Illuminate\Support\Facades\Gate;

class TripController extends Controller
{
    public function index(Request $request): View
    {
        // 自分の釣行を新しい順に（PG10）
        $trips = $request->user()->trips()
            ->with(['spot:id,name,prefecture', 'catches:id,trip_id,fish_species'])
            ->orderByDesc('went_at')
            ->paginate(20);

        return view('trips.index', ['trips' => $trips]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();

        // 選べる釣り場：公開か、自分が登録したもの（NF-01）
        $spots = Spot::query()
            ->where(function ($query) use ($user) {
                $query->where('visibility', 'public')->orWhere('created_by', $user->id);
            })
            ->orderBy('prefecture')
            ->orderBy('name')
            ->get(['id', 'name', 'prefecture']);

        return view('trips.create', [
            'spots' => $spots,
            // 釣り場の画面から来たときは、その釣り場を最初から選んでおく
            'selectedSpotId' => $request->query('spot'),
        ]);
    }

    public function store(StoreTripRequest $request, TideCalculator $tides): RedirectResponse
    {
        $catches = $request->validated('catches') ?? [];

        // 釣行のデータに、日時から計算した潮を足す（FN-08）
        $tripData = $request->safe()->except('catches');
        $tripData['tide'] = $tides->tideFor(Carbon::parse($tripData['went_at'], 'Asia/Tokyo'));

        $trip = DB::transaction(function () use ($request, $tripData, $catches) {
            $trip = $request->user()->trips()->create($tripData);

            // 釣果を1匹ずつ保存
            foreach ($catches as $catch) {
                $trip->catches()->create([
                    'fish_species' => $catch['fish_species'] === 'その他'
                        ? $catch['fish_species_other']
                        : $catch['fish_species'],
                    'method' => $catch['method'],
                    'method_detail' => $catch['method_detail'] ?? null,
                    'length_cm' => $catch['length_cm'] ?? null,
                    'weight_g' => $catch['weight_g'] ?? null,
                    'notes' => $catch['notes'] ?? null,
                ]);
            }

            return $trip;
        });

        $message = count($catches) > 0
            ? "釣行を記録しました（釣果 " . count($catches) . " 匹）。"
            : "釣行を記録しました（坊主）。";

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', $message);
    }

    public function show(Request $request, Trip $trip): View
    {
        // 見てよい釣行か（本人か、全体公開・釣り場だけ隠すの釣行）
        Gate::authorize('view', $trip);

        $trip->load(['spot', 'catches', 'user:id,name']);
        $isOwner = $trip->user_id === $request->user()->id;

        return view('trips.show', [
            'trip' => $trip,
            'isOwner' => $isOwner,
            // 本人には保存した公開範囲、ほかの人には実際に使う公開範囲
            'visibility' => $trip->effectiveVisibility(),
            // ほかの人が見ていて「釣り場だけ隠す」なら、釣り場名を出さない（FN-12）
            'hideSpot' => ! $isOwner && $trip->effectiveVisibility() === 'spot_hidden',
        ]);
    }
}
