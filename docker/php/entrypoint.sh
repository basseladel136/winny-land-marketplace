#!/bin/sh
# ─────────────────────────────────────────────────────────────────────────────
# PHP-FPM container entrypoint
#
# Runs before php-fpm starts.  Clears any stale Laravel caches that may have
# been baked with wrong env values (old Redis host, wrong queue driver, etc.)
# so the container always boots with live env var values.
# ─────────────────────────────────────────────────────────────────────────────
set -e

# Working directory is /var/www/html/backend (set in Dockerfile WORKDIR and
# docker-compose working_dir).  Both volume mounts must be in place before
# this script runs — Docker guarantees that.

echo "[entrypoint] Clearing Laravel application caches..."

# Clear config cache — baked values override container env vars, so must go.
php artisan config:clear  2>/dev/null && echo "[entrypoint] config cache cleared"  || echo "[entrypoint] config:clear skipped (artisan not ready yet)"

# Clear other caches that may reference stale config.
php artisan cache:clear   2>/dev/null && echo "[entrypoint] application cache cleared"   || true
php artisan route:clear   2>/dev/null && echo "[entrypoint] route cache cleared"   || true
php artisan view:clear    2>/dev/null && echo "[entrypoint] view cache cleared"    || true

# Ensure writable directories exist (idempotent).
mkdir -p storage/framework/sessions \
         storage/framework/views \
         storage/framework/cache \
         storage/logs \
         bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

echo "[entrypoint] Starting php-fpm..."
# exec replaces this shell with the CMD passed by docker-compose / Dockerfile.
exec "$@"
