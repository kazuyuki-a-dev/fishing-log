<?php

namespace App\Services;

use App\Models\FishCatch;
use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * 釣行一覧の条件検索（FN-03・PG10）
 * 絞り込みの条件を読み取り、見てよい釣行だけを条件で絞る
 */
class TripSearch
{
    /** 絞り込みの項目（scope と prefecture 以外）。空なら「指定なし」 */
    public const FIELDS = ['spot_id', 'species', 'method', 'from', 'to', 'tide', 'weather', 'time_of_day'];

    /**
     * URL の条件を読み取る。一覧にない値やおかしな日付は「指定なし」にする
     */
    public function filters(Request $request, User $user): array
    {
        $in = fn(string $key, array $allowed) => in_array($request->query($key), $allowed, true) ? $request->query($key) : null;
        $date = function (string $key) use ($request) {
            $value = $request->query($key);
            return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
        };

        // みんな（public）か自分（mine）か。最初は「自分」
        $scope = $request->query('scope') === 'public' ? 'public' : 'mine';

        // 県は「みんな」のときだけ使う。最初はメインフィールド（FN-17）
        $prefecture = $request->query('prefecture', $user->home_prefecture);
        if ($prefecture !== 'all' && ! in_array($prefecture, config('prefectures'), true)) {
            $prefecture = $user->home_prefecture;
        }

        // 釣り場は、選べる釣り場（公開か自分のもの）だけ（NF-01）
        $spotId = $request->query('spot_id');
        $spotId = is_numeric($spotId) && Spot::visibleTo($user)->whereKey((int) $spotId)->exists() ? (int) $spotId : null;

        return [
            'scope' => $scope,
            'prefecture' => $scope === 'public' ? $prefecture : null,
            'spot_id' => $spotId,
            'species' => $in('species', config('fishing.fish_species')),
            'method' => $in('method', config('fishing.methods')),
            'from' => $date('from'),
            'to' => $date('to'),
            'tide' => $in('tide', config('fishing.tides')),
            'weather' => $in('weather', config('fishing.weathers')),
            'time_of_day' => $in('time_of_day', config('fishing.times_of_day')),
        ];
    }

    /** 何か1つでも絞り込んでいるか（「条件をクリア」を出すかどうか） */
    public function isFiltered(array $filters): bool
    {
        return collect(self::FIELDS)->contains(fn(string $field) => $filters[$field] !== null);
    }

    /**
     * 見てよい釣行を、条件で絞ったもの
     */
    public function query(User $user, array $filters): Builder
    {
        return Trip::query()
            // 自分：自分の釣行すべて（非公開も）
            ->when($filters['scope'] === 'mine', fn($query) => $query->where('user_id', $user->id))
            // みんな：フィードと同じく、全体公開と「釣り場だけ隠す」（FN-13）
            ->when($filters['scope'] === 'public', fn($query) => $query
                ->forFeed()
                ->when($filters['prefecture'] !== 'all', fn($query) => $query->whereHas(
                    'spot',
                    fn($spot) => $spot->where('prefecture', $filters['prefecture'])
                )))
            ->when($filters['spot_id'], function ($query) use ($filters) {
                $query->where('spot_id', $filters['spot_id']);
                // みんなで釣り場を指定したときは、釣り場を出してよい釣行（釣行も釣り場も全体公開）だけ
                // 「釣り場だけ隠す」を入れると、その釣り場での釣行だと分かってしまうため
                if ($filters['scope'] === 'public') {
                    $query->where('visibility', 'public')
                        ->whereHas('spot', fn($spot) => $spot->where('visibility', 'public'));
                }
            })
            ->when($filters['from'], fn($query) => $query->whereDate('went_at', '>=', $filters['from']))
            ->when($filters['to'], fn($query) => $query->whereDate('went_at', '<=', $filters['to']))
            ->when($filters['tide'], fn($query) => $query->where('tide', $filters['tide']))
            ->when($filters['weather'], fn($query) => $query->where('weather', $filters['weather']))
            ->when($filters['time_of_day'], fn($query) => $query->where('time_of_day', $filters['time_of_day']))
            // 魚種と釣り方は「同じ1匹」で当てはめる（アジをルアーで釣った釣行）
            ->when(
                $filters['species'] || $filters['method'],
                fn($query) => $query->whereHas('catches', fn($catch) => $this->catchConditions($catch, $filters))
            );
    }

    /**
     * 結果のまとめ：行った回数・釣れた回数・釣り方ごとの匹数（件数を隠さない、FN-16 と同じ考え方）
     * 匹数は、魚種・釣り方の条件に当てはまる魚だけを数える
     */
    public function summary(Builder $query, array $filters): array
    {
        $tripIds = (clone $query)->pluck('id');

        return [
            'visits' => $tripIds->count(),
            'caught' => Trip::whereKey($tripIds)->has('catches')->count(),
            'methods' => $this->catchConditions(FishCatch::whereIn('trip_id', $tripIds), $filters)
                ->selectRaw('method, COUNT(*) as fish')
                ->groupBy('method')
                ->pluck('fish', 'method'),
        ];
    }

    private function catchConditions(Builder $catch, array $filters): Builder
    {
        return $catch
            ->when($filters['species'], fn($catch) => $catch->where('fish_species', $filters['species']))
            ->when($filters['method'], fn($catch) => $catch->where('method', $filters['method']));
    }
}
