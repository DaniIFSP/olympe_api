#!/usr/bin/env bash
set -e

echo "==> Installing Composer dependencies"

composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

echo "==> Preparing Laravel"

# Garantir que a pasta pública de imagens exista
mkdir -p storage/app/public/instituicoes

# Remover link simbólico antigo, caso exista
if [ -L public/storage ]; then
    rm public/storage
fi

# Criar link simbólico correto para os arquivos públicos
php artisan storage:link

# Garantir permissões para o Laravel
chown -R www-data:www-data storage bootstrap/cache

php artisan config:cache
php artisan route:cache

echo "==> Starting PHP-FPM"

php-fpm -D

echo "==> Starting Nginx"

exec nginx -g "daemon off;"