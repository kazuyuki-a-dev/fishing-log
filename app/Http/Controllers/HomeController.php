<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // ログイン中の人はダッシュボードへ
        if ($request->user()) {
            return redirect()->route('dashboard');
        }

        $trips = Trip::forFeed()
            ->with(['spot', 'user:id,name', 'catches'])
            ->latest('went_at')
            ->latest('id')
            ->take(5)
            ->get();

        return view('home', compact('trips'));
    }
}
