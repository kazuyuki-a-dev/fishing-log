<?php

namespace App\Http\Controllers;

use App\Http\Requests\SpotRequest;
use App\Models\Spot;
use App\Services\TideCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use App\Services\WeatherService;

class SpotController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // 県：ログインしている人は、指定がなければメインフィールド
        //     ゲストは、指定がなければ「まだ選んでいない」（FN-17）
        $prefecture = $request->query('prefecture');
        if ($prefecture !== 'all' && ! in_array($prefecture, config('prefectures'), true)) {
            $prefecture = $user?->home_prefecture;
        }

        // ゲストがまだ県を選んでいなければ、一覧は出さずに県を選んでもらう
        if ($prefecture === null) {
            return view('spots.index', ['spots' => collect(), 'prefecture' => null]);
        }

        $spots = Spot::query()
            ->visibleTo($user) // 見てよい釣り場だけ（NF-01）
            // 県で絞る（全国なら絞らない）（FN-17）
            ->when($prefecture !== 'all', fn($query) => $query->where('prefecture', $prefecture))
            // 自分の釣行回数と、最後に行った日（ログインしている人だけ）（FN-09）
            ->when($user, fn($query) => $query
                ->withCount(['trips as my_trips_count' => fn($query) => $query->where('user_id', $user->id)])
                ->withMax(['trips as my_last_went_at' => fn($query) => $query->where('user_id', $user->id)], 'went_at'))
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

    public function store(SpotRequest $request): RedirectResponse
    {
        $spot = new Spot($request->validated());
        $spot->created_by = $request->user()->id;
        $spot->updated_by = $request->user()->id;
        $spot->save();

        // 登録したら、その釣り場のカルテへ。釣行の登録へ進むボタンを出す（PG08）
        return redirect()
            ->route('spots.show', $spot)
            ->with('status', '釣り場を登録しました。')
            ->with('registered', true);
    }

    public function show(Request $request, Spot $spot, TideCalculator $tides, WeatherService $weather): View
    {
        $user = $request->user();

        Gate::authorize('view', $spot);

        $trips = $spot->trips()
            ->visibleWithSpotTo($user) // 自分のもの ＋ ほかの人の「全体公開かつ釣り場も公開」のもの（FN-12）
            ->with(['catches', 'user:id,name'])
            ->orderByDesc('went_at')
            ->get();

        // ゲストには「自分の実績」はなく、見える釣行はすべて「ほかの人の公開実績」
        $mine = $user ? $trips->where('user_id', $user->id) : collect();
        $others = $user ? $trips->where('user_id', '!=', $user->id) : $trips;

        // ---- 釣行判断（FN-11） ----
        // 日付：指定がない・おかしい値なら今日
        $date = $this->dateFromQuery($request->query('date'));

        // 時間帯：選ばれていなければ「指定なし」
        $timeOfDay = in_array($request->query('time_of_day'), config('fishing.times_of_day'), true)
            ? $request->query('time_of_day')
            : null;

        $tide = $tides->tideFor($date);

        $matched = $trips->filter(fn($trip) => $trip->matchesCondition($tide, $timeOfDay));

        $matchedCatches = $matched->flatMap->catches;

        $judge = [
            'date' => $date,
            'timeOfDay' => $timeOfDay,
            'tide' => $tide,
            'lunarDay' => $tides->lunarDay($date),
            'visits' => $matched->count(),
            'caught' => $matched->filter(fn($trip) => $trip->catches->isNotEmpty())->count(),
            'methods' => $matchedCatches->countBy('method'),
            'species' => $matchedCatches->countBy('fish_species')->sortDesc()->take(3),
            'nextBigTide' => $tides->nextDateWithTide($date->copy()->addDay(), '大潮'),
            'matchedIds' => $matched->pluck('id'),
        ];

        // 天気予報（FN-11）。位置がある釣り場で、今日から 15 日先までだけ
        $judge['forecast'] = $spot->latitude !== null && $weather->isForecastable($judge['date'])
            ? ($weather->forecastFor((float) $spot->latitude, (float) $spot->longitude, $judge['date']) ?? '取得できませんでした')
            : null;

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
            'judge' => $judge,
            'location' => $spot->locationFor($request->user()),
        ]);
    }

    public function edit(Request $request, Spot $spot): View
    {
        // 見られる人はみんな、編集画面を開ける（FN-14）
        Gate::authorize('update', $spot);

        return view('spots.edit', [
            'spot' => $spot,
            // 釣り場名などを直せるのは、登録した本人だけ（PG09）
            'canEditBasic' => $request->user()->can('updateBasic', $spot),
        ]);
    }

    public function update(SpotRequest $request, Spot $spot): RedirectResponse
    {
        Gate::authorize('update', $spot);

        // 入力チェックを通った項目だけ書き換える（本人でなければ、釣り場名などは入っていない）
        $spot->fill($request->validated());
        // 最後に更新した人を記録する（FN-14）
        $spot->updated_by = $request->user()->id;
        $spot->save();

        return redirect()
            ->route('spots.show', $spot)
            ->with('status', '釣り場の情報を更新しました。');
    }

    /**
     * 釣行を登録した直後の質問から、現地の情報だけを保存する（FN-14）
     * 釣り場名などの基本の情報は受け取らない
     */
    public function updateLocalInfo(Request $request, Spot $spot): RedirectResponse
    {
        Gate::authorize('update', $spot);

        $validated = $request->validate([
            'caution_type' => ['nullable', Rule::in(config('fishing.caution_types'))],
            'parking_type' => ['nullable', Rule::in(config('fishing.parking_types'))],
            'toilet_available' => ['nullable', Rule::in(config('fishing.toilet_available'))],
            'convenience_distance_m' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ], [], [
            'caution_type' => '注意区分',
            'parking_type' => '駐車場',
            'toilet_available' => 'トイレ',
            'convenience_distance_m' => 'コンビニまでの距離',
        ]);

        // 空欄のまま送られた項目は外す（今入っている値を消さないため）
        $answers = array_filter($validated, fn($value) => $value !== null);

        if ($answers === []) {
            return back();
        }

        $spot->fill($answers);
        $spot->updated_by = $request->user()->id;
        $spot->save();

        return back()->with('status', '現地の情報を追加しました。ありがとうございます！');
    }

    /**
     * ピンを置いた位置の近く（300m 以内）にある釣り場を返す（PG08・FN-08）
     * 対象は公開の釣り場と自分の釣り場だけ。位置は返さない（NF-01・NF-06 ⑦）
     */
    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);
        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];

        // まず、四角の範囲でざっくり絞る（緯度 0.003 度 ≒ 330m。経度は北へ行くほど幅が狭くなるので広げる）
        $latRange = 0.003;
        $lngRange = 0.003 / max(cos(deg2rad($lat)), 0.01);

        $spots = Spot::visibleTo($request->user())
            ->whereNotNull('latitude')
            ->whereBetween('latitude', [$lat - $latRange, $lat + $latRange])
            ->whereBetween('longitude', [$lng - $lngRange, $lng + $lngRange])
            ->get(['id', 'name', 'latitude', 'longitude']);

        // 次に、正確な距離を計算して 300m 以内だけを残す
        $candidates = $spots
            ->map(fn(Spot $spot) => [
                'id' => $spot->id,
                'name' => $spot->name,
                'distance' => $this->distanceInMeters($lat, $lng, (float) $spot->latitude, (float) $spot->longitude),
                'url' => route('spots.show', $spot),
            ])
            ->filter(fn(array $candidate) => $candidate['distance'] <= 300)
            ->sortBy('distance')
            ->take(5)
            // 距離は約 50m 単位に丸めて返す
            ->map(fn(array $candidate) => [
                ...$candidate,
                'distance' => max(50, (int) (round($candidate['distance'] / 50) * 50)),
            ])
            ->values();

        return response()->json(['spots' => $candidates]);
    }

    /**
     * 2点間の距離（メートル）。地球を球として計算する（ハバーサインの公式）
     */
    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
