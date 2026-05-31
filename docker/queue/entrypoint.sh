#!/bin/sh
# ─────────────────────────────────────────────────────────────────────────────
# Queue worker container entrypoint
#
# Clears stale config cache before starting the worker so it picks up the
# correct REDIS_HOST / QUEUE_CONNECTION from the container env rather than
# from a stale bootstrap/cache/config.php.
# ─────────────────────────────────────────────────────────────────────────────
set -e

echo "[queue-entrypoint] Clearing Laravel caches..."

php artisan config:clear 2>/dev/null && echo "[queue-entrypoint] config cache cleared" || echo "[queue-entrypoint] config:clear skipped"
php artisan cache:clear  2>/dev/null || true

echo "[queue-entrypoint] Starting queue worker..."
exec "$@"
