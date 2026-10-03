#!/usr/bin/env bash
# FishingLog のバックアップ（NF-03）
# データベースと、アップロードした写真を backups/ に保存する
# 使い方：scripts/backup.sh
set -euo pipefail

# どこから打っても、プロジェクトのフォルダで動くようにする
cd "$(dirname "$0")/.."

# sail が裏で用意している設定。docker compose を直接使うときの注意を出さないため
export WWWUSER="${WWWUSER:-$(id -u)}"
export WWWGROUP="${WWWGROUP:-$(id -g)}"

stamp=$(date +%Y%m%d-%H%M%S)
mkdir -p backups storage/app/public/catches

# 1. データベース（mysql の入れ物の中で mysqldump を動かし、結果をファイルに書く）
docker compose exec -T mysql sh -c \
  'exec mysqldump --no-tablespaces --single-transaction -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" 2>/dev/null' \
  > "backups/db-${stamp}.sql"

# 2. 写真（catches フォルダを1つのファイルに固める）
tar czf "backups/photos-${stamp}.tar.gz" -C storage/app/public catches

# 3. 古いバックアップを消す（新しい7回分だけ残す）
ls -1t backups/db-*.sql | tail -n +8 | xargs -r rm --
ls -1t backups/photos-*.tar.gz | tail -n +8 | xargs -r rm --

echo "バックアップしました"
echo "  データベース：backups/db-${stamp}.sql"
echo "  写真       ：backups/photos-${stamp}.tar.gz"