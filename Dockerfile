# Gunakan PHP 8.4 FPM sesuai kebutuhan Laravel terbaru/Symfony 8
FROM php:8.4-fpm

# Install dependencies sistem
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev

# Install ekstensi PHP yang dibutuhkan Laravel & PostgreSQL
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

# Install & aktifkan ekstensi Redis lewat PECL
RUN pecl install redis && docker-php-ext-enable redis

# Ambil Composer versi terbaru
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy seluruh file project ke dalam container
COPY . .

# Bypass limit memori composer saat install
ENV COMPOSER_MEMORY_LIMIT=-1

# Jalankan install composer (tanpa dev dependencies untuk production)
RUN composer install --no-dev --no-scripts

# Atur hak akses folder storage dan cache agar bisa ditulis oleh Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Ekspos port 9000 untuk berkomunikasi dengan Nginx
EXPOSE 9000

CMD ["php-fpm"]
