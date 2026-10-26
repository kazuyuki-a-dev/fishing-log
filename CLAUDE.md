# FishingLog（釣果記録・釣り場情報共有アプリ）

釣行を記録すると「どの潮・時間帯・釣り方なら何回行って何回釣れたか」が貯まり、次の行き先を決める材料になる Web アプリ。主役は釣行プランナー（FN-16）と釣り場カルテの釣行判断ビュー（FN-11）。

## 資料（作業の前に読む）

- 定義書：`docs/spec.md`（元は Excel「フィッシングログ.xlsx」）
- 定義書と違うこと・書いていないことを決めたときの判断と理由：`docs/decisions.md`
- 要件ごとの優先度・状態・確かめ方・結果：`docs/requirements.md`
- ER 図 `docs/database.md`、開発環境の手引き `docs/development.md`、運用 `docs/operations.md`、本番リリース計画 `docs/release-plan.md`、バックアップ `docs/backup.md`
- これまでの経緯は Git の記録・PR・`docs/decisions.md` にある（ここには書かない）

## よく使うコマンド

- コマンドは Sail を通す：`./vendor/bin/sail artisan test`（テスト）、`./vendor/bin/sail npm run build`（画面の部品を作る）
- 止めるときは `sail stop`。**`sail down -v` は使わない**（データベースが消える）
- `sail artisan migrate:fresh --seed` は今のデータを全部消す。**打つ前に `scripts/backup.sh`** を勧め、ユーザーに確認する
- ダミーの入れ直し：`sail exec -u sail -e DEMO_RESEED_TOKEN=好きな文字 laravel.test php artisan app:reset-demo-if-requested`（**`-u sail` を忘れると root で動き、写真のフォルダに書き込めなくなる**）
- 管理者にする：`sail artisan app:make-admin メールアドレス`

## 作業の流れ

1. Issue を作る（ユーザーの依頼で `gh issue create`）。番号は作ったあとに確かめる（予想しない）
2. `git switch -c feature/<番号>-<短い英語の名前>`
3. 作る → `sail artisan test` → 画面で確かめてもらう（新しい画面部品はスマホ幅 360px でも見る）
4. コミットは「機能（`feat:`／`fix:`）→ テスト（`test:`）→ 記録（`docs:`）」の順。メッセージは日本語で、最後に `(#番号)`。コミットするときは「ここでコミットします」と伝える
5. **push の前に `git status`**（コミットし忘れを防ぐ）→ push → `gh run list --branch <ブランチ名>` で GitHub Actions のテストの結果を見る
6. PR を作る（本文：変更内容／定義書との違い／確認したこと／`Closes #番号`。Blade の `@js` などは ` で囲む）
7. マージはユーザーの指示があってから（「Create a merge commit」）。そのあと `git switch main && git pull && git branch -d <ブランチ名>`、`git status` で clean、Issue が閉じたかを `gh issue view` で確かめる

### 機能や画面を変えたら、あわせて直すもの

- `docs/requirements.md`（状態・テストの名前・確認日。`RequirementsDocTest` が食い違いを見張る）と、README の「機能と優先度」・テストの件数
- 定義書と違う判断をしたら、選択肢と理由をユーザーに出して決めてもらい、`docs/decisions.md` に1行足す（列：日付｜項目｜定義書｜決定｜理由。**前の行の終わりに改行を入れる**。`DecisionsDocTest` が列の数を見張る）
- 使い方のページ `resources/views/static/guide.blade.php`、利用規約・プライバシーポリシー（公開範囲・写真・外部サービス・退会が今の機能と合っているか）
- テーブルを足したら `docs/database.md` の ER 図（`ErDiagramDocTest`）
- ページを足したら `config/sections.php` の routes（ナビの付箋）
- 見た目を大きく変えたら README の画面の画像（`docs/images/screen-*.png`。scratchpad の `shoot.mjs`（Node 24 の WebSocket で chrome-headless-shell を動かす）で撮る。なければ作り直す。足りないライブラリ libnspr4・libnss3・libasound は `apt-get download` で scratchpad に取り出し、`LD_LIBRARY_PATH` で使う。システムには入れない）
- README は入口だけ。詳しいことは README に足さず、docs/ の資料に書いてリンクする
- Issue・PR・コミット・記録・README には「スクール」「レビュー」「提出」「FB」の言葉を書かない

### Dependabot の PR

- 毎週月曜に PR が来る。まず Checks（GitHub Actions のテスト）を見る。画面や写真の読み取りに関わる部品（vite・Tailwind・exifreader・heic-to）や PHP の箱は、手元でも `gh pr checkout` → install／build → テスト → 画面で確かめる
- **Tailwind CSS 4 には上げない**（見た目がくずれる）
- Issue はなく、decisions.md にも書かなくてよい（セキュリティで判断したときだけ書く）。マージすると Render が公開し直す

## 環境

- WSL2＋Docker＋Laravel Sail／PHP 8.5（公開環境の `Dockerfile` は `php:8.5.12-apache` と版まで書く。PHP のセキュリティ修正が出たら上げ、開発環境も `sail build --no-cache`）／Laravel 13／MySQL 8.4／Breeze（Blade）＋Alpine.js＋Tailwind CSS v3＋Vite
- `.env` は `APP_URL=http://localhost`（`:8000` にすると写真が出ない）。`.env` はコミットしない
- 外部サービス：地図 Leaflet＋OpenStreetMap（帰属表示）、カルテの天気予報 MET Norway（User-Agent で名乗る・帰属表示）、釣行の天候 Open-Meteo
- メール：会員登録の確認とパスワード再設定。公開環境は Brevo の SMTP（**ポート 2525**。Render の無料のプランは 587 番が止められている）、開発環境は Mailpit（http://localhost:8025）
- 写真：Intervention Image（GD）で描き直して Exif を消す。ブラウザでは ExifReader・heic-to を必要なときだけ読み込む

## やってはいけないこと・気をつけること

- `config/prefectures.php`（47都道府県）は書き換えない。県の中心座標は `config/prefecture_centers.php`
- Blade の `@js()` の中に長い式を書かない（`@php` で変数にしてから渡す）
- Alpine の `x-for` の中身は1つの要素にまとめる
- ルートは上から順に当てはめられる。`/spots/nearby` のような固定の URL は `/spots/{spot}` より上に書く
- 説明のコメントは Blade コメント `{{-- --}}`（HTML コメントは画面に出て、テストの `assertDontSee` に引っかかる）
- 「✔」などの記号の字は使わない（Windows で絵文字になる）。CSS の線で描く
- **集計は全部を読み込まず、データベースで数える**。一覧はページ単位にする（3万件でカルテが10秒・300MB かかったことがある）
- テスト：メールアドレスは `example.com`。外の API に本当につながない（`Http::preventStrayRequests()`、天気は `Http::fake`）。ファクトリーの県や公開範囲はランダムなので、結果に関わるときは値を決める。写真のフォルダは `Storage::fake('public')`（`tests/TestCase.php`）
- Leaflet のように JavaScript が付けるクラスの CSS は、`@layer` の外に書く（`@layer components` の中だと Tailwind が捨てる。`BuiltCssTest` が見張る）
- 写真が 403 のときは、まずファイルがあるかを見る（`storage/app/public/catches`）
- 画像の URL は `FishCatch::photoUrl()` か `Storage::disk(PhotoStorer::disk())->url()`（`asset('storage')` を決め打ちしない）
- ボタン：フォームの決定は右寄せ（`justify-end`）、説明と並ぶ大きなボタンは `w-full sm:w-auto`、アカウント削除だけ左
- 見本コードを出すときは「（…今のまま）」のような省略を入れない
- VS Code（Intelephense）の赤線は勘違いのことが多い
- 秘密の値（APP_KEY・DB・AWS・Brevo・ADMIN_PASSWORD など）は Render の環境変数と GitHub の Secrets にだけ入れる。会話・Git に出さない

## 公開範囲とプライバシー（どの機能でも必ず守る）

- 釣行の公開範囲は `public`（全体公開）／`spot_hidden`（釣り場だけ隠す）／`private`（非公開、初期値）
- **閉じているほうを優先**：釣り場が非公開なら、釣行が `public` でも `spot_hidden` として扱う（`Trip::effectiveVisibility()`）
- 「釣り場だけ隠す」の釣り場名・位置は**サーバー側で取り除いてから**画面に渡す
- 公開する位置は緯度経度を小数第2位で**切り捨て**（約1km四方）。正確な位置は登録した本人だけ（`Spot::locationFor()`）
- 選べる・提案する釣り場は「公開の釣り場」と「自分の釣り場」だけ（スコープ `visibleTo`）。釣行は `visibleWithSpotTo`
- 写真の Exif は保存時に必ず消す（`PhotoStorer`）
- 画面以外の出口（お知らせ・CSV・JSON）でも同じチェックをする
- お知らせは番号だけ保存し、表示のたびに `NotificationPresenter` で公開範囲を確かめて文章を作る
- ダミーの人（`users.is_sample`）の釣行には「ダミー」の印。本番に移るときはこの人たちを消す

## 見た目の決まり（クレヨンのメモ帳風）

- 部品：カード `crayon-card`、見出し `crayon-heading`／`crayon-subheading`、ボタン `crayon-button`（2番目は `crayon-button-secondary`）、案内 `crayon-note`、注意 `crayon-caution`、付箋 `crayon-sticky`、点線の表 `crayon-ledger`、ハンコ `crayon-stamp`
- クレヨンの効果は飾り（::before／::after）にだけかける。文字・数字・表の線にはかけない。字の後ろにクレヨンのまだらを置かない
- ボタンはうすいオレンジの塗り＋濃いオレンジのふち、字は黒。オレンジの地に白い字は使わない
- 本文と数字は BIZ UDPゴシック、見出し・ボタン・入力欄は手書きの字（Yomogi、`font-hand`）
- アイコンは `<x-icon name="…" />`（Tabler Icons）。クレヨンのざらつきはかけない。大きくするときは `!h-16 !w-16` のように `!` を付ける
- ナビの付箋のページと色は `config/sections.php` の1か所。幅 1280px より狭いときはアイコンを消して名前だけ（アイコンだけにはしない）
- エラーページは番号を出さない（`resources/views/errors/`）

## 主なファイル

- ルート：`routes/web.php`（ログインして確かめた会員だけ → `['auth', 'verified']`、プロフィールは `auth` だけ、ゲストも見られる：トップ・釣り場・フィード・プランナー・単位変換・使い方・規約）
- 条件の照合：`ConditionMatcher`（時期（前後1か月）をいつも入れ、ぴったり → 時期と時間帯 → 時期だけ。決まりは `levels()` の1か所。カルテは `matchQuery()`、プランナーは `matchBySpot()`。うちダミーの回数 `samples`、記録の内訳 `breakdown()`・`breakdownBySpot()`）
- そのほかのサービス（`app/Services/`）：`TideCalculator`（潮）、`WeatherService`（天気）、`PhotoStorer`（写真）、`NewPostNotifier`・`NotificationPresenter`（お知らせ）、`TripSearch`・`TripCsvExporter`（絞り込みと CSV）、`SeasonHeatmap`（月×魚種）、`KeepRecording`（継続と気づき）、`PersonalBests`（自己ベスト）、`LocalInfoHistory`（現地の情報の変更の履歴。改行は `normalize()` でそろえる）、`CatchHighlighter`（登録直後のハイライト）
- 部品：`match-stamps`（一致のハンコ）、`record-breakdown`（記録の内訳）、`season-table`（月×魚種の表）、`dummy-tag`（ダミーの印）、`notification-bell`（ベル）
- 決まった言葉：`config/fishing.php`（魚種・時間帯・潮・天候・釣り方・公開範囲）、単位変換の数字は `config/units.php`
- JavaScript：`resources/js/app.js`（地図・釣果の行・まとめて登録・単位変換・県と釣り場の選択 `spotPicker`）、`resources/js/photo-hints.js`
- シーダー：`database/seeders/DatabaseSeeder.php`（実在の港131・湖7、ダミーの釣行840件、`fake()->seed(2026)` で毎回同じ）。**国土数値情報の位置は非商用のみなので使わない**

## 公開環境と運用

- お試し環境：https://fishing-log-hasx.onrender.com（Render 無料＋Aiven の MySQL＋Cloudflare R2）。**main に push すると自動で公開し直す**。起動のたびに `docker/start.sh` がマイグレーションとお試しデータの準備をする
- **開かないときは、まず Aiven の `fishinglog-db` が Powered off になっていないか見る**（Power on で戻る）
- 死活監視は UptimeRobot（5分おき）、予備に GitHub Actions の keep-alive
- バックアップ：GitHub Actions（`.github/workflows/backup.yml`）が毎日 4:07 に DB と写真を R2 の `fishinglog-backups` に写し、空の MySQL に戻して件数を確かめる。戻し方は `docs/backup.md`
- 毎月の初めに点検する（`docs/operations.md`。結果は GitHub の Issue に残す）。報告は3日以内に確かめる。鍵は毎年10月に作り直す
- 本番として使うときは `docs/release-plan.md` のとおりに移る（費用は Notion にだけ書く）

## 開発用のログイン

- `test@example.com` / `password`（秋田、釣行45件）、管理者 `admin@example.com` / `password`
- ほかに `minato@`・`surf@`・`iso@`（秋田）、`wanoku@`（神奈川）。すべて `@example.com`・`password`
- 公開環境：お試し用 `demo@example.com` / `fishinglog-demo`。admin は秘密のパスワード、ほかのダミーはログインできない
