# =======================
# STAGE 1: Vendor
# =======================
FROM php:8.3-cli AS vendor

# System deps + PHP extensions (GIỐNG runtime)
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev libicu-dev \
    librdkafka-dev zip unzip git curl \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    gd mbstring intl zip bcmath pcntl \
 && pecl install rdkafka \
 && docker-php-ext-enable rdkafka \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-scripts
# =======================
# STAGE 2: Runtime
# =======================
FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev libicu-dev \
    librdkafka-dev zip unzip curl git \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install \
    pdo pdo_mysql gd mbstring bcmath intl zip opcache \
 && pecl install rdkafka \
 && docker-php-ext-enable rdkafka \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .
COPY --from=vendor /app/vendor ./vendor

RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache vendor

USER www-data
CMD ["php-fpm"]

