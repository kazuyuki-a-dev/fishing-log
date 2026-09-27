<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripRequest;
use App\Models\Spot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TripController extends Controller
{
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

    public function store(StoreTripRequest $request): RedirectResponse
    {
        $catches = $request->validated('catches') ?? [];

        $trip = DB::transaction(function () use ($request, $catches) {
            // 釣行を保存（持ち主はログイン中の本人）
            $trip = $request->user()->trips()->create(
                $request->safe()->except('catches')
            );

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
            ->route('spots.index', ['prefecture' => $trip->spot->prefecture])
            ->with('status', $message);
    }
}
