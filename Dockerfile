# ------------------------
# 1. Build frontend assets
# ------------------------
FROM node:18 AS build
WORKDIR /app

# Install dependencies and build frontend
COPY package*.json vite.config.js ./
RUN npm install

COPY . .
RUN npm run build

# ------------------------
# 2. PHP + Apache runtime
# ------------------------
FROM php:8.2-apache

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    curl \
    vim \
    less \
    && docker-php-ext-install pdo pdo_pgsql

# Enable Apache mod_rewrite and set DocumentRoot to /var/www/html/public
RUN a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy full Laravel source
COPY . .

# Copy built frontend assets from Node build
COPY --from=build /app/public/build /var/www/html/public/build

# Install Composer & PHP dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public

# ------------------------
# Setup log tailing for debug
# ------------------------
# Stream Laravel logs to stdout so you can see them in Render
RUN touch /var/www/html/storage/logs/laravel.log \
    && chown www-data:www-data /var/www/html/storage/logs/laravel.log

# Apache error log will also go to stdout
RUN ln -sf /dev/stdout /var/log/apache2/error.log \
    && ln -sf /dev/stdout /var/log/apache2/access.log

# Expose port
EXPOSE 80

# ------------------------
# Healthcheck
# ------------------------
HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

# ------------------------
# Start command with cache clear & log tail
# ------------------------
CMD php artisan config:clear \
    && php artisan cache:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && tail -F /var/www/html/storage/logs/laravel.log \
    & apache2-foreground
