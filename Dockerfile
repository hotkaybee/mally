# Multi-stage Dockerfile for Mally (Laravel 12 + Filament 4) Production

# Stage 1: PHP dependencies
FROM composer:2 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --ignore-platform-reqs


# Stage 2: Frontend assets (Vite + Tailwind 4)
FROM node:20-alpine AS asset-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
RUN npm run build


# Stage 3: Production image
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    bash \
    nano \
    oniguruma-dev \
    pkgconfig \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        gd \
        zip \
        mbstring \
        intl \
        opcache \
        bcmath \
    && rm -rf /var/cache/apk/*

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy Nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy Supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .
COPY --from=composer-builder --chown=www-data:www-data /app/vendor ./vendor
COPY --from=asset-builder --chown=www-data:www-data /app/public/build ./public/build

# Set permissions
RUN chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    && chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

RUN mkdir -p /var/log/supervisor /var/log/php /var/log/nginx \
    && chown -R www-data:www-data /var/log/supervisor

# Publish Filament/Livewire static assets
RUN php artisan filament:assets

# Create entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
