<?php

namespace App\Providers;

use App\Models\Report;
use App\Services\NotificationPresenter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // どの画面のナビにも、ベルマークの未読件数を渡す（FN-18）
        View::composer('layouts.navigation', function ($view) {
            $user = auth()->user();
            $view->with('unreadNotifications', $user ? app(NotificationPresenter::class)->unreadCount($user) : 0);
            // 管理者のナビには、未対応の報告の件数を出す（NF-04）。管理者でなければ null（リンクも出さない）
            $view->with('openReports', $user?->isAdmin() ? Report::where('status', 'open')->count() : null);
        });

        // インデックス付箋（#108）。見せてよい付箋の一覧と、今開いているページの付箋（なければ null）
        View::composer(['layouts.app', 'layouts.navigation'], function ($view) {
            $user = auth()->user();
            $sections = collect(config('sections'))->filter(fn(array $section) => match ($section['who']) {
                'guest' => true,
                'auth' => $user !== null,
                'admin' => (bool) $user?->isAdmin(),
            });
            $view->with('sections', $sections);
            $current = $sections->search(fn(array $section) => request()->routeIs(...$section['routes']));
            $view->with('currentSection', $current === false ? null : $current);
        });
    }
}
