# FishingLog（釣果記録・釣り場情報共有アプリ）

[![テスト](https://github.com/kazuyuki-a-dev/fishing-log/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/kazuyuki-a-dev/fishing-log/actions/workflows/tests.yml)

釣行を記録していくと、「どの潮・時間帯・釣り方なら、何回行って何回釣れたか」が貯まり、次にどこへ行くかを決める材料になる Web アプリです。月に1〜3回ほど釣りに行く人が、自分の記録とみんなの公開記録から「釣れる条件」を見つけることを目指しています。

主役は **釣行プランナー**（行く日と県を選ぶと、過去の記録から釣り場を提案）と **釣り場カルテ**（その釣り場で、選んだ日の条件のときにどうだったか）です。

## お試し環境

環境構築をしなくても、ブラウザで試せます。

| 項目 | 内容 |
| --- | --- |
| URL | https://fishing-log-hasx.onrender.com |
| お試し用アカウント | メールアドレス `demo@example.com` ／ パスワード `fishinglog-demo` |

| 付箋 | 試してほしいこと |
| --- | --- |
| 釣行プランナー | 日付を変えて、釣り場の並びと「記録の内訳を見る」を見る。ログインしなくても、トップの「プランナーを試す」から自分の県で試せる |
| 釣り場 | 「秋田港」のカルテで、判断ビュー（潮・天気予報・何回行って何回釣れたか・記録の内訳）と月×魚種の表を見る |
| 釣行 | 「釣行を記録」で魚の写真を選ぶ（日時と近くの釣り場の候補が出る）。「絞り込む」「CSV で保存」も |
| ダッシュボード | 自己ベストの「すべて見る」と、シーズンヒートマップを開く |
| 釣果フィード・単位変換 | フィードで「秋田県」を選ぶ。単位変換でラインの号を入れて、lb と kg を見る |

- お試し用アカウントは、だれでも使います。**本物の個人情報や、知られたくない釣り場は入れないでください**（このアカウントは、プロフィールの変更と退会ができません）
- **釣行の記録は、ほとんどがダミーです**。ダミーの釣行には灰色の「ダミー」の印が付き、プランナーとカルテには「うちダミー N回」と出ます。「サンプル」のスタンプがある写真は、自作の魚のイラストです
- 自分で会員登録すると、確認のメールが届きます（[Brevo](https://www.brevo.com/) から送るので、迷惑メールのフォルダに入ることがあります）
- 無料の公開先の制約で、釣行を記録するときの天候の自動入力は取得できないことがあります（天候は手で選べます）。公開先と動かし方は [`docs/operations.md`](docs/operations.md) にあります

## 画面

開発用のデータ（`test@example.com`、記録はダミー）で撮った画面です。

<table>
<tr>
<th>ダッシュボード</th>
<th>釣行プランナー</th>
</tr>
<tr>
<td><img src="docs/images/screen-dashboard.png" alt="ダッシュボード：点線の表と連続記録のスタンプ、気づきカード" width="420"></td>
<td><img src="docs/images/screen-planner.png" alt="釣行プランナー：行く日の潮と、釣り場の候補。うちダミーの回数と、記録の内訳を見るボタン" width="420"></td>
</tr>
<tr>
<th>釣り場カルテ（秋田港）</th>
<th>釣果フィード</th>
</tr>
<tr>
<td><img src="docs/images/screen-karte.png" alt="釣り場カルテ：付箋の釣行判断ビュー。うちダミーの回数と、記録の内訳を見るボタン" width="420"></td>
<td><img src="docs/images/screen-feed.png" alt="釣果フィード：みんなの公開釣果。ダミーの釣行に灰色の印" width="420"></td>
</tr>
<tr>
<th>単位変換</th>
<th>スマホ幅（左：釣り場カルテ、右：≡ メニュー）</th>
</tr>
<tr>
<td valign="top"><img src="docs/images/screen-converter.png" alt="単位変換：重さ・長さ・ラインのどれか1つに入れると、ほかの単位が出る" width="420"></td>
<td valign="top"><img src="docs/images/screen-mobile-karte.png" alt="スマホ幅の釣り場カルテ" width="130"> <img src="docs/images/screen-mobile-menu.png" alt="スマホ幅で ≡ メニューを開いたところ" width="130"></td>
</tr>
</table>

## 機能と優先度

機能を「このアプリの価値の中心か」「公開して人に使ってもらうのに欠かせないか」で分けています（#217）。番号は定義書（[`docs/spec.md`](docs/spec.md)）の機能番号で、要件ごとの状態は [`docs/requirements.md`](docs/requirements.md) にあります。

| 優先度 | 機能 | できること |
| --- | --- | --- |
| 主役 | 釣行プランナー（FN-16） | 行く日と県を選ぶと、同じ時期（その月と前後1か月）の記録とその日の潮から釣り場を並べる。記録がなければ「ぴったり一致 → 時期と時間帯 → 時期だけ」と条件をゆるめ、一致した条件をハンコで見せる。「記録の内訳を見る」で、時間帯・潮・年ごとの件数が開く |
| 主役 | 釣り場カルテ（FN-09）・釣行判断ビュー（FN-11） | カルテの一番上で、選んだ日の条件のときに「何回行って何回釣れたか」・釣り方・魚・記録の内訳を出す。実績・月×魚種の表・履歴・天気予報・次の大潮も |
| 必須 | 記録（FN-01）・釣り方（FN-15）・自動補完（FN-08） | 釣り場・釣行・魚1匹ごとの釣り方を記録（坊主も、昔の釣行のまとめて登録も）。写真から日時と近くの釣り場の候補、潮は計算、天候は自動 |
| 必須 | 公開範囲（FN-12） | 全体公開／釣り場だけ隠す／非公開（初期値） |
| 必須 | 会員（FN-05）・メインフィールド（FN-17） | 会員登録（確認のメール）・パスワード再設定と変更・退会。住んでいる県が、プランナーやフィードの最初の県になる |
| 必須 | 現地の情報（FN-14） | 注意区分・駐車場・トイレなど。変更の履歴を会員に見せ、管理者が前の値に戻せる |
| 必須 | 公開の運用 | セキュリティ（NF-01）・スマホ対応（NF-02）・毎日のバックアップ（NF-03）・報告と管理画面（NF-04）・規約（NF-05）・自動テスト（NF-06）・初期データ（NF-07） |
| 補助 | ダッシュボード（FN-06）・ハイライト（FN-07）・継続（FN-10） | 累計・自己ベスト（定義書にない）・連続記録のスタンプ・気づきカード、登録した直後の「最大サイズを更新！」 |
| 補助 | ヒートマップ（FN-02）・条件検索（FN-03）・CSV 出力（FN-04） | 月×魚種の釣れた数、条件で絞り込んで「N回行って M回釣れた」、自分のデータの CSV |
| 補助 | 釣果フィード（FN-13）・県内のお知らせ（FN-18） | みんなの公開釣果、同じ県の新着をベルで知らせる |
| 補助 | 単位変換（定義書にない） | 重さ・長さ・ラインの号と lb。記録しない日にも開くきっかけにする |
| 後回し | 今回は作らない | プランナーへの天気予報（カルテで見る）、隣の県を自動で含める、会員を止める機能・エラーの知らせ・報告のメールでの知らせ（[`docs/release-plan.md`](docs/release-plan.md)） |

- **定義書の Phase との違い**：機能を広げる前に主要な機能と運用の準備を優先するべきだという指摘を受け、定義書では最初に作るもの（Phase 1）だった FN-06・FN-07・FN-13 を「補助」に下げ、Phase 3 の報告と管理画面（NF-04）と、定義書にないメールの確認を「必須」に上げました（理由は [`docs/decisions.md`](docs/decisions.md)）
- **完成の境界**：お試し環境としては、主役と必須がそろっています（本番の公開のときに行う「開発者自身の実際の釣行の登録」だけが残り）。本番としては、その登録と、本番リリース計画の「必須」が済んだら完成です

## 工夫したこと

**公開範囲とプライバシー**（釣り場の場所は、釣り人にとって大事な情報なので）

- 公開範囲は3段階で、初期値は非公開。釣り場が非公開なら、釣行が「全体公開」でも「釣り場だけ隠す」として扱う（閉じているほうを優先）
- 「釣り場だけ隠す」の釣り場名と位置は、画面で隠すのではなく、サーバーで取り除いてから渡す。公開する位置は約1km四方に切り捨てる
- 写真は保存するときに描き直して、Exif（撮影日時や位置）を消す
- お知らせ・CSV・提案など、画面以外の出口でも同じチェックをし、組み合わせの多さは Feature テストで確かめる

**記録が少ないうちも使い続けられる**

- 記録が1件でもダッシュボードの数字が動き、登録した直後に「最大サイズを更新！」などの手応えがある。連続で記録した月数をスタンプで見せる
- 自分の記録が少なくても、みんなの公開記録を使い、条件をゆるめて探す。どのレベルの一致か・記録の内訳・うちダミーの回数を必ず出すので、どこまで信じてよいかを自分で判断できる
- 昔の釣行のまとめて登録、写真からの候補、潮と天候の自動入力、実在の港と湖で、初めの入力を軽くする

**見た目**：テーマはクレヨンのメモ帳風（3つの見本から決めた）。クレヨンの効果は枠や下線の飾りにだけかけ、文字・数字・表の線にはかけない。色は文字とのコントラストが目安（4.5）を超えるように決め、スマホ（幅 360px）でもくずれないようにしている

## 使った技術

| 種類     | 使ったもの                                                                                                                                            |
| -------- | ----------------------------------------------------------------------------------------------------------------------------------------------------- |
| サーバー | PHP 8.5 / Laravel 13 / MySQL 8.4                                                                                                                      |
| 画面     | Laravel Breeze（Blade）/ Alpine.js / Tailwind CSS v3 / Vite                                                                                           |
| 開発環境 | WSL2（Ubuntu）＋ Docker ＋ Laravel Sail                                                                                                               |
| 写真     | Intervention Image v4（保存するときに描き直して Exif を消す）、ExifReader（ブラウザで撮影日時と位置を読む）、heic-to（iPhone の HEIC を JPEG に変換） |
| 地図     | Leaflet ＋ OpenStreetMap                                                                                                                              |
| 天気     | 天気予報は [MET Norway](https://api.met.no/)（ノルウェー気象局、CC BY 4.0）、釣行の日時の天候は Open-Meteo（どちらも API キー不要）                   |
| フォント | Google Fonts の BIZ UDPGothic・Yomogi（どちらも SIL Open Font License）                                                                               |
| アイコン | [Tabler Icons](https://tabler.io/icons)（MIT ライセンス、Copyright (c) 2020-2026 Paweł Kuna）の魚・釣り針・灯台・王冠など。エサとルアーの絵は自作          |

## 動かし方

Docker が使える環境（WSL2 など）を前提にしています。

```bash
git clone https://github.com/kazuyuki-a-dev/fishing-log.git
cd fishing-log

# PHP のパッケージを入れる（Sail を使えるようにするため、最初の1回だけ Docker で入れる）
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php84-composer:latest composer install --ignore-platform-reqs

cp .env.example .env

./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail artisan storage:link
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

ブラウザで http://localhost を開きます。開発用のログイン（`test@example.com` ／ `password` など）・入るデータ・気をつけること（`sail down -v` を使わない、など）は [`docs/development.md`](docs/development.md) にあります。

**テスト**：`./vendor/bin/sail artisan test`（408 件。公開範囲とアクセス制御を中心に、計算や入力チェックも）。push のたびに GitHub Actions（[`.github/workflows/tests.yml`](.github/workflows/tests.yml)）でも全部のテストを流しています。いちばん上のバッジが緑なら main はすべて成功で、[Actions の画面](https://github.com/kazuyuki-a-dev/fishing-log/actions/workflows/tests.yml) の Summary で件数を見られます。

## 資料の一覧

| 資料 | 中身 |
| --- | --- |
| [`docs/spec.md`](docs/spec.md) | 定義書（機能・画面・テーブルの一覧） |
| [`docs/decisions.md`](docs/decisions.md) | 定義書と違うことや、書いていないことを決めたときの判断と理由（日付つき） |
| [`docs/requirements.md`](docs/requirements.md) | 要件の対応表（要件ごとの優先度・状態・受け入れの条件・確かめ方・結果） |
| [`docs/database.md`](docs/database.md) | データベース（ER 図） |
| [`docs/development.md`](docs/development.md) | 開発環境の手引き（開発用のログインとデータ・気をつけること・データが増えたときの確かめ） |
| [`docs/operations.md`](docs/operations.md) | 運用の手順書（毎月の点検・報告への対応・鍵の管理・障害のとき・公開先） |
| [`docs/release-plan.md`](docs/release-plan.md) | 本番リリース計画（お試し環境で割り切っていることと、本番で変えること） |
| [`docs/backup.md`](docs/backup.md) | バックアップと戻し方（公開環境は毎日自動で取り、毎回戻して確かめる） |

## 開発の記録

- 機能ごとに Issue を立て、ブランチで作り、Pull Request でマージしています。コミットは「機能 → テスト → 記録」の順です
- フェーズ1（記録・カルテ・プランナー・公開範囲）→ フェーズ2（お知らせ・ヒートマップ・条件をゆるめて探す）→ フェーズ3（条件検索・CSV・継続カウンタ・報告と管理画面・見た目）の順に作りました
- 公開したあと、完了の説明・テストの再現・運用の決めごとが足りないという指摘を受けて直しました（#196〜#221）：テストの自動実行 → 要件の対応表 → 現地の情報の変更の履歴 → メール → 毎日のバックアップ → 運用の手順書と本番リリース計画 → ゲストのプランナー → ダミーの印と記録の内訳 → 会員登録のメールの確認 → 機能の優先順位 → 資料の見直し → README の整理

## 開発者・リンク

| 項目         | 内容                                                              |
| ------------ | ----------------------------------------------------------------- |
| 開発者       | kazuyuki-a-dev（[GitHub](https://github.com/kazuyuki-a-dev)）     |
| リポジトリ   | https://github.com/kazuyuki-a-dev/fishing-log                     |
| Issue        | https://github.com/kazuyuki-a-dev/fishing-log/issues?q=is%3Aissue |
| Pull Request | https://github.com/kazuyuki-a-dev/fishing-log/pulls?q=is%3Apr     |
