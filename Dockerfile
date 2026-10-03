# syntax=docker/dockerfile:1

# ---------- frontend build ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json bun.lock ./
RUN npm install --ignore-scripts
COPY vite.config.js resources/ ./resources/
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- backend deps ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-interaction --no-progress --no-dev --no-scripts

# ---------- runtime ----------
FROM php:8.3-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache fcgi icu-data-full libpng libjpeg-turbo libwebp freetype \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS freetype-dev libpng-dev libjpeg-turbo-dev libwebp-dev icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql bcmath intl exif zip pcntl opcache \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN addgroup -S www && adduser -S www -G www \
    && mkdir -p storage/app/public storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www:www storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

USER www
EXPOSE 9000
HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD php-fpm -t || exit 1
ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
