# データベース（ER 図）

## 文書の情報

| 項目 | 内容 |
|---|---|
| 文書名 | FishingLog データベース（ER 図） |
| 版 | 1.0 |
| 作成日 | 2026-10-24（README から移した。図は #200 で Mermaid にしたもの） |
| 最終更新日 | 2026-10-24 |
| 作成者 | kazuyuki-a-dev |

## 改訂履歴

| 版 | 日付 | 変更内容 | Issue |
|---|---|---|---|
| 1.0 | 2026-10-24 | README の「データベース（ER 図）」をこの資料に移した | #221 |

---

アプリで使うテーブルは8つです（users・spots・trips・catches・reports・notifications・password_reset_tokens・spot_local_info_changes）。ほかに、Laravel が最初から作るセッション・キャッシュ・ジョブ用のテーブルがあります。各テーブルの列の説明は、定義書（[`docs/spec.md`](spec.md)）のテーブル一覧とマイグレーション（`database/migrations/`）にあります。

```mermaid
erDiagram
    users ||--o{ trips : "記録する"
    trips ||--o{ catches : "の釣果"
    spots ||--o{ trips : "で釣る"
    users ||--o{ spots : "登録する（created_by）"
    users |o--o{ spots : "現地の情報を最後に更新（updated_by）"
    spots ||--o{ spot_local_info_changes : "の変更の履歴"
    users |o--o{ spot_local_info_changes : "が変えた"
    users ||--o{ reports : "報告する（reporter_id）"
    trips |o--o{ reports : "を報告"
    spots |o--o{ reports : "を報告"
    users ||--o{ notifications : "受け取る（notifiable）"
    users ||..o{ password_reset_tokens : "メールアドレスでつながる"

    users {
        bigint id PK
        varchar name "ニックネーム"
        varchar home_prefecture "メインフィールド（県）"
        boolean notify_enabled "お知らせ ON／OFF"
        varchar role "user／admin"
        boolean is_sample "ダミーの人"
        varchar email UK
        varchar password "ハッシュ化"
    }
    spots {
        bigint id PK
        bigint created_by FK "登録した人"
        varchar name
        varchar prefecture
        decimal latitude "正確な位置は本人だけ"
        decimal longitude
        varchar visibility "public／private"
        varchar caution_type "注意区分"
        varchar parking_type "駐車場"
        varchar toilet_available "トイレ"
        int convenience_distance_m "コンビニまで（m）"
        text facility_note "現地のメモ"
        text notes "本人だけのメモ"
        bigint updated_by FK "現地の情報を最後に更新した人"
    }
    trips {
        bigint id PK
        bigint user_id FK
        bigint spot_id FK
        datetime went_at
        varchar time_of_day "時間帯"
        varchar visibility "public／spot_hidden／private"
        varchar tide "潮"
        varchar weather "天候"
        text notes "本人だけのメモ"
    }
    catches {
        bigint id PK
        bigint trip_id FK
        varchar fish_species "魚種"
        varchar method "エサ／ルアー"
        decimal length_cm
        int weight_g
        varchar image_path "Exif を消した写真"
    }
    reports {
        bigint id PK
        bigint reporter_id FK
        bigint trip_id FK "どちらか一方"
        bigint spot_id FK "どちらか一方"
        varchar reason
        varchar status "未対応／確認済み／対応完了"
    }
    notifications {
        uuid id PK
        varchar notifiable_type
        bigint notifiable_id "受け取る会員"
        text data "釣行・釣り場の番号だけ"
        timestamp read_at
    }
    spot_local_info_changes {
        bigint id PK
        bigint spot_id FK
        bigint user_id FK "退会したら空"
        varchar field "変えた項目"
        text old_value "前の値"
        text new_value "後の値"
        varchar action "create／edit／revert"
        bigint reverted_change_id FK "戻した元の変更（この表の id）"
        timestamp created_at
    }
    password_reset_tokens {
        varchar email PK
        varchar token
        timestamp created_at
    }
```

- 図は Mermaid（文字で書く図）で、GitHub がそのまま絵にして表示します。テーブルを足したら、この図も直します（直し忘れは `ErDiagramDocTest` で気づけます）
- 説明に要る列だけを載せています。省いた列：`created_at`・`updated_at`、`spots.parking_note`・`toilet_note`・`notified_at`、`trips.notified_at`、`catches.method_detail`・`notes`、`reports.detail`、`users.email_verified_at`・`remember_token` など
- 線の見方：`||--o{` は「1 対 たくさん」、`|o--o{` は「つながりが空のこともある（退会したら空になる、など）」、点線は外部キーの制約を張っていない（メールアドレスでつながる）つながり
- `notifications.data` には文章を保存せず、釣行・釣り場の番号だけを保存し、表示するたびにその時点の公開範囲をチェックして文章を作ります（あとで非公開に戻しても、釣り場名が残らないように）
- `spot_local_info_changes.reverted_change_id` は、管理者が戻したときの「戻した元の変更」（同じ表の id）です（#200）
- 設計のとき（2026-09-25）に draw.io で描いた初版の図は、Git の履歴（`docs/images/er-diagram.png`、2026-10-23 まで）にあります
