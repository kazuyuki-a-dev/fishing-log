<?php

namespace App\Policies;

use App\Models\Spot;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SpotPolicy
{
    /**
     * 釣り場（カルテ）を見てよいか（NF-01）
     * - ログインしている人：公開か、自分が登録したもの
     * - ゲスト：公開のものだけ
     */
    public function view(?User $user, Spot $spot): Response
    {
        return $spot->visibility === 'public' || ($user && $spot->created_by === $user->id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * 釣り場を報告してよいか（PG21・NF-04）
     * ほかの人が登録した公開の釣り場だけ。非公開は「ない」ことにする
     */
    public function report(User $user, Spot $spot): Response
    {
        if ($spot->visibility !== 'public') {
            return Response::denyAsNotFound();
        }

        return $spot->created_by === $user->id
            ? Response::deny('自分の投稿は報告できません。')
            : Response::allow();
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
