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

    // 写真を保存する場所（#136）。開発はサーバーの中のフォルダ（public）、公開環境は S3 の形の保存先（s3。Cloudflare R2）
    // 公開先（Render の無料枠）は、止まるたびにサーバーの中のファイルが消えるため
    'photo_disk' => env('PHOTO_DISK', 'public'),

    // お試し用の専用アカウント（#136）。だれでもログインできるので、メール・パスワードの変更と退会はできないようにする
    'demo_email' => env('DEMO_EMAIL', 'demo@example.com'),
    // お試し用アカウントのパスワード。README に書く公開用のもの（本物の情報は入れないアカウント）
    'demo_password' => env('DEMO_PASSWORD', 'fishinglog-demo'),

    // お試しデータを入れ直す合図（#160）。公開環境の Render の環境変数に好きな文字を入れて公開し直すと、
    // 起動のときにお試しデータと写真を消して入れ直す。同じ合図では1回だけ（app:reset-demo-if-requested）
    'demo_reseed_token' => env('DEMO_RESEED_TOKEN'),
    // 公開環境の管理者のパスワード（秘密。Render の環境変数にだけ入れる）。なければランダムにする
    'admin_password' => env('ADMIN_PASSWORD'),
];
