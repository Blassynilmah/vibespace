# ---------------------------
# Stage 1: Build assets
# ---------------------------
FROM node:20 AS frontend

WORKDIR /app

# Copy package files first for caching
COPY package.json package-lock.json* ./

# Install dependencies
RUN npm install

# Copy all frontend resources
COPY resources ./resources
COPY vite.config.js ./

# Build assets
RUN npm run build

# ---------------------------
# Stage 2: PHP + Composer
# ---------------------------
FROM php:8.3-fpm AS backend

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libjpeg-dev libfreetype6-dev zip unzip libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd bcmath pdo_pgsql

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first for dependency cache
COPY composer.json composer.lock ./

# Copy rest of the app (including artisan and all source files)
COPY . .

# Ensure Laravel cache and storage directories exist and are writable
RUN mkdir -p bootstrap/cache \
    && mkdir -p storage/framework/cache \
    && mkdir -p storage/framework/sessions \
    && mkdir -p storage/framework/views \
    && touch bootstrap/cache/.gitignore \
    && touch storage/framework/cache/.gitignore \
    && touch storage/framework/sessions/.gitignore \
    && touch storage/framework/views/.gitignore \
    && chown -R www-data:www-data storage bootstrap/cache

# Install PHP dependencies (no dev, optimized for prod)
RUN composer install --no-dev --optimize-autoloader

# Copy built frontend assets from stage 1
COPY --from=frontend /app/public/build ./public/build

# Expose port
EXPOSE 8000

# Start Laravel with PHP built-in server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]