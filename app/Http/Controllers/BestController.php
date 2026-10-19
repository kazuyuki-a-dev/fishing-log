<?php

namespace App\Http\Controllers;

use App\Services\PersonalBests;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 魚種ごとの自己ベストのページ（#170）。自分の記録だけ
 */
class BestController extends Controller
{
    public function index(Request $request, PersonalBests $bests): View
    {
        return view('bests.index', [
            'bests' => $bests->forUser($request->user()),
        ]);
    }
}
