<?php

namespace App\Services;

use App\Models\FishCatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * シーズンヒートマップの集計（FN-02・FN-10）
 * 月別×魚種別に、釣れた数（匹）と、何回の釣行で釣れたか（回）を数える
 * 色は匹数で付ける（群れが入った月が濃く見えるように）。回数も添えて、たまたまの大当たりか見分けられるようにする
 */
class SeasonHeatmap
{
    /** 色の段階の数（0匹は白、1匹以上は1〜4） */
    public const LEVELS = 4;

    /**
     * @param  string  $scope  public（みんな：全体公開と釣り場だけ隠す）／mine（自分：公開範囲は問わない）
     * @param  string  $prefecture  県の名前、または all（全国）
     * @return array{rows: array<string, array<int, array{fish: int, trips: int, level: int}>>, tripCount: int}
     *         rows は 魚種 => 月(1〜12) => マス。tripCount は表の元になった釣行の数
     */
    public function build(User $user, string $scope, string $prefecture): array
    {
        $counts = $this->query($user, $scope, $prefecture)
            // 釣果は1行が1匹なので、行の数が匹数。釣行は同じものを2回数えない（DISTINCT）
            ->selectRaw('catches.fish_species as species, MONTH(trips.went_at) as month, COUNT(*) as fish_count, COUNT(DISTINCT trips.id) as trip_count')
            ->groupBy('species', 'month')
            ->get();

        // 色の基準：表の中でいちばん多いマスの匹数
        $max = $counts->max('fish_count');

        $rows = [];
        // 魚種は config の順。記録のある魚だけ行にする
        foreach (config('fishing.fish_species') as $species) {
            $byMonth = $counts->where('species', $species)->keyBy('month');
            if ($byMonth->isEmpty()) {
                continue;
            }

            foreach (range(1, 12) as $month) {
                $fish = (int) ($byMonth[$month]->fish_count ?? 0);
                $rows[$species][$month] = [
                    'fish' => $fish,
                    'trips' => (int) ($byMonth[$month]->trip_count ?? 0),
                    // 0匹は白（0）。1匹でも釣れていれば、いちばん薄い色（1）から付く
                    'level' => $fish > 0 ? (int) ceil($fish / $max * self::LEVELS) : 0,
                ];
            }
        }

        return [
            'rows' => $rows,
            // 「この表は釣行 N 件から」に使う（FN-10 の「記録を続けるきっかけ」）
            'tripCount' => $this->query($user, $scope, $prefecture)->distinct()->count('trips.id'),
        ];
    }

    /**
     * 集計の元：条件に合う釣行で釣れた釣果（表と件数で同じ条件を使うため、1か所にまとめる）
     */
    private function query(User $user, string $scope, string $prefecture): Builder
    {
        return FishCatch::query()
            ->join('trips', 'trips.id', '=', 'catches.trip_id')
            ->join('spots', 'spots.id', '=', 'trips.spot_id')
            ->when(
                $scope === 'mine',
                fn($query) => $query->where('trips.user_id', $user->id),
                // みんな：非公開の釣行は数えない（自分のものでも）
                fn($query) => $query->whereIn('trips.visibility', ['public', 'spot_hidden'])
            )
            ->when($prefecture !== 'all', fn($query) => $query->where('spots.prefecture', $prefecture));
    }
}
