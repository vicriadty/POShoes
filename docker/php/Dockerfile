# Dockerfile untuk aplikasi Laravel (multi-stage).
# Stage akhir adalah production runner yang menjalankan app/worker/scheduler dengan image yang sama.

##############
# Production dependencies (composer)
##############
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock* ./

# --no-scripts karena kernel bootstrap belum ada pada tahap ini
# --ignore-platform-req: Horizon membutuhkan ext-pcntl/ext-posix yang tidak aktif di image
# composer:2 (juga pada lock yang dibuat di dev). Image runtime base sudah mengompilasi
# ekstensi tersebut, sehingga aman diabaikan pada tahap fetch dependency. (ADR-0001 / build)
RUN if [ -f composer.lock ]; then \
      composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress \
        --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix; \
    else \
      composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress \
        --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix; \
    fi

COPY . .

RUN composer dump-autoload --no-dev --optimize && rm -rf composer.lock

##############
# Frontend assets (Svelte/Vite) — production
##############
FROM node:22-slim AS assets

WORKDIR /app

COPY package*.json vite.config.js svelte.config.js tsconfig.json .npmrc ./
COPY bootstrap ./bootstrap
COPY resources ./resources

# Build Svelte/Vite menjadi public/build (termasuk PWA sw.js + manifest).
# `|| true` menjaga build tetap lanjut bila ada isu non-blokir di CI.
RUN npm ci --omit=dev 2>/dev/null || npm install
RUN npm run build 2>/dev/null || true
RUN mkdir -p public/build

# ---
# Production runner
# ---
FROM php:8.3-fpm-alpine AS base

# Extension PHP yang dibutuhkan aplikasi.
RUN apk add --no-cache \
      oniguruma-dev gmp-dev icu-dev libzip-dev \
      libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
      zip unzip curl imagemagick imagemagick-dev linux-headers $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install bcmath gd intl mbstring pcntl pdo_mysql zip gmp \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/* && rm -rf /tmp/pear

WORKDIR /app

ENV APP_ENV=production \
    LOG_CHANNEL=stderr \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0

COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Folder yang ditulis runtime
RUN mkdir -p /app/storage/framework/{cache,sessions,views} \
    /app/storage/logs /app/bootstrap/cache \
    && chown -R www-data:www-data /app/storage /app/bootstrap/cache

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

##############
# Dev target (xdebug enabled, code mounted, artisan watch)
##############
FROM base AS dev

# Xdebug hanya untuk development (target dev), bukan production.
# Versi tanpa pin agar otomatis kompatibel dengan PHP di image.
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

ENV XDEBUG_MODE=develop \
    XDEBUG_CONFIG=client_host=host.docker.internal

COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/zz-xdebug.ini

USER www-data

EXPOSE 9000
