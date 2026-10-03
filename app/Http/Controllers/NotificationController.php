<?php

namespace App\Http\Controllers;

use App\Services\NotificationPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * お知らせ一覧（PG25・FN-18）
     * 開いたら全部既読にする。今回初めて見るものには「NEW」を付ける
     */
    public function index(Request $request, NotificationPresenter $presenter): View
    {
        $user = $request->user();

        // 既読にする前に、どれが未読だったかをメモしておく（NEW の印に使う）
        $newIds = $user->unreadNotifications()->pluck('id')->all();
        $user->unreadNotifications()->update(['read_at' => now()]);

        $notifications = $user->notifications()->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            // 今の公開範囲で文章を作る。見せられなくなったものは、ここで外れる
            'items' => $presenter->present($notifications->getCollection()),
            'newIds' => $newIds,
            'notifyEnabled' => $user->notify_enabled,
        ]);
    }
}
