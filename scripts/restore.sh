#!/usr/bin/env bash
# FishingLog をバックアップから戻す（NF-03）
# 今のデータは消えて、バックアップを取った時点に戻る
# 使い方：scripts/restore.sh 20261003-103000
set -euo pipefail

cd "$(dirname "$0")/.."

export WWWUSER="${WWWUSER:-$(id -u)}"
export WWWGROUP="${WWWGROUP:-$(id -g)}"

stamp="${1:-}"
if [ -z "$stamp" ]; then
  echo "使い方：scripts/restore.sh 日時"
  echo "戻せるバックアップ："
  ls -1 backups/db-*.sql 2>/dev/null | sed 's/.*db-\(.*\)\.sql/  \1/' || echo "  （まだありません）"
  exit 1
fi

db="backups/db-${stamp}.sql"
photos="backups/photos-${stamp}.tar.gz"
if [ ! -f "$db" ] || [ ! -f "$photos" ]; then
  echo "見つかりません：${stamp}"
  exit 1
fi

read -r -p "今のデータを消して ${stamp} の時点に戻します。よろしいですか？（yes と入力）" answer
if [ "$answer" != "yes" ]; then
  echo "やめました"
  exit 1
fi

# 1. データベースを戻す（バックアップの中に、表を作り直す命令も入っている）
docker compose exec -T mysql sh -c \
  'exec mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" 2>/dev/null' \
  < "$db"

# 2. 写真を戻す（今の写真を消してから、バックアップの写真を広げる）
rm -rf storage/app/public/catches
tar xzf "$photos" -C storage/app/public

echo "${stamp} の時点に戻しました"