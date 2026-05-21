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

# Build Backend (Laravel)
ENV COMPOSER_MEMORY_LIMIT=-1
RUN composer install --no-dev --no-scripts

# Build Frontend (Vue/Inertia) - INI YANG SEBELUMNYA KURANG
RUN npm install
RUN npm run build

EXPOSE 9000

# Eksekusi Symlink dan Permission SAAT container start (mengatasi masalah Volume CasaOS), lalu jalankan PHP-FPM
CMD sh -c "rm -rf public/storage && \
    php artisan storage:link && \
    chown -R www-data:www-data storage bootstrap/cache public/storage && \
    chmod -R 775 storage bootstrap/cache public/storage && \
    php-fpm"