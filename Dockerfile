# Sử dụng PHP 8.3 FPM làm base image
FROM php:8.3-fpm

# Cài các extension cần thiết
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    librdkafka-dev \
    libssl-dev \
    zlib1g-dev \
    libzstd-dev \
    pkg-config \
    build-essential \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd mbstring exif pcntl bcmath opcache \
    && yes "" | pecl install -f rdkafka \
    && docker-php-ext-enable rdkafka
    && pecl install redis \
    && docker-php-ext-enable redis

# Cài Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc
WORKDIR /var/www

# Copy toàn bộ code vào container
COPY . .

# Phân quyền (cho Laravel storage, bootstrap/cache)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

CMD ["php-fpm"]
