# Use official PHP image with extensions
FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev zip unzip nodejs npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Set working directory
WORKDIR /var/www

# Copy composer first (better caching)
COPY composer.json composer.lock ./

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy rest of the app
COPY . .

# Install JS dependencies and build assets
RUN npm install && npm run build

# Expose port
EXPOSE 8000

# Run Laravel server
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000
