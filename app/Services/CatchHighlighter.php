<?php

namespace App\Services;

use App\Models\FishCatch;
use App\Models\Trip;

class CatchHighlighter
{
    /**
     * 登録したばかりの釣行について、ハイライトの文を返す（FN-07）
     * 比べる相手は、同じ人のほかの釣行の記録（この釣行は除く）
     */
    public function for(Trip $trip): array
    {
        $catches = $trip->catches;

        // 坊主のときは、励ましの一言
        if ($catches->isEmpty()) {
            $visits = Trip::where('user_id', $trip->user_id)
                ->where('spot_id', $trip->spot_id)
                ->count();

            return ["記録しました。坊主も大事な判断材料です（この釣り場 {$visits} 回目）。"];
        }

        // 自分のほかの釣行の釣果（使うたびに作り直す）
        $pastCatches = fn() => FishCatch::whereHas('trip', fn($query) => $query
            ->where('user_id', $trip->user_id)
            ->where('id', '!=', $trip->id));

        $highlights = [];

        // ① 自分の最大サイズを更新
        $biggest = $catches->whereNotNull('length_cm')->sortByDesc('length_cm')->first();
        if ($biggest) {
            $pastMax = $pastCatches()->max('length_cm');
            if ($pastMax === null || (float) $biggest->length_cm > (float) $pastMax) {
                $highlights[] = "自分の最大サイズを更新！ {$biggest->fish_species} {$biggest->length_cm} cm";
            }
        }

        // ② 初めて釣った魚
        $pastSpecies = $pastCatches()->distinct()->pluck('fish_species');
        $newSpecies = $catches->pluck('fish_species')->unique()->diff($pastSpecies);
        if ($newSpecies->isNotEmpty()) {
            $highlights[] = '初めて釣った魚：' . $newSpecies->join('・');
        }

        // ③ この釣り場で初めて釣れた
        $caughtHereBefore = Trip::where('user_id', $trip->user_id)
            ->where('spot_id', $trip->spot_id)
            ->where('id', '!=', $trip->id)
            ->has('catches')
            ->exists();
        if (! $caughtHereBefore) {
            $highlights[] = 'この釣り場で初めて釣れました！';
        }

        // どれにも当てはまらないときも、この釣り場での通算を返す（記録1件ごとに手応え）
        if (empty($highlights)) {
            $here = Trip::where('user_id', $trip->user_id)->where('spot_id', $trip->spot_id);
            $visits = (clone $here)->count();
            $caught = (clone $here)->has('catches')->count();

            $highlights[] = "この釣り場で {$caught} 回目の釣果です（{$visits} 回行って {$caught} 回釣れた）。";
        }

        return $highlights;
    }
}
