<?php

namespace App\Http\Controllers;

use App\Models\Spot;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Http\Requests\StoreSpotRequest;
use Illuminate\Http\RedirectResponse;

class SpotController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // 県の選択。指定がない・おかしい値なら、メインフィールドの県
        $prefecture = $request->query('prefecture');
        if ($prefecture !== 'all' && ! in_array($prefecture, config('prefectures'), true)) {
            $prefecture = $user->home_prefecture;
        }

        $spots = Spot::query()
            // 公開の釣り場か、自分が登録した釣り場だけ（NF-01）
            ->where(function ($query) use ($user) {
                $query->where('visibility', 'public')
                    ->orWhere('created_by', $user->id);
            })
            // 県で絞る（全国なら絞らない）（FN-17）
            ->when($prefecture !== 'all', fn($query) => $query->where('prefecture', $prefecture))
            // 自分の釣行回数と、最後に行った日（FN-09）
            ->withCount(['trips as my_trips_count' => fn($query) => $query->where('user_id', $user->id)])
            ->withMax(['trips as my_last_went_at' => fn($query) => $query->where('user_id', $user->id)], 'went_at')
            ->orderBy('name')
            ->get();

        return view('spots.index', [
            'spots' => $spots,
            'prefecture' => $prefecture,
        ]);
    }

    public function create(Request $request): View
    {
        return view('spots.create', [
            'defaultPrefecture' => $request->user()->home_prefecture,
        ]);
    }

    public function store(StoreSpotRequest $request): RedirectResponse
    {
        $spot = new Spot($request->validated());
        $spot->created_by = $request->user()->id;
        $spot->updated_by = $request->user()->id;
        $spot->save();

        return redirect()
            ->route('spots.index', ['prefecture' => $spot->prefecture])
            ->with('status', "釣り場「{$spot->name}」を登録しました。");
    }

    public function show(Request $request, Spot $spot): View
    {
        $user = $request->user();

        // 見てよい釣り場か（公開か、自分が登録したもの）（NF-01）
        abort_unless($spot->visibility === 'public' || $spot->created_by === $user->id, 404);

        // カルテに出す釣行：自分のもの ＋ （釣り場が公開なら）ほかの人の全体公開のもの（FN-12）
        $trips = $spot->trips()
            ->where(function ($query) use ($user, $spot) {
                $query->where('user_id', $user->id);
                if ($spot->visibility === 'public') {
                    $query->orWhere('visibility', 'public');
                }
            })
            ->with(['catches', 'user:id,name'])
            ->orderByDesc('went_at')
            ->get();

        $mine = $trips->where('user_id', $user->id);
        $others = $trips->where('user_id', '!=', $user->id);

        return view('spots.show', [
            'spot' => $spot->load('editor:id,name'),
            'trips' => $trips,
            'mine' => [
                'visits' => $mine->count(),
                'caught' => $mine->filter(fn($trip) => $trip->catches->isNotEmpty())->count(),
                'maxSize' => $mine->flatMap->catches->max('length_cm'),
                'lastWentAt' => $mine->first()?->went_at,
            ],
            'others' => [
                'visits' => $others->count(),
                'caught' => $others->filter(fn($trip) => $trip->catches->isNotEmpty())->count(),
            ],
        ]);
    }
}
