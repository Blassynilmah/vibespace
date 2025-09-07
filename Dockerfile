# Build frontend
FROM node:18 as build
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm install
COPY . .
RUN npm run build

# PHP + Apache
FROM php:8.2-apache

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql



# Enable Apache mod_rewrite and set DocumentRoot to /var/www/html/public
RUN a2enmod rewrite \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Set working directory
WORKDIR /var/www/html

# Copy app files
COPY . /var/www/html
COPY --from=build /app/public /var/www/html/public


# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Optimize Laravel caches
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

# Expose port
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]