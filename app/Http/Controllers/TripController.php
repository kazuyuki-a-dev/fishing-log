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
use App\Services\CatchHighlighter;
use App\Services\PhotoStorer;
use App\Services\WeatherService;
use App\Http\Requests\BulkTripRequest;
use App\Services\NewPostNotifier;

class TripController extends Controller
{
    // 写真・天気・お知らせの係。コントローラが作られるときに、Laravel が用意して渡してくれる
    public function __construct(
        private PhotoStorer $photos,
        private WeatherService $weather,
        private NewPostNotifier $notifier,
    ) {}

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
            // ?mode=bulk のときは、過去の釣行のまとめて登録モード（PG15）
            'bulk' => $request->query('mode') === 'bulk',
        ]);
    }

    public function store(TripRequest $request, TideCalculator $tides, CatchHighlighter $highlighter): RedirectResponse
    {
        $trip = DB::transaction(function () use ($request, $tides) {
            // 釣行を保存（持ち主はログイン中の本人）
            $trip = $request->user()->trips()->create($this->tripData($request, $tides));
            $this->saveCatches($trip, $request->validated('catches') ?? []);

            return $trip;
        });

        // 公開の釣行なら、同じ県の会員にお知らせする（FN-18）
        $this->notifier->trips($request->user(), new Collection([$trip]));

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', $this->savedMessage('記録', $trip))
            ->with('highlights', $highlighter->for($trip));
    }

    /**
     * 過去の釣行のまとめて登録（PG15）
     * 同じ日・同じ釣り場・同じ時間帯の行を、1つの釣行にまとめて保存する
     */
    public function storeBulk(BulkTripRequest $request, TideCalculator $tides): RedirectResponse
    {
        $visibility = $request->validated('visibility');

        // まとめる目印：「2026-05-03|12|朝マズメ」のような文字
        $groups = collect($request->validated('rows'))->groupBy(fn(array $row) => implode('|', [
            Carbon::parse($row['went_at'])->toDateString(),
            $row['spot_id'],
            $row['time_of_day'],
        ]));

        // 登録した釣行（あとでまとめてお知らせするために集めておく）
        $created = new Collection();

        $catchCount = DB::transaction(function () use ($request, $tides, $groups, $visibility, $created) {
            $fetchWeather = true;
            $catchCount = 0;

            foreach ($groups as $group) {
                // 釣行の日時は、まとまった行のうち一番早いもの
                $first = $group->sortBy('went_at')->first();

                $data = $this->withConditions([
                    'spot_id' => $first['spot_id'],
                    'went_at' => $first['went_at'],
                    'time_of_day' => $first['time_of_day'],
                    'visibility' => $visibility,
                ], $tides, $fetchWeather);

                // 天候が1回取れなかったら、残りは取りに行かない（API が止まっているときに長く待たせないため）
                if (array_key_exists('weather', $data) && $data['weather'] === null) {
                    $fetchWeather = false;
                }

                $trip = $request->user()->trips()->create($data);
                $created->push($trip);

                // 魚種が空の行は坊主なので、釣果にはしない
                $catches = $group->filter(fn(array $row) => ! empty($row['fish_species']))->all();
                $this->saveCatches($trip, $catches);
                $catchCount += count($catches);
            }

            return $catchCount;
        });

        // まとめて登録は、お知らせも県ごとに1件にまとめる（FN-18）
        $this->notifier->trips($request->user(), $created);

        return redirect()
            ->route('trips.index')
            ->with('status', "{$groups->count()}件の釣行を登録しました（釣果 {$catchCount} 匹）。");
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
            // 登録した直後だけ、その釣り場の空欄の現地情報を最大2問聞く（FN-14）
            'localQuestions' => $request->session()->has('highlights') && $request->user()->can('update', $trip->spot)
                ? array_slice($trip->spot->missingLocalInfo(), 0, 2)
                : [],
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

        // 更新する前は非公開だったか（非公開から公開にしたときだけ、お知らせする）
        $wasPrivate = $trip->visibility === 'private';

        // 今ついている写真（引き継いでよい写真の一覧）
        $oldPhotos = $trip->catches()->whereNotNull('image_path')->pluck('image_path')->all();

        DB::transaction(function () use ($request, $trip, $tides, $oldPhotos) {
            // 釣行を書き換える（日時が変わったら潮も計算し直す）
            $trip->update($this->tripData($request, $tides));

            // 釣果は、今あるものを全部消して、送られてきたものを入れ直す（写真は引き継げる）
            $trip->catches()->delete();
            $this->saveCatches($trip, $request->validated('catches') ?? [], $oldPhotos);
        });

        // 引き継がれなかった写真のファイルを消す
        $usedPhotos = $trip->catches()->whereNotNull('image_path')->pluck('image_path')->all();
        foreach (array_diff($oldPhotos, $usedPhotos) as $path) {
            $this->photos->delete($path);
        }

        // 非公開から公開（または釣り場だけ隠す）に変えたら、お知らせする。1回だけ（FN-18）
        if ($wasPrivate) {
            $this->notifier->trips($request->user(), new Collection([$trip]));
        }

        return redirect()
            ->route('trips.show', $trip)
            ->with('status', $this->savedMessage('更新', $trip));
    }

    public function destroy(Trip $trip): RedirectResponse
    {
        // 削除してよいのは本人だけ（PG14）
        Gate::authorize('delete', $trip);

        // 釣果はテーブルの設定（ON DELETE CASCADE）で一緒に消えるが、写真のファイルは消えないので先に集めておく
        $photos = $trip->catches()->whereNotNull('image_path')->pluck('image_path')->all();

        $trip->delete();

        foreach ($photos as $path) {
            $this->photos->delete($path);
        }

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
     * 保存する釣行のデータ（1件の登録・編集）
     */
    private function tripData(TripRequest $request, TideCalculator $tides): array
    {
        return $this->withConditions($request->safe()->except('catches'), $tides);
    }

    /**
     * 日時から計算した潮と、取ってきた天候を足す（FN-08）
     */
    private function withConditions(array $data, TideCalculator $tides, bool $fetchWeather = true): array
    {
        $wentAt = Carbon::parse($data['went_at'], 'Asia/Tokyo');
        $data['tide'] = $tides->tideFor($wentAt);

        // 天候が空で、釣り場に位置があれば、その日時の天候を取ってくる
        if ($fetchWeather && empty($data['weather'])) {
            $spot = Spot::find($data['spot_id']);
            if ($spot?->latitude !== null && $spot?->longitude !== null) {
                $data['weather'] = $this->weather->weatherAt(
                    (float) $spot->latitude,
                    (float) $spot->longitude,
                    $wentAt,
                );
            }
        }

        return $data;
    }

    /**
     * 釣果を1匹ずつ保存する。「その他」は入力した魚の名前で保存（FN-01）
     * 写真は、新しく選ばれたものを保存するか、今の写真（$keepable の中にあるもの）を引き継ぐ
     */
    private function saveCatches(Trip $trip, array $catches, array $keepable = []): void
    {
        foreach ($catches as $catch) {
            if (isset($catch['photo'])) {
                $imagePath = $this->photos->store($catch['photo']);
            } elseif (in_array($catch['keep_photo'] ?? null, $keepable, true)) {
                $imagePath = $catch['keep_photo'];
            } else {
                $imagePath = null;
            }

            $trip->catches()->create([
                'fish_species' => $catch['fish_species'] === 'その他'
                    ? $catch['fish_species_other']
                    : $catch['fish_species'],
                'method' => $catch['method'],
                'method_detail' => $catch['method_detail'] ?? null,
                'length_cm' => $catch['length_cm'] ?? null,
                'weight_g' => $catch['weight_g'] ?? null,
                'notes' => $catch['notes'] ?? null,
                'image_path' => $imagePath,
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
