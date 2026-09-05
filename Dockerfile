# --- Tahap 1: Build Frontend (Node 22) ---
FROM node:22-slim AS frontend-builder
WORKDIR /app
COPY . .
RUN npm install && npm run build

# --- Tahap 2: Runtime (PHP 8.4 + Nginx) ---
FROM php:8.4-fpm-bullseye

# Install tools, Nginx, dan ekstensi PHP
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libsqlite3-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install bcmath gd intl pdo_pgsql pdo_sqlite zip

# Instalasi Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Setup Working Directory
WORKDIR /var/www/html
COPY . .
COPY --from=frontend-builder /app/public/build ./public/build

# Install PHP Dependencies
RUN composer install --no-dev --optimize-autoloader

# Setup Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Copy konfigurasi Nginx & Supervisor
COPY ./docker-config/nginx-single.conf /etc/nginx/sites-available/default
COPY ./docker-config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Script untuk jalankan Nginx & PHP barengan, serta otomatisasi DB
RUN printf "#!/bin/sh\n\
# Pastikan .env ada\n\
if [ ! -f .env ]; then\n\
    cp .env.example .env\n\
fi\n\
\n\
# Pastikan APP_KEY ada\n\
if ! grep -q \"APP_KEY=base64:\" .env; then\n\
    php artisan key:generate\n\
fi\n\
\n\
# Pastikan file SQLite ada dan folder database bisa ditulisi\n\
touch /var/www/html/database/database.sqlite\n\
chown -R www-data:www-data /var/www/html/database\n\
chmod -R 775 /var/www/html/database\n\
\n\
# Jalankan migrasi\n\
php artisan migrate --force\n\
\n\
# Start PHP-FPM dan Nginx\n\
php-fpm -D\n\
nginx -g 'daemon off;'\n" > /start.sh && chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]
