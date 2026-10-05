<?php

/*
 * ナビのインデックス付箋（#108）
 * ページのまとまりごとに、付箋の色（tab）・本文の紙の色（paper）・見出しの下線の色（line）を決める
 * - routes：このまとまりに入るルートの名前（開いているページの付箋を手前に出すのに使う）
 * - link：付箋を押したときに開くルート
 * - who：guest＝だれでも／auth＝ログインした人／admin＝管理者だけ
 * - icon：付箋に付けるアイコンの名前（components/icon.blade.php。#112）
 * どれにも入らないページ（トップ・お知らせ・ヒートマップ・プロフィールなど）は、メモ帳の紙の色のまま
 * 色はどれも、黒い字とのコントラストが付箋 9 以上・紙 13 以上（補足の灰色は紙の上で 4.9 以上）
 */
return [
    'dashboard' => [
        'label' => 'ダッシュボード',
        'icon' => 'notebook',
        'link' => 'dashboard',
        'routes' => ['dashboard'],
        'who' => 'auth',
        'tab' => '#FFE58A',
        'paper' => '#FFF8D6',
        'line' => '#D9A400',
    ],
    'planner' => [
        'label' => '釣行プランナー',
        'icon' => 'moon',
        'link' => 'planner',
        'routes' => ['planner'],
        'who' => 'auth',
        'tab' => '#FFC9A8',
        'paper' => '#FFF0E6',
        'line' => '#E0572B',
    ],
    'spots' => [
        'label' => '釣り場',
        'icon' => 'lighthouse',
        'link' => 'spots.index',
        'routes' => ['spots.*'],
        'who' => 'guest',
        'tab' => '#C8E6B0',
        'paper' => '#F0F8E8',
        'line' => '#5E9E3A',
    ],
    'trips' => [
        'label' => '釣行',
        'icon' => 'hook',
        'link' => 'trips.index',
        'routes' => ['trips.*'],
        'who' => 'auth',
        'tab' => '#B9DDF0',
        'paper' => '#EAF5FB',
        'line' => '#2F86B5',
    ],
    'feed' => [
        'label' => '釣果フィード',
        'icon' => 'fish',
        'link' => 'feed',
        'routes' => ['feed'],
        'who' => 'guest',
        'tab' => '#F9BCCB',
        'paper' => '#FDEEF2',
        'line' => '#D9577A',
    ],
    'admin' => [
        'label' => '報告',
        'icon' => 'flag',
        'link' => 'admin.reports.index',
        'routes' => ['admin.*'],
        'who' => 'admin',
        'tab' => '#D5C8F0',
        'paper' => '#F3EFFB',
        'line' => '#7B5CC4',
    ],
];
