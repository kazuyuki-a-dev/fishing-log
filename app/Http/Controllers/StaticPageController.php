<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    public function terms(): View
    {
        return view('static.terms');
    }

    public function privacy(): View
    {
        return view('static.privacy');
    }

    /** アプリの使い方（#116）。だれでも見られる */
    public function guide(): View
    {
        return view('static.guide');
    }

    /** 単位変換ツール（#122）。だれでも使える。計算はブラウザの中だけで、何も保存しない */
    public function converter(): View
    {
        return view('tools.converter', ['units' => config('units')]);
    }
}
