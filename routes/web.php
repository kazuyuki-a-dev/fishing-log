<?php

use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\AnalysisController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SpotController;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ログインした人だけ
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // /spots/nearby と /spots/create は、カルテ（/spots/{spot}）より先に書く
    Route::get('/spots/nearby', [SpotController::class, 'nearby'])->name('spots.nearby');
    Route::resource('spots', SpotController::class)->only(['create', 'store', 'edit', 'update']);
    Route::patch('/spots/{spot}/local-info', [SpotController::class, 'updateLocalInfo'])->name('spots.local-info');

    // CSV 出力（PG17）。/trips/{trip} より先に書く（export が釣行の番号と間違えられないように）
    Route::get('/trips/export', [TripController::class, 'export'])->name('trips.export');
    // 過去の釣行のまとめて登録の保存（PG15）
    Route::post('/trips/bulk', [TripController::class, 'storeBulk'])->name('trips.bulk-store');
    Route::resource('trips', TripController::class);
    Route::get('/planner', [PlannerController::class, 'index'])->name('planner');

    // シーズンヒートマップ（PG16）
    Route::get('/analysis/heatmap', [AnalysisController::class, 'heatmap'])->name('analysis.heatmap');

    // 県内新着のお知らせ一覧（PG25）
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // 不適切な投稿の報告（PG21）。画面はなく、カルテと釣行詳細のモーダルから送る
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');

    // 報告を確かめて対応する管理画面（NF-04）。管理者だけ（ほかの人には 404）
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::patch('/reports/{report}', [AdminReportController::class, 'update'])->name('reports.update');
        Route::post('/reports/{report}/hide', [AdminReportController::class, 'hide'])->name('reports.hide');
    });
});

// ゲストも見られる（NF-01・FN-13）。ログインが必要なルートより下に書く
Route::resource('spots', SpotController::class)->only(['index', 'show']);
Route::get('/feed', [FeedController::class, 'index'])->name('feed');
Route::get('/terms', [StaticPageController::class, 'terms'])->name('terms');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::get('/guide', [StaticPageController::class, 'guide'])->name('guide');
Route::get('/tools/converter', [StaticPageController::class, 'converter'])->name('tools.converter');

require __DIR__ . '/auth.php';
