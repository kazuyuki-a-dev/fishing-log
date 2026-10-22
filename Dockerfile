# 公開環境（Render）で動かすための設定（#136）。開発は今までどおり Laravel Sail を使う
# 1. 画面の部品（CSS・JavaScript）を作る
FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# 2. 動かす箱。PHP 8.5（開発の Sail と同じ版）＋ Apache
# 版は細かい番号まで書き切る。8.5 だけだと、前に作った箱が使い回されて古い版のまま動くことがあるため。
# 8.5.12 は緊急のセキュリティ修正（CVE-2026-109618・CVE-2026-109619）の入った版。次の修正が出たら、ここを上げる（#184）
FROM php:8.5.12-apache
# 写真（GD・Exif）、MySQL、そのほか Laravel に要る部品（opcache は PHP 8.5 から最初から入っている）
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev libzip-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" gd exif pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite
# 公開するのは public フォルダだけ
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "upload_max_filesize=20M\npost_max_size=60M\nmemory_limit=256M\n" > "$PHP_INI_DIR/conf.d/fishinglog.ini"

# PHP の部品（vendor）を入れる道具
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
# 先に部品の一覧だけ入れておくと、プログラムを直しただけのときは部品を入れ直さずに済む
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction
COPY . .
# 開発用の部品は入れない。部品の読み込みを速くする
RUN composer dump-autoload --optimize --no-dev
COPY --from=assets /app/public/build ./public/build
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

CMD ["docker/start.sh"]
