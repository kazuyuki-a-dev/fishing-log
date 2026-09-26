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
}
