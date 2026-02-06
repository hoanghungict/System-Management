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
    libzip-dev \
    pkg-config \
    build-essential \
    zip \
    unzip \
    git \
    curl \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        gd \
        mbstring \
        exif \
        pcntl \
        bcmath \
        opcache \
        intl \
        zip \
    && yes "" | pecl install -f rdkafka \
    && docker-php-ext-enable rdkafka

# Cài Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Thiết lập thư mục làm việc
WORKDIR /var/www

# Copy toàn bộ code vào container
COPY . .

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Phân quyền ban đầu (cho Laravel storage, bootstrap/cache)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

# Entrypoint sẽ tự động fix permissions mỗi khi container start
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]
