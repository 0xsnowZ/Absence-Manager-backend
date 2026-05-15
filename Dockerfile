# ─── Stage 1: Build dependencies ──────────────────────────────────────────────
FROM php:8.4-cli-alpine AS builder

# System deps needed to compile PHP extensions
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    zip \
    unzip \
    git

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    opcache

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first (layer cache — only re-runs if composer.json changes)
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev, no scripts yet)
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --optimize-autoloader \
    --prefer-dist

# Copy the rest of the application
COPY . .

# Generate optimized autoloader with full app code present
RUN composer dump-autoload --optimize --no-dev

# Fix storage & cache directory permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
             storage/logs \
             bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# ─── Stage 2: Runtime image ───────────────────────────────────────────────────
FROM php:8.4-cli-alpine

# Only runtime system deps
RUN apk add --no-cache \
    postgresql-dev \
    libzip \
    oniguruma-dev

# Install runtime PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    zip \
    opcache

# Tune OPcache for production
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
} > /usr/local/etc/php/conf.d/opcache.ini

WORKDIR /app

# Copy compiled app from builder stage
COPY --from=builder /app .

# Expose the port Render will assign via $PORT env var
EXPOSE 8000

# Entrypoint: migrate then start the server
# Using sh -c so that $PORT is expanded at runtime
CMD sh -c "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"
