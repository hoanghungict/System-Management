#!/bin/sh
set -e

echo "🚀 Laravel optimize..."

php artisan key:check || true

php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
