# ================================================================
# STAGE 1: Vendor — Composer dependencies
# ================================================================
FROM composer:latest AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .
RUN composer install \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# ================================================================
# STAGE 2: Frontend — npm build
# ================================================================
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN npm run build

# ================================================================
# STAGE 3: Production — PHP + Apache + Python
# ================================================================
FROM php:8.4-apache

LABEL org.opencontainers.image.source=https://github.com/rahmadwidiansyah/Bendaharaku

# --- 1. Install Python ---
RUN apt-get update && apt-get install -y --no-install-recommends \
    python3 \
    python3-pip \
    python3-venv \
    tesseract-ocr \
    libtesseract-dev \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    curl \
    && rm -rf /var/lib/apt/lists/*

# --- 2. Create Python virtual environment ---
ENV VIRTUAL_ENV=/opt/venv
RUN python3 -m venv $VIRTUAL_ENV
ENV PATH="$VIRTUAL_ENV/bin:$PATH"

# --- 3. Install PHP extensions ---
RUN docker-php-ext-install \
    pdo pdo_pgsql pgsql \
    mbstring exif pcntl bcmath gd zip

RUN pecl install redis && docker-php-ext-enable redis

# --- 4. Apache configuration ---
ENV APACHE_DOCUMENT_ROOT=/var/www/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

# --- 5. Copy build artifacts from previous stages ---
WORKDIR /var/www

COPY --from=vendor /app/vendor /var/www/vendor
COPY --from=frontend /app/public/build /var/www/public/build

# --- 6. Copy application source ---
# vendor/ and node_modules/ excluded by .dockerignore
COPY . .

# --- 7. Install Python AI Parser dependencies (inside venv) ---
RUN pip install --no-cache-dir -r /var/www/script_pencatat_keuangan/requirements.txt \
    && rm -rf /root/.cache/pip

# --- 8. Fix permissions (Apache runs as www-data) ---
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# --- 9. Laravel finalization ---
RUN rm -f bootstrap/cache/*.php \
    && php artisan storage:link --force 2>/dev/null || true

# Default log to stdout in Docker
ENV LOG_CHANNEL=stdout

# --- 10. Entrypoint ---
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80 3987 8000

STOPSIGNAL SIGTERM

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache-only"]
