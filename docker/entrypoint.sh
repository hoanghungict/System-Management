#!/bin/bash
set -e

# ===========================================
# Laravel Docker Entrypoint Script
# Tự động fix permissions mỗi khi container start
# ===========================================

echo "🔧 Setting up Laravel permissions..."

# Fix ownership cho storage và bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Fix permissions
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Đảm bảo các thư mục cần thiết tồn tại
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/cache/laravel-excel
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/logs
mkdir -p /var/www/bootstrap/cache

# Fix lại permissions sau khi tạo thư mục
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

echo "✅ Permissions configured successfully!"

# Chạy command được truyền vào (php-fpm hoặc artisan commands)
exec "$@"
