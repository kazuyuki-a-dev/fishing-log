<?php

namespace App\Services;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\NewSpotNotification;
use App\Notifications\NewTripNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Notification;

/**
 * 県内の新着をお知らせする係（FN-18）
 * 送る相手：その県をメインフィールドにしていて、お知らせを ON にしている人（投稿した本人は除く）
 * どの投稿も、お知らせは1回だけ（notified_at に送った日時を残す）
 */
class NewPostNotifier
{
    /**
     * 釣行が公開されたとき。まとめて登録なら何件でも、釣り場の県ごとに1件にまとめる
     *
     * @param  EloquentCollection<int, Trip>  $trips
     */
    public function trips(User $author, EloquentCollection $trips): void
    {
        // 非公開の釣行と、もうお知らせした釣行は送らない
        $trips = $trips->filter(fn(Trip $trip) => $trip->visibility !== 'private' && $trip->notified_at === null);

        if ($trips->isEmpty()) {
            return;
        }

        $trips->load('spot:id,prefecture');

        foreach ($trips->groupBy(fn(Trip $trip) => $trip->spot->prefecture) as $prefecture => $group) {
            Notification::send(
                $this->recipients($prefecture, $author),
                new NewTripNotification($group->pluck('id')->all())
            );
        }

        // 受け取る人が0人でも「お知らせ済み」にする（あとで公開し直したときに送らないため）
        Trip::whereKey($trips->pluck('id'))->update(['notified_at' => now()]);
    }

    /**
     * 釣り場が公開で登録されたとき（または非公開から初めて公開になったとき）
     */
    public function spot(User $author, Spot $spot): void
    {
        if ($spot->visibility !== 'public' || $spot->notified_at !== null) {
            return;
        }

        Notification::send(
            $this->recipients($spot->prefecture, $author),
            new NewSpotNotification($spot->id)
        );

        $spot->forceFill(['notified_at' => now()])->save();
    }

    /** 送る相手：同じ県・お知らせ ON・本人以外 */
    private function recipients(string $prefecture, User $author): EloquentCollection
    {
        return User::where('home_prefecture', $prefecture)
            ->where('notify_enabled', true)
            ->whereKeyNot($author->id)
            ->get();
    }
}
