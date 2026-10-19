<?php

namespace App\Services;

use App\Models\FishCatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * 魚種ごとの自己ベスト（#170）
 * 魚種ごとに、長さ（cm）がいちばん大きい釣果を1匹ずつ。同じ長さなら先に釣ったほう
 * 自分の記録なので、公開範囲に関係なく全部を数える。データベースで数える（記録が増えても重くならないように）
 */
class PersonalBests
{
    /**
     * 大きい順。$limit を指定すると、上からその数だけ
     *
     * @return Collection<int, FishCatch>  trip.spot 付き
     */
    public function forUser(User $user, ?int $limit = null): Collection
    {
        // 魚種ごとに、長さの大きい順 → 先に釣った順に番号を付け（ROW_NUMBER）、1番だけを残す
        $ranked = FishCatch::query()
            ->join('trips', 'trips.id', '=', 'catches.trip_id')
            ->where('trips.user_id', $user->id)
            ->whereNotNull('catches.length_cm')
            ->selectRaw('catches.*, ROW_NUMBER() OVER (PARTITION BY catches.fish_species ORDER BY catches.length_cm DESC, trips.went_at ASC, catches.id ASC) as best_rank');

        return FishCatch::query()
            ->fromSub($ranked, 'catches')
            ->where('best_rank', 1)
            ->orderByDesc('length_cm')
            ->orderBy('id')
            ->when($limit, fn($query) => $query->limit($limit))
            ->with('trip.spot')
            ->get();
    }

    /**
     * 自己ベストがある魚種の数（長さが入っている釣果の魚種）
     */
    public function speciesCount(User $user): int
    {
        return FishCatch::query()
            ->join('trips', 'trips.id', '=', 'catches.trip_id')
            ->where('trips.user_id', $user->id)
            ->whereNotNull('catches.length_cm')
            ->distinct()
            ->count('catches.fish_species');
    }
}
