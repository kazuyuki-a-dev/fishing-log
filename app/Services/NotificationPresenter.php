<?php

namespace App\Services;

use App\Models\Spot;
use App\Models\Trip;
use App\Models\User;
use App\Notifications\NewSpotNotification;
use App\Notifications\NewTripNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * お知らせを画面に出す形（文章とリンク）にする係（FN-18・NF-01）
 * お知らせには番号だけが入っているので、表示するたびに「今の」公開範囲を見て文章を作る
 * 消された・非公開に戻されたものは出さない。公開範囲のチェックは、ここ1か所だけで行う
 */
class NotificationPresenter
{
    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return Collection<int, array{notification: DatabaseNotification, text: string, url: string}>
     */
    public function present(Collection $notifications): Collection
    {
        // 釣行と釣り場は、まとめて1回で読み込む（1件ずつ読むと遅くなるため）
        $tripIds = $notifications->flatMap(fn($notification) => $notification->data['trip_ids'] ?? []);
        $spotIds = $notifications->pluck('data.spot_id')->filter();

        $trips = Trip::with('spot')->findMany($tripIds->unique()->all())->keyBy('id');
        $spots = Spot::findMany($spotIds->unique()->all())->keyBy('id');

        return $notifications
            ->map(fn(DatabaseNotification $notification) => match ($notification->type) {
                NewTripNotification::class => $this->forTrips($notification, $trips),
                NewSpotNotification::class => $this->forSpot($notification, $spots),
                default => null,
            })
            ->filter()
            ->values();
    }

    /** ベルマークの数字：未読のうち、今も見せてよいものだけを数える */
    public function unreadCount(User $user): int
    {
        return $this->present($user->unreadNotifications()->get())->count();
    }

    private function forTrips(DatabaseNotification $notification, Collection $trips): ?array
    {
        // 今も残っていて、非公開になっていない釣行だけ（釣り場が非公開なら「釣り場だけ隠す」として扱う）
        $visible = collect($notification->data['trip_ids'] ?? [])
            ->map(fn($id) => $trips->get($id))
            ->filter(fn(?Trip $trip) => $trip && $trip->effectiveVisibility() !== 'private')
            ->values();

        if ($visible->isEmpty()) {
            return null;
        }

        $first = $visible->first();
        $prefecture = $first->spot->prefecture;

        // 2件以上（まとめて登録）：釣り場名は入れず、件数だけ。行き先はその県のフィード
        if ($visible->count() > 1) {
            return [
                'notification' => $notification,
                'text' => "{$prefecture}で釣果が{$visible->count()}件公開されました",
                'url' => route('feed', ['prefecture' => $prefecture]),
            ];
        }

        // 1件：全体公開なら釣り場名を入れる。「釣り場だけ隠す」なら県だけ（FN-12）
        $text = $first->effectiveVisibility() === 'public'
            ? "{$prefecture}の{$first->spot->name}で釣果が公開されました"
            : "{$prefecture}で釣果が公開されました";

        return [
            'notification' => $notification,
            'text' => $text,
            'url' => route('trips.show', $first),
        ];
    }

    private function forSpot(DatabaseNotification $notification, Collection $spots): ?array
    {
        $spot = $spots->get($notification->data['spot_id'] ?? null);

        // 消された・非公開になった釣り場は出さない
        if (! $spot || $spot->visibility !== 'public') {
            return null;
        }

        return [
            'notification' => $notification,
            'text' => "{$spot->prefecture}に新しい釣り場「{$spot->name}」が登録されました",
            'url' => route('spots.show', $spot),
        ];
    }
}
