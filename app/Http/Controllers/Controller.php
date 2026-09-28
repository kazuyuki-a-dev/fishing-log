<?php

namespace App\Http\Controllers;

use Illuminate\Support\Carbon;

abstract class Controller
{
    /**
     * URL の日付（2026-09-28 の形）を読み取る。指定がない・おかしい値なら今日
     */
    protected function dateFromQuery(?string $value): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d', (string) $value, 'Asia/Tokyo')->startOfDay();
        } catch (\Throwable) {
            return Carbon::today('Asia/Tokyo');
        }
    }
}
