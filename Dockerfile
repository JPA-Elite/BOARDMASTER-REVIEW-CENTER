# syntax=docker/dockerfile:1.7

FROM php:8.3-fpm-bookworm AS php_extensions
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    git \
    unzip \
    libfreetype6-dev \
    libicu-dev \
    libjpeg62-turbo-dev \
    libonig-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl mbstring pcntl pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.8 /usr/bin/composer /usr/local/bin/composer

FROM php_extensions AS vendor_prod
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

FROM php_extensions AS vendor_dev
COPY composer.json composer.lock ./
RUN composer install \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts

FROM node:20-bookworm-slim AS frontend
WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js postcss.config.js tailwind.config.js ./
RUN npm run build

FROM php_extensions AS php_base
COPY . .
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-boardmasters.ini
COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint-app

RUN chmod +x /usr/local/bin/docker-entrypoint-app \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

FROM php_base AS php_runtime
COPY --from=vendor_prod /var/www/html/vendor ./vendor
COPY --from=frontend /var/www/html/public/build ./public/build

RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint-app"]
CMD ["php-fpm", "-F"]

FROM php_base AS php_dev
COPY --from=vendor_dev /var/www/html/vendor ./vendor
COPY --from=frontend /var/www/html/public/build ./public/build

RUN rm -f bootstrap/cache/*.php \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint-app"]
CMD ["php-fpm", "-F"]

FROM nginx:1.27-alpine AS nginx_base
WORKDIR /var/www/html

COPY . .
COPY --from=frontend /var/www/html/public/build ./public/build

RUN mkdir -p /var/www/html/storage/app/public \
    && ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

FROM nginx_base AS nginx_dev
COPY docker/nginx/default.dev.conf /etc/nginx/templates/default.conf.template
EXPOSE 80

FROM nginx_base AS nginx_prod
COPY docker/nginx/default.prod.conf /etc/nginx/templates/default.conf.template
EXPOSE 80 443
