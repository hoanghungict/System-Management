FROM php:8.3-fpm

# Cài extension PHP cần cho Laravel
RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Cài Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Tạo thư mục làm việc
WORKDIR /var/www

# Copy code Laravel (chỉ để build lần đầu, sau đó sẽ mount từ host)
COPY . .

# Cài đặt dependency của Laravel
RUN composer install --optimize-autoloader
