FROM php:8.4-apache

LABEL org.opencontainers.image.source=https://github.com/rahmadwidiansyah/Bendaharaku

# --- 1. Install Dependencies Sistem & Node.js 20 (LTS) ---
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
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# --- 2. Install Ekstensi PHP ---
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

# Install Redis
RUN pecl install redis && docker-php-ext-enable redis

# --- 3. Konfigurasi Apache untuk Laravel ---
ENV APACHE_DOCUMENT_ROOT=/var/www/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Aktifkan mod_rewrite agar routing Laravel bisa jalan
RUN a2enmod rewrite

# --- 4. Install Composer ---
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www
COPY . .

# --- 5. Build Backend (Laravel) ---
ENV COMPOSER_MEMORY_LIMIT=-1
# 👉 FIX: Hapus paksa file cache lokal yang tidak sengaja terbawa oleh perintah 'COPY . .'
RUN rm -f bootstrap/cache/*.php
# 👉 OPTIMASI: Hapus '--no-scripts' agar Laravel bisa menjalankan 'package:discover' versi produksi secara bersih
RUN composer install --no-dev --optimize-autoloader

# --- 6. Build Frontend (Vue/Inertia) ---
ENV NODE_OPTIONS="--max-old-space-size=4096"
RUN npm ci
RUN npm run build

# --- 7. Expose Port ---
EXPOSE 80

# --- 8. Command Startup ---
# 👉 OPTIMASI: Tambahkan pembersihan cache bootstrap di runtime agar lebih aman sebelum Apache jalan
CMD ["sh", "-c", "rm -rf public/storage bootstrap/cache/*.php && php artisan storage:link && chown -R www-data:www-data storage bootstrap/cache public/storage && chmod -R 775 storage bootstrap/cache public/storage && apache2-foreground"]