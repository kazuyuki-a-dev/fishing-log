# FishingLog（釣果記録・釣り場情報共有アプリ）

スクールに提出する個人開発アプリ。定義書は `docs/spec.md`（元は Excel「フィッシングログ.xlsx」）。
定義書と違うことをしたときの判断と理由は `docs/decisions.md` にすべて記録してある。作業の前に両方を読むこと。

## ユーザーについて（いちばん大事）

- 開発の初心者。**日本語で、やさしい言葉で**説明する。専門用語を使うときは一言で意味を添える
- 「1つずつ解説しながら、定義書に沿って作りたい」。コードを書いたら、**何を・なぜ**そうしたかを短く説明する。提出のときに自分で説明できるようになることが目的
- 定義書と違いが出るときは、**勝手に決めずに選択肢と理由を出して、ユーザーに決めてもらう**。決めたら `docs/decisions.md` に書く
- 開発の過程は、すべてログが残るようにする（Issue・PR・コミット・decisions.md）
- コミット（`git add .` → `git commit`）のタイミングは任されている。切りのいいところで行い、**毎回「ここでコミットします」と伝える**
- Issue と PR は、ユーザーが **GitHub のブラウザ画面で** 作る。Claude は、Issue はタイトルと本文、**PR は本文だけ**を出す（PR のタイトルは GitHub の自動入力を使う）。gh コマンドで作りたい場合は、ユーザーに確認してから

## 開発の流れ（毎回この順番）

1. Issue のタイトルと本文を出す → ユーザーがブラウザで作り、**画面で番号を確かめる**（Issue と PR は同じ番号の列を使うので、番号は予想しない）
2. `git switch -c feature/<番号>-<短い英語の名前>`（`#` や `<>` は付けない）
3. 作る → テスト（`sail artisan test`）→ 画面で確かめてもらう
4. コミット。メッセージは日本語で、最後に `(#番号)`。種類を付ける：`feat:` 機能 / `fix:` 直し / `test:` テスト / `docs:` 記録 / `chore:` その他
   - 順番は「機能のコミット → テストのコミット → 記録（decisions.md）のコミット」
5. `docs/decisions.md` の表に追記（列：日付 | 項目 | 定義書 | 決定 | 理由）
6. **push の前に `git status`**（コミットし忘れを防ぐ。#61 でテストと記録を入れ忘れたことがある）→ `git push -u origin <ブランチ名>`
7. PR の本文だけを出す（変更内容／定義書との違い／確認したこと／`Closes #番号`）。**Blade の `@js` `@php` などは本文の中で ` で囲む**（`@名前` は GitHub のメンションになる）
8. ユーザーが「Create a merge commit」でマージしてブランチを削除 → `git switch main && git pull && git branch -d <ブランチ名>` → `git status` で `working tree clean`

## 環境

- WSL2（Ubuntu）＋ Docker ＋ Laravel Sail。PHP 8.5 / Laravel 13 / MySQL 8.4
- Breeze（Blade 版）＋ Alpine.js ＋ Tailwind CSS v3 ＋ Vite
- コマンドは `sail` を通す（例：`sail artisan test`、`sail npm run build`）
- `.env` は `APP_URL=http://localhost`（`:8000` にすると写真が表示されない）。`.env` はコミットしない
- 外部サービス：地図は Leaflet ＋ OpenStreetMap（帰属表示が必要）、天気は Open-Meteo（キー不要）
- 写真：Intervention Image v4（GD）で描き直して Exif を消す。ブラウザ側は ExifReader（撮影日時・位置の読み取り）と heic-to（HEIC→JPEG）。どちらも必要なときだけ動的 import

## 絶対にしないこと・気をつけること

- **`sail down -v` は使わない**（データベースが消える）。止めるときは `sail stop`
- `sail artisan migrate:fresh --seed` は今のデータを全部消す。**打つ前に `scripts/backup.sh`** を勧め、ユーザーに確認する
- テスト用のメールアドレスは `example.com`
- `config/prefectures.php`（47都道府県の名前の一覧）は書き換えない。県の中心座標は `config/prefecture_centers.php`
- Blade の `@js()` の中に長い式を書かない。`@php` で変数にしてから渡す（入れ子のかっこで JavaScript の文法エラーになったことがある）
- Alpine の `x-for` の中身は、1つの要素にまとめる
- ルートは上から順に当てはめられる。同じ URL の古い定義が残っていないか気をつける（`/spots/nearby` が 404 になったことがある）
- テストでは外の API に本当につながない（`tests/TestCase.php` で `Http::preventStrayRequests()`。天気は `Http::fake`）
- VS Code（Intelephense）の赤線は勘違いのことが多い（`assertExists` など）。`Storage::fake` は `/** @var \Illuminate\Filesystem\FilesystemAdapter $disk */` を付けると消える
- 見本コードを出すときは「（…今のまま）」のような省略を入れない。ユーザーがそのまま貼ってしまうことがある
- ボタンの位置：フォームの決定ボタンは右寄せ（`justify-end`）。説明の文と並ぶ大きなボタンは `w-full sm:w-auto`。アカウント削除だけは左のまま
- 新しい画面部品は、スマホ幅（360px）でも見えるか確かめる（ナビの `hidden sm:flex` の中だけに置いて、スマホでベルマークが消えたことがある）
- 説明のコメントは Blade コメント `{{-- --}}` で書く。HTML コメント `<!-- -->` は画面のソースに出るので、テストの `assertDontSee` に引っかかることがある
- 写真が 403 になるときは、まずファイルがあるかを見る（`storage/app/public/catches`）。`restore.sh` で DB と写真の時点がずれると、DB にだけ写真が残る

## 公開範囲とプライバシー（このアプリの肝。どの機能でも必ず守る）

- 釣行の公開範囲は3段階：`public`（全体公開）／`spot_hidden`（釣り場だけ隠す）／`private`（非公開、初期値）
- **閉じているほうを優先**：釣り場が非公開なら、釣行が `public` でも `spot_hidden` として扱う（`Trip::effectiveVisibility()`）
- 「釣り場だけ隠す」の釣り場名・位置は**サーバー側で取り除いてから**画面に渡す
- 公開する釣り場の位置は、緯度経度を小数第2位で**切り捨て**（約1km四方）。正確な位置は登録した本人だけ（`Spot::locationFor()`）
- 選べる・提案する釣り場は「公開の釣り場」と「自分の釣り場」だけ（スコープ `visibleTo`）
- 写真の Exif は、保存時に必ず消す（`App\Services\PhotoStorer`）
- 画面以外からデータが出る場所（お知らせ・CSV・API の JSON）でも、同じチェックを必ずする（NF-01）
- お知らせには釣行・釣り場の**番号だけ**を保存し、表示するたびに `NotificationPresenter` でその時点の公開範囲をチェックして文章を作る（一覧もベルの件数も、ここ1か所を通す）

## 主なファイル

- ルート：`routes/web.php`（ログインが必要なグループ → ゲストも見られる `spots` の index/show・`/feed`・`/terms`・`/privacy`）
- コントローラ：`TripController`（store・storeBulk・update・`withConditions()` で潮と天候）、`SpotController`（show に判断ビュー・`nearby()`・`updateLocalInfo()`）、`DashboardController`（サマリー指標・継続カウンタと気づきカード）、`HomeController`、プランナー、`NotificationController`（お知らせ一覧。開いたら全部既読）、`ReportController`（報告を受け取る）、`Admin\ReportController`（管理画面：一覧・状態の変更・非表示）、`AnalysisController`（ヒートマップ）
- サービス：`app/Services/` の `TideCalculator`（旧暦から潮）、`WeatherService`（Open-Meteo、90日より前は archive API、予報は1時間キャッシュ）、`CatchHighlighter`（登録直後のハイライト）、`PhotoStorer`、`NewPostNotifier`（お知らせを送る。更新では、更新前が非公開のときだけ）、`NotificationPresenter`（お知らせの文章づくりと公開範囲のチェック）、`TripSearch`（釣行一覧の条件検索。条件の読み取り・絞り込み・まとめ。CSV でも同じものを使う）、`TripCsvExporter`（CSV の行。位置と写真は入れない、式の対策）、`ConditionMatcher`（ぴったり → 潮だけ → 月だけ と条件をゆるめる。プランナーとカルテの判断ビューで共通）、`SeasonHeatmap`（月×魚種の匹数と回数。`build()` はヒートマップ、`forTrips()` はカルテ。マスの決まりは `rows()` の1か所）、`KeepRecording`（継続カウンタと気づきカード）
- お知らせ：`app/Notifications/` の `NewTripNotification`（`trip_ids`）・`NewSpotNotification`（`spot_id`）。ベルは `resources/views/components/notification-bell.blade.php`（PC とスマホの両方で使う）
- 一致レベルのバッジ：`resources/views/components/match-level.blade.php`
- 月×魚種の表：`resources/views/components/season-table.blade.php`（ヒートマップとカルテの両方で使う。`highlight-month` で月に印）
- 入力チェック：`app/Http/Requests/` の `TripRequest`・`BulkTripRequest`・`SpotRequest`・`ReportRequest`（エラーは `report` の袋）
- コマンド：`app/Console/Commands/MakeAdmin.php`（`app:make-admin`）
- 決まった言葉の一覧：`config/fishing.php`（魚種20種・時間帯・潮・天候・釣り方・公開範囲など）
- JavaScript：`resources/js/app.js`（`spotMapInput`・`spotMapView`・`catchRows`・`bulkRows`）、`resources/js/photo-hints.js`
- シーダー：`database/seeders/DatabaseSeeder.php`（秋田：ユーザー4・釣り場8・釣行50、神奈川：1・2・6。`fake()->seed(2026)` で毎回同じ）
- バックアップ：`scripts/backup.sh`・`scripts/restore.sh`・`docs/backup.md`（`/backups` は Git に入れない）

## 開発用のログイン

- `test@example.com` / `password`（秋田、釣行15件）
- 管理者：`admin@example.com`（東京都、釣行なし。今の開発用データベースにも足してある）
- ほかに `minato@` `surf@` `iso@`（秋田）、`wanoku@`（神奈川）。すべて `@example.com`、パスワードは `password`

## 今どこまでできているか（2026-10-05）

**フェーズ1・フェーズ2は完了。** フェーズ3は条件検索（FN-03、#86）・CSV 出力（FN-04、#88）・継続カウンタと気づきカード（FN-10、#90）が完了。報告と管理画面（NF-04、#92・#94）が完了。見た目も土台（#96）・カルテ（#98）・ダッシュボードとプランナー（#100）・残りの画面（#102）でひととおり完了。テストは 263 件すべて成功。最後の Issue は #102（PR と次の番号は画面で確かめる）。

### フェーズ2でやったこと（決めたことは `docs/decisions.md`）

- 県内新着のアプリ内通知（FN-18、#76）：番号だけ保存し、表示のたびに公開範囲をチェック
- シーズンヒートマップ（FN-02、#80）：色は匹数・小さく回数、1匹から色、最初は「みんな」。FN-10 の「あと何件」は「釣行 N 件から作っています」に変えた
- カルテの小さな表（PG07、#82）：履歴と同じ釣行（「釣り場だけ隠す」は数えない）、選んだ日付の月に印
- 条件をゆるめて探す（FN-16、#84）：釣り場ごとに、釣れた記録がなければ ぴったり → 潮だけ → 月だけ。レベルが先に並ぶ。カルテの判断ビュー（FN-11）も同じ

### フェーズ3の進み具合（順番はユーザーと決めた）

- 済：① 条件検索（FN-03、#86）：釣行一覧（`/trips`）に絞り込み・「自分／みんな」・まとめ。**「みんな」で釣り場を指定したら「釣り場だけ隠す」は出さない**。魚種×釣り方は同じ1匹。条件は URL に残す
- 済：② CSV 出力（FN-04、#88、`/trips/export`）：今の絞り込みのまま**自分のデータだけ**（URL で scope=public でも自分だけ）。魚1匹＝1行、BOM 付き、位置と写真は入れない。ルートは `/trips/{trip}` より上
- 済：③ 継続カウンタと気づきカード（FN-10、#90、`KeepRecording`）：ダッシュボードのサマリー指標の下。自分の釣行の回数だけ（坊主も1回）。連続月数は今月が終わるまで途切れにしない。気づきは潮・時間帯・天候ごとに釣れた割合がいちばん高いもの（2回以上行った条件だけ、最大3枚、件数を出す）
- ④ 報告と管理画面（NF-04）：2つの Issue に分けた
  - 済：報告を送る（#92）：`users.role`（user／admin）、`reports` テーブル、カルテと釣行詳細のモーダル（`reports/partials/modal.blade.php`）、ポリシーの `report`（見えないものは 404、自分の投稿は 403）、未対応の重複は不可。管理者は `sail artisan app:make-admin メール` とシーダーの `admin@example.com`
  - 済：管理画面（#94、`/admin/reports`、`Admin\ReportController`）：ミドルウェア `admin`（`EnsureAdmin`、管理者以外は 404）。最初は未対応だけ。投稿の中身は管理画面の中に出す（**メモは出さない**、釣り場名は出す）。**非表示は公開範囲を `private` に変える**（新しい列は足さない）＋その投稿への報告をまとめて対応完了。釣り場の updated_at は変えない
- ⑤ 見た目：**クレヨンのメモ帳風**（見本 https://claude.ai/artifact/SJXjKegQq9FzjDHpsj9Br4 を見比べてユーザーが決めた）
  - 済：土台（#96）：紙の背景 `crayon-paper`・カード `crayon-card`（::after にクレヨンの枠）・見出し `crayon-heading`（手書き＋クレヨンの下線）・`crayon-subheading`。SVG フィルターは `components/crayon-filters.blade.php`（レイアウトで1回読み込む）。手書きの字は Yomogi（`font-hand`）で、ナビ・ボタン・選ぶ部品・入力欄・ラベルにも当てた（`app.css` の `@layer base`、`<a>` のボタンは class に `font-hand`）。本文・カードの数字・注意の警告は BIZ UDPゴシックのまま。`sand` は #5B6B70
  - 済：カルテ（#98、見本 https://claude.ai/artifact/SRU3JbH7hHtHiHK53BgCj5 の C「付箋と書き込み」）。共通クラス `crayon-sticky`（＋`-yellow` `-pink` `-blue`）・`crayon-caution`（＋`crayon-caution-icon`）・`crayon-button`・`crayon-check`・`crayon-underline`
    - **ボタンはぬり絵風**（うすいオレンジ `float-light` のむらのない塗り＋濃いオレンジのクレヨンのふち、字は黒）。**字の後ろにクレヨンのまだら（crayon-fill）を置かない**（字が点にまぎれて読めない。何度も試してだめだった）
    - **「✔」などの記号の字は使わない**（Windows で色付きの絵文字になる）。CSS の線で描く
  - 済：ダッシュボードとプランナー（#100、見本 https://claude.ai/artifact/XHVi3DDLDE3vPTaHEqSYot の B「書き込みとスタンプ」）。共通クラス `crayon-ledger`（点線の表）・`crayon-stamp`（＋`crayon-stamp-empty`）・`crayon-marker`・`crayon-scrawl`・`crayon-caution-tag`。白い字のオレンジのボタンは全部 `crayon-button` に（`primary-button` の部品も）
  - 済：残りの画面（#102）。今の部品でそろえた。共通クラス `crayon-button-secondary`（白い塗り＋青いふち）・`crayon-note`（水色のメモ。お知らせの帯・説明や案内の箱）。目立たせたいしるし（「ぴったり一致」・NEW・未対応）は `bg-crayon-pink text-ink`。見出しは全部 `crayon-heading` / `crayon-subheading`
  - **新しい画面部品を作るとき**：カード `crayon-card`、ボタン `crayon-button`（2番目は `crayon-button-secondary`）、案内 `crayon-note`、注意 `crayon-caution`、付箋 `crayon-sticky`。オレンジの地に白い字は使わない（3.8 で足りない）
- **次はここから：README**（下の「README」の項目。全部終わったので書き直す）。そのあと、ユーザーと残りを相談
  - 見た目の確認：作業用の場所（scratchpad）に確認用の HTML を作り、Playwright の chrome-headless-shell で画像にして自分でも見る（足りないライブラリ libnspr4・libnss3・libasound は apt-get download で scratchpad に取り出し、LD_LIBRARY_PATH で使う。システムには入れない）

### フェーズ3でやること

- 条件検索（FN-03）・CSV 出力（FN-04、自分のデータだけ）・継続カウンタと気づきカード（FN-10）・不適切な投稿の報告と最小の管理画面（NF-04、`users.role`）
- 最後に見た目。ユーザーは**オリジナリティを出したい** → クレヨンのメモ帳風に決めた。クレヨンの効果は飾り（::before / ::after）にだけかけ、文字・数字・表の線にはかけない。新しいカードは `crayon-card`、見出しは `crayon-heading` / `crayon-subheading` を使う。数字や注意の読みやすさと、素材の権利には気をつける

### README

- フェーズ2・3で変わるので、**全部終わってから**まとめて書き直す（今は Laravel の初期文章のまま）。提出がその前なら先に書く
- 書くこと：アプリの説明、セットアップ（`sail up -d` → `sail artisan migrate --seed` → `sail artisan storage:link` → `sail npm install` → `sail npm run build`）、`APP_URL` の注意、`sail down -v` を使わない、シーダーのログイン、外部サービス、テストの動かし方、バックアップ（`docs/backup.md`）、開発の記録（`docs/decisions.md`）
