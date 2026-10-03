<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * 同じ県で釣行が公開されたお知らせ（FN-18）
 * 文章は保存せず、釣行の番号だけを保存する。文章は表示するたびに NotificationPresenter が作る
 * まとめて登録のときは、番号が何件も入る
 */
class NewTripNotification extends Notification
{
    /** @param array<int> $tripIds */
    public function __construct(private array $tripIds) {}

    // アプリの中だけ（notifications テーブル）。メールは送らない
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['trip_ids' => $this->tripIds];
    }
}
