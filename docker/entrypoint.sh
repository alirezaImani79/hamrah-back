#!/bin/sh
set -e

# Ensure the writable storage tree exists; the "storage" volume is empty on
# first boot.
mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache/data \
    storage/app/public \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

echo "[entrypoint] storage:link"
php artisan storage:link || true

echo "[entrypoint] config:cache / route:cache / view:cache"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[entrypoint] migrate"
php artisan migrate --force

echo "[entrypoint] starting php-fpm"
exec php-fpm
