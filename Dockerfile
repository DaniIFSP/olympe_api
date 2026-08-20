FROM php:8.3-fpm-bookworm

# Dependências do sistema
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        opcache \
        zip \
        xml \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Composer instala dependências no startup
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV LOG_CHANNEL=stderr

# Nginx
COPY conf/nginx/nginx-site.conf /etc/nginx/conf.d/default.conf

# Script de inicialização/deploy
COPY scripts/00-laravel-deploy.sh /usr/local/bin/laravel-deploy.sh
RUN chmod +x /usr/local/bin/laravel-deploy.sh

# Permissões necessárias pelo Laravel
RUN chown -R www-data:www-data \
    storage \
    bootstrap/cache

EXPOSE 10000

CMD ["/usr/local/bin/laravel-deploy.sh"]