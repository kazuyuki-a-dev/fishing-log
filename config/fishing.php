<?php

return [
    // 釣り場
    'spot_visibility' => ['public', 'private'],
    'caution_types' => ['立入注意', '私有地隣接', '注意あり', 'なし'],
    'parking_types' => ['公式駐車場', '路肩等', '駐車不可', '不明'],
    'toilet_available' => ['あり', 'なし', '不明'],

    // 釣行
    'trip_visibility' => ['public', 'spot_hidden', 'private'],
    'times_of_day' => ['朝マズメ', '日中', '夕マズメ', '夜', '終日'],
    'tides' => ['大潮', '中潮', '小潮', '長潮', '若潮'],
    'weathers' => ['晴れ', '曇り', '雨', '雪'],

    // 釣果
    'fish_species' => [
        'アジ',
        'サバ',
        'イワシ',
        'カマス',
        'タチウオ',
        'シーバス',
        'ヒラメ',
        'マゴチ',
        'キス',
        'マダイ',
        'クロダイ',
        'メジナ',
        'メバル',
        'カサゴ',
        'アイナメ',
        'カワハギ',
        'ブリ',
        'サワラ',
        'アオリイカ',
        'マダコ',
    ],
    'methods' => ['エサ', 'ルアー'],
    // 報告の理由（テーブル5・PG21）
    'report_reasons' => ['不適切な内容', '誤った釣り場情報', '危険を招く情報', 'その他'],
    // 報告の対応状況（テーブル5）
    'report_statuses' => [
        'open' => '未対応',
        'reviewed' => '確認済み',
        'closed' => '対応完了',
    ],
    // 公開範囲の表示名
    'visibility_labels' => [
        'public' => '全体公開',
        'spot_hidden' => '釣り場だけ隠す',
        'private' => '非公開',
    ],
];
