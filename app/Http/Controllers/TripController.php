<?php

namespace App\Http\Controllers;

use App\Http\Requests\TripRequest;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Services\TideCalculator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

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
        return view('trips.create', [
            'spots' => $this->selectableSpots($request->user()),
            // 釣り場の画面から来たときは、その釣り場を最初から選んでおく
            'selectedSpotId' => $request->query('spot'),
        ]);
    }

    public function store(TripRequest $request, TideCalculator $tides): RedirectResponse
    {
        $trip = DB::transaction(function () use ($request, $tides) {
            // 釣行を保存（持ち主はログイン中の本人）
            $trip = $request->user()->trips()->create($this->tripData($request, $tides));
            $this->saveCatches($trip, $request->validated('catches') ?? []);

            return $trip;
        });

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', $this->savedMessage('記録', $trip));
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
            'visibility' => $trip->effectiveVisibility(),
            // ほかの人が見ていて「釣り場だけ隠す」なら、釣り場名を出さない（FN-12）
            'hideSpot' => ! $isOwner && $trip->effectiveVisibility() === 'spot_hidden',
        ]);
    }

    public function edit(Request $request, Trip $trip): View
    {
        // 編集してよいのは本人だけ（PG13）
        Gate::authorize('update', $trip);

        $trip->load('catches');

        return view('trips.edit', [
            'trip' => $trip,
            'spots' => $this->selectableSpots($request->user(), $trip->spot_id),
        ]);
    }

    public function update(TripRequest $request, Trip $trip, TideCalculator $tides): RedirectResponse
    {
        Gate::authorize('update', $trip);

        DB::transaction(function () use ($request, $trip, $tides) {
            // 釣行を書き換える（日時が変わったら潮も計算し直す）
            $trip->update($this->tripData($request, $tides));

            // 釣果は、今あるものを全部消して、送られてきたものを入れ直す
            $trip->catches()->delete();
            $this->saveCatches($trip, $request->validated('catches') ?? []);
        });

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', $this->savedMessage('更新', $trip));
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        // 削除してよいのは本人だけ（PG14）
        Gate::authorize('delete', $trip);

        // 釣果は、テーブルの設定（ON DELETE CASCADE）で一緒に消える
        $trip->delete();

        return redirect()
            ->route('trips.index')
            ->with('status', '釣行を削除しました。');
    }

    // ---- ここから下は、このコントローラの中だけで使う手伝い ----

    /**
     * 選べる釣り場：公開か、自分が登録したもの（NF-01）。編集のときは今の釣り場も
     */
    private function selectableSpots(User $user, ?int $currentSpotId = null): Collection
    {
        return Spot::query()
            ->where(function ($query) use ($user, $currentSpotId) {
                $query->visibleTo($user);
                if ($currentSpotId) {
                    $query->orWhere('id', $currentSpotId);
                }
            })
            ->orderBy('prefecture')
            ->orderBy('name')
            ->get(['id', 'name', 'prefecture']);
    }

    /**
     * 保存する釣行のデータ。日時から計算した潮を足す（FN-08）
     */
    private function tripData(TripRequest $request, TideCalculator $tides): array
    {
        $data = $request->safe()->except('catches');
        $data['tide'] = $tides->tideFor(Carbon::parse($data['went_at'], 'Asia/Tokyo'));

        return $data;
    }

    /**
     * 釣果を1匹ずつ保存する。「その他」は入力した魚の名前で保存（FN-01）
     */
    private function saveCatches(Trip $trip, array $catches): void
    {
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
    }

    /**
     * 保存したあとのメッセージ
     */
    private function savedMessage(string $verb, Trip $trip): string
    {
        $count = $trip->catches()->count();

        return $count > 0
            ? "釣行を{$verb}しました（釣果 {$count} 匹）。"
            : "釣行を{$verb}しました（坊主）。";
    }
}
