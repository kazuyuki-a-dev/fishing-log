<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * 同じ県に釣り場が登録されたお知らせ（FN-18）
 * 文章は保存せず、釣り場の番号だけを保存する。文章は表示するたびに NotificationPresenter が作る
 */
class NewSpotNotification extends Notification
{
    public function __construct(private int $spotId) {}

    // アプリの中だけ（notifications テーブル）。メールは送らない
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['spot_id' => $this->spotId];
    }
}
