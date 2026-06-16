# Stage 1: Build assets using Node.js
FROM node:20-alpine AS assets-builder
WORKDIR /app
COPY . .
RUN npm ci && npm run build

# Stage 2: Build PHP environment
FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    unzip \
    git \
    curl \
    oniguruma-dev \
    bash

# Configure & Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        opcache \
        bcmath

# Install Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Copy built frontend assets from Node stage
COPY --from=assets-builder /app/public/build ./public/build

# Set permissions for Laravel storage and bootstrap/cache
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Run composer install (optimized for production)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Expose port 9000
EXPOSE 9000

# Set entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
