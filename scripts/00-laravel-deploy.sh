#!/usr/bin/env bash
set -e

echo "==> Installing Composer dependencies"

composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

echo "==> Preparing Laravel"

php artisan config:cache
php artisan route:cache

echo "==> Starting PHP-FPM"

php-fpm -D

echo "==> Starting Nginx"

exec nginx -g "daemon off;"