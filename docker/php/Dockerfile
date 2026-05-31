# ─── Stage: PHP-FPM 8.4 (Laravel 12 / PHPUnit 12 require PHP >= 8.4) ────────
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

# ─── Working directory is backend sub-folder (build context = project root) ──
WORKDIR /var/www/html/backend

# ─── Install Composer dependencies (cached layer) ─────────────────────────────
# Copy only lock files first so Docker can cache this layer unless deps change.
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

# ─── Entrypoint: clears stale Laravel caches before php-fpm starts ───────────
# The COPY source path is relative to the build context (project root).
COPY docker/php/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

USER www

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]
