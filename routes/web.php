<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaticPageController;
use App\Http\Controllers\SpotController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('spots', SpotController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::resource('trips', TripController::class);
    Route::get('/planner', [PlannerController::class, 'index'])->name('planner');
    Route::resource('spots', SpotController::class)->only(['create', 'store', 'edit', 'update']);
});

Route::resource('spots', SpotController::class)->only(['index', 'show']);
Route::get('/feed', [FeedController::class, 'index'])->name('feed');
Route::get('/terms', [StaticPageController::class, 'terms'])->name('terms');
Route::get('/privacy', [StaticPageController::class, 'privacy'])->name('privacy');
Route::resource('spots', SpotController::class)->only(['index', 'show']);

require __DIR__ . '/auth.php';
