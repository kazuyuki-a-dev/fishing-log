<?php

namespace App\Policies;

use App\Models\Trip;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TripPolicy
{
    /**
     * 釣行の詳細を見てよいか（PG11・NF-01）
     * 本人はいつでも。ほかの人は、全体公開か釣り場だけ隠すの釣行だけ
     */
    public function view(User $user, Trip $trip): Response
    {
        if ($trip->user_id === $user->id) {
            return Response::allow();
        }

        return in_array($trip->effectiveVisibility(), ['public', 'spot_hidden'], true)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * 釣行を編集してよいか（PG13・NF-01）：本人だけ
     */
    public function update(User $user, Trip $trip): Response
    {
        return $trip->user_id === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * 釣行を削除してよいか（PG14・NF-01）：本人だけ
     */
    public function delete(User $user, Trip $trip): Response
    {
        return $this->update($user, $trip);
    }
}
