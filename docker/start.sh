#!/bin/sh
# 公開環境（Render）で、箱が起動するたびに動かす（#136）
set -e
cd /var/www/html

# Render が決めた番号（PORT）で Apache を待ち受けにする。なければ 80
PORT="${PORT:-80}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Aiven の MySQL は暗号化した接続（SSL）が必要。証明書の中身を環境変数から受け取って、ファイルにする
if [ -n "$DB_SSL_CA_CONTENT" ]; then
    printf '%s\n' "$DB_SSL_CA_CONTENT" > /var/www/html/storage/db-ca.pem
    export MYSQL_ATTR_SSL_CA=/var/www/html/storage/db-ca.pem
fi

# 設定・ルート・画面をまとめて、読み込みを速くする
php artisan config:cache
php artisan route:cache
php artisan view:cache

# データベースの表を作る・最新にする
php artisan migrate --force

# データがまだ空のとき（最初の1回）だけ、お試しデータを入れる（routes/console.php の app:seed-if-empty）
php artisan app:seed-if-empty

# 全体公開の釣果のいくつかに、サンプルの魚のイラストを付ける。もう付いていれば何もしない（#148）
php artisan app:attach-sample-photos

chown -R www-data:www-data storage bootstrap/cache
exec apache2-foreground
