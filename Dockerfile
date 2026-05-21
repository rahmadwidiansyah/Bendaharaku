FROM php:8.4-fpm
LABEL org.opencontainers.image.source=https://github.com/rahmadwidiansyah/Bend

# dependencies sistem
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    nodejs \
    npm

# ekstensi PHP 
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Redis
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

ENV COMPOSER_MEMORY_LIMIT=-1
RUN composer install --no-dev --no-scripts

# 1. Buat Symlink Storage (PENTING untuk gambar!)
RUN php artisan storage:link

# 2. Atur Ownership ke user www-data (Nginx/Apache)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache /var/www/public/storage

# 3. Atur Permission Folder agar bisa dibaca & ditulis
RUN chmod -R 775 /var/www/storage /var/www/bootstrap/cache /var/www/public/storage

EXPOSE 9000

CMD ["php-fpm"]