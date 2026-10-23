# バックアップと戻し方（NF-03）

## 文書の情報

| 項目 | 内容 |
|---|---|
| 文書名 | FishingLog バックアップと戻し方 |
| 版 | 2.0 |
| 作成日 | 2026-10-03 |
| 最終更新日 | 2026-10-23 |
| 作成者 | kazuyuki-a-dev |
| 対象 | データベース（MySQL）と、アップロードした写真 |

## 改訂履歴

| 版 | 日付 | 変更内容 | Issue |
|---|---|---|---|
| 1.0 | 2026-10-03 | 初版（開発環境の手順） | #71 |
| 2.0 | 2026-10-23 | 公開環境の毎日の自動バックアップ・戻す試し・本番に戻す手順を足した | #204 |

## まとめ

| | 開発環境（Sail） | 公開環境（Render ＋ Aiven ＋ Cloudflare R2） |
|---|---|---|
| 取り方 | 手で `scripts/backup.sh` | GitHub Actions で毎日自動（`.github/workflows/backup.yml`） |
| いつ | データが消える操作の前・週に1回くらい | 毎日 日本時間の朝 4:07 |
| どこに | 同じパソコンの `backups/` | Cloudflare R2 の非公開のバケット `fishinglog-backups` |
| 何日分 | 新しい7回分 | データベースは30日分（写真の写しは消さない） |
| 戻せるかの確かめ | 2026-10-03 に手で確かめた | 毎回、取ったすぐあとに Actions の中で戻して件数を確かめる |

ほかに、Aiven にも毎日の自動バックアップがあります（Aiven の中にあるので、Aiven のアカウントが止められたり消えたりすると一緒に失います。そのため、上の自前のバックアップを別の場所に持ちます）。

---

## 公開環境

### 何を・いつ・どこに

- **データベース**：毎日 4:07 に、Aiven の MySQL を `mysqldump` で書き出して圧縮し、`fishinglog-backups` の `db/fishinglog_日付_時刻.sql.gz` に置きます
  - 30日より古いものは、R2 の決まり（ライフサイクルのルール）で自動で消えます
- **写真**：写真のバケット（`fishinglog-photos`）を、`fishinglog-backups` の `photos/` に写します
  - 写す側では消さないので、間違って消した写真も残ります（退会などで消した写真は、写しから手で消します。下の「退会した人の写真」）
- **戻せるかの確かめ**：取ったバックアップを、Actions の中の空の MySQL に戻し、アプリのテーブルが全部あるかと、会員・釣り場・釣行・釣果の件数を数えます。会員か釣り場が0件、またはテーブルが足りないと失敗にします。写真は、写しの枚数が元より少ないと失敗にします
- **結果**：GitHub の [Actions → バックアップ](https://github.com/kazuyuki-a-dev/fishing-log/actions/workflows/backup.yml) の実行を開くと、Summary に「本番と戻したものの件数」「ファイルの大きさ」「写真の枚数」が出ます
- **失敗したとき**：GitHub からメールが届きます（下の「失敗したとき」）

### 設定（最初に1回だけ）

1. **R2 のバケット**：Cloudflare の R2 で `fishinglog-backups` を作る（公開しない。Public access は Disallowed のまま）
2. **30日で消す決まり**：`fishinglog-backups` → Settings → Object lifecycle rules → ルールを足す（名前 `db-30days`、Prefix `db/`、Delete objects after 30 days）
3. **バックアップ専用の鍵**：R2 → Manage API tokens → Create API token
   - 名前 `fishinglog-backup`、権限 **Object Read & Write**、対象のバケットは `fishinglog-photos` と `fishinglog-backups` の2つだけ
   - アプリが使っている鍵とは分けます（漏れたときに、片方だけ止めて作り直せるように）
   - 写真のバケットにも書ける権限ですが、ワークフローは写真のバケットから読むだけで、書いたり消したりはしません
4. **GitHub の Secrets**：リポジトリの Settings → Secrets and variables → Actions → New repository secret

| 名前 | 中身 |
|---|---|
| `BACKUP_DB_HOST` | Aiven の Host |
| `BACKUP_DB_PORT` | Aiven の Port |
| `BACKUP_DB_USERNAME` | Aiven の User（`avnadmin`） |
| `BACKUP_DB_PASSWORD` | Aiven の Password |
| `BACKUP_DB_DATABASE` | Aiven の Database name |
| `BACKUP_DB_CA` | Aiven の CA certificate の中身（Render の `DB_SSL_CA_CONTENT` と同じ） |
| `BACKUP_R2_ENDPOINT` | R2 の S3 API の住所（`https://アカウントID.r2.cloudflarestorage.com`） |
| `BACKUP_R2_ACCESS_KEY_ID` | 3 の鍵の Access Key ID |
| `BACKUP_R2_SECRET_ACCESS_KEY` | 3 の鍵の Secret Access Key |
| `BACKUP_R2_PHOTOS_BUCKET` | `fishinglog-photos` |
| `BACKUP_R2_BACKUP_BUCKET` | `fishinglog-backups` |

- Aiven のパスワードを作り直したら、Render の `DB_PASSWORD` と一緒に `BACKUP_DB_PASSWORD` も入れかえます

### 本番に戻す手順（データベース）

**今の本番のデータは消えて、バックアップの時点に戻ります。** 戻す前に、必ず今の状態もバックアップします。

1. **今の状態を残す**：GitHub の Actions → バックアップ → **Run workflow** で、今の状態のバックアップを取る（成功するまで待つ）
2. **戻すファイルを決める**：Cloudflare の R2 → `fishinglog-backups` → `db/` から、戻したい日のファイルを選んでダウンロードする
3. **アプリを止める**：Render の fishing-log → Settings → **Suspend Web Service**（戻している途中に書き込まれないように）
4. **戻す**：手元のパソコン（WSL）で、Aiven の接続の情報を使って入れる。証明書は Aiven の画面から `ca.pem` としてダウンロードしておく

       gunzip -c fishinglog_日付_時刻.sql.gz | docker run --rm -i -e MYSQL_PWD='Aivenのパスワード' -v "$PWD:/work" mysql:8.4 \
         mysql --host=Aivenのホスト --port=Aivenのポート --user=avnadmin --ssl-mode=VERIFY_CA --ssl-ca=/work/ca.pem Aivenのデータベース名

   - パスワードは、打ち終わったら端末の履歴（`history`）から消すか、`.env` のような Git に入れないファイルから読み込む
5. **アプリを動かす**：Render の **Resume Web Service**
6. **確かめる**：お試し環境を開き、釣り場・釣行が戻っていること、ログインできることを見る。Actions → バックアップ をもう一度動かし、Summary の件数を見る

### 本番に戻す手順（写真）

写真を消してしまったときは、写しから元のバケットに戻します（手元のパソコンで、3 の鍵を使う）。

    export AWS_ACCESS_KEY_ID=…  AWS_SECRET_ACCESS_KEY=…  AWS_DEFAULT_REGION=auto
    export AWS_REQUEST_CHECKSUM_CALCULATION=when_required AWS_RESPONSE_CHECKSUM_VALIDATION=when_required
    aws s3 sync s3://fishinglog-backups/photos s3://fishinglog-photos --endpoint-url https://アカウントID.r2.cloudflarestorage.com

- 元のバケットにないものだけを写すので、今ある写真は消えません

### 退会した人の写真

- 退会すると、アプリは写真のバケットからその人の写真を消しますが、写しは残ります
- プライバシーポリシーでは「バックアップには最大30日残る」としています。写真の写しを長く持たないよう、**月に1回**、写しにだけ残っている写真を消します（元のバケットにない写真＝退会や削除で消したもの）

      aws s3 sync s3://fishinglog-photos s3://fishinglog-backups/photos --delete --endpoint-url …

  - `--delete` は、元にない写しを消すという意味です。ふだんの毎日のバックアップには付けません

### 失敗したとき

1. GitHub から「Run failed: バックアップ」のメールが届く
2. Actions → バックアップ → 失敗した実行を開き、赤い × の手順のログを見る
   - 「データベースを書き出す」で失敗：Aiven が止まっていないか（Powered off なら Power on）、パスワードを作り直していないか
   - 「戻せるかを試す」で失敗：バックアップの中身がおかしい。前の日のバックアップがあるか確かめ、原因が分かるまで本番を変える作業はしない
   - 「R2 に置く」「写真を写す」で失敗：バックアップ専用の鍵が切れていないか・消していないか
3. 直したら、**Run workflow** で手で動かして、成功するのを確かめる

---

## 開発環境

### バックアップを取る

    scripts/backup.sh

- `backups/db-日時.sql`（データベース）と `backups/photos-日時.tar.gz`（写真）ができます
- 新しい7回分だけ残し、それより古いものは自動で消します
- Sail が動いている状態（`sail up -d`）で打ちます

### いつ取るか

- `sail artisan migrate:fresh` など、データが消える操作の前（必ず）
- データベースの形を変える作業（マイグレーションの追加）の前
- それ以外は、週に1回くらい

### 戻す

    scripts/restore.sh            # 戻せるバックアップの一覧が出る
    scripts/restore.sh 20261003-104010

- 「よろしいですか？」と聞かれたら `yes` と入力します
- **今のデータは消えて、バックアップを取った時点に戻ります**
- 戻したあとは、ブラウザを読み込み直してください

### 気をつけること

- `backups/` は Git に入れません（`.gitignore` 済み）。メールアドレスやパスワードのハッシュが入っているためです
- `backups/` は同じパソコンの中にあるので、パソコンが壊れると一緒に消えます。大事なデータが入ったら、ときどき USB メモリやクラウドにもコピーしてください
- `sail down -v` はデータベースを消します。止めるときは `sail stop` を使います

### 確かめたこと（2026-10-03）

1. 写真付きの釣行 A を記録してバックアップを取った
2. そのあと釣行 B を記録した
3. 1 のバックアップに戻すと、釣行 A（写真も）は残り、釣行 B は消えた
