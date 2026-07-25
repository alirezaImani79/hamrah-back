ARG PHP_VERSION=8.4

###############################################################################
# Stage 1 — Composer dependencies (no dev, no scripts)
###############################################################################
FROM composer:2 AS composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist

###############################################################################
# Stage 2 — application (PHP-FPM, Debian bookworm)
###############################################################################
# Debian (not alpine) is used here on purpose: alpine's postgresql-dev drags in
# PostgreSQL's LLVM/clang JIT toolchain (~1.5 GB) just to build pdo_pgsql.
# Debian's libpq-dev ships pg_config + the client headers without any of that.
FROM php:${PHP_VERSION}-fpm-bookworm AS app

WORKDIR /var/www/html

# Build the PHP extensions, then strip the compiler toolchain (the runtime
# libpq/libicu/libzip libs stay behind for the compiled .so files).
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libicu-dev \
        build-essential \
        autoconf \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        bcmath \
        zip \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get purge -y --auto-remove build-essential autoconf \
    && rm -rf /var/lib/apt/lists/*

# Production PHP / OPcache settings (errors to stderr -> docker logs).
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-production.ini

# Composer binary, used to bake an optimized autoloader at build time.
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Dependencies first (better layer caching), then application source.
COPY --from=composer /var/www/html/vendor ./vendor
COPY . .

# Optimized autoloader now that the App\ source is present.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# Writable storage skeleton + ownership.
RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache/data \
        storage/app/public \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Entrypoint: cache config/views, run migrations, then start FPM.
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

###############################################################################
# Stage 3 — web server (nginx serving public/ + proxying to FPM)
###############################################################################
FROM nginx:1.27-alpine AS web

WORKDIR /var/www/html

# Static assets + front controller from the app stage.
COPY --from=app /var/www/html/public ./public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
