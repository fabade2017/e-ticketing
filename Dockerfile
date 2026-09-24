FROM php:8.3-apache

# System deps
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev libicu-dev libpq-dev \
    nodejs npm \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app
COPY . .
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm install --ignore-scripts \
    && npm run build \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Apache vhost for Laravel public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80

CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf && sed -i "s/:80/:${PORT:-80}/" /etc/apache2/sites-available/000-default.conf && php artisan config:clear && php artisan migrate --force && php artisan db:seed --force && php artisan storage:link || true && apache2-foreground
