<?php

namespace App\Policies;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SpotPolicy
{
    /**
     * 釣り場（カルテ）を見てよいか（NF-01）：公開か、自分が登録したもの
     */
    public function view(User $user, Spot $spot): Response
    {
        return $spot->visibility === 'public' || $spot->created_by === $user->id
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * 釣り場を編集してよいか（FN-14）：見られる人はみんな、現地の情報を直せる
     */
    public function update(User $user, Spot $spot): Response
    {
        return $this->view($user, $spot);
    }

    /**
     * 釣り場名・県・公開設定・メモを直してよいか（PG09）：登録した本人だけ
     */
    public function updateBasic(User $user, Spot $spot): bool
    {
        return $spot->created_by === $user->id;
    }
}
