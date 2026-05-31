# ─── Stage: Queue worker — same PHP 8.4 base as php service ──────────────────
FROM php:8.4-fpm-alpine

# ─── System dependencies ──────────────────────────────────────────────────────
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    gmp-dev \
    $PHPIZE_DEPS

# ─── PHP extensions ───────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        mbstring \
        gd \
        intl \
        bcmath \
        opcache \
        pcntl \
        gmp \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && rm -rf /tmp/pear

# ─── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ─── Non-root user ────────────────────────────────────────────────────────────
RUN addgroup -g 1000 -S www \
 && adduser  -u 1000 -S www -G www

# ─── Working directory is the backend sub-folder ──────────────────────────────
WORKDIR /var/www/html/backend

# ─── Install Composer dependencies (cached layer) ─────────────────────────────
COPY --chown=www:www backend/composer.json backend/composer.lock ./
RUN composer install \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# ─── Copy full backend source ─────────────────────────────────────────────────
COPY --chown=www:www backend/ ./

# ─── Generate optimised autoloader ───────────────────────────────────────────
RUN composer dump-autoload --optimize --no-interaction

# ─── Ensure Laravel writable directories exist with correct permissions ───────
RUN mkdir -p \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/cache \
        storage/logs \
        bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache \
 && chown -R www:www storage bootstrap/cache

# ─── Entrypoint: clears stale caches before queue worker starts ──────────────
COPY docker/queue/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# queue:work auto-restarts via docker-compose restart: unless-stopped
CMD ["php", "artisan", "queue:work", "redis", \
     "--sleep=3", "--tries=3", "--timeout=90", "--max-time=3600"]
