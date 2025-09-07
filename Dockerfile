# ------------------------
# 1. Build frontend assets
# ------------------------
FROM node:18 AS build
WORKDIR /app

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
    && docker-php-ext-install pdo_pgsql

# Enable Apache mod_rewrite and set DocumentRoot to /var/www/html/public
RUN a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy composer files + artisan + bootstrap + config
COPY composer.json composer.lock artisan ./
COPY bootstrap ./bootstrap
COPY config ./config


# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy full app source
COPY . .

# Copy built frontend assets
COPY --from=build /app/public/build /var/www/html/public/build

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public

# Expose port
EXPOSE 80

# ------------------------
# Healthcheck
# ------------------------
HEALTHCHECK --interval=30s --timeout=10s --retries=3 \
  CMD curl -f http://localhost/ || exit 1

# ------------------------
# Start command
# ------------------------
CMD php artisan config:clear \
    && php artisan cache:clear \
    && php artisan route:clear \
    && php artisan view:clear \
    && apache2-foreground
