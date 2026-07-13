#!/bin/sh
set -e

# Runs on every container start, before php-fpm takes over.
# Kept idempotent so restarts / rebuilds are safe.

# Ensure runtime dirs exist and are writable (named volume may start empty).
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

# Cache config/routes/views for production performance.
# These read APP_KEY / DB_* from the environment at runtime, which is why we
# cache here (at start) rather than at build time.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link public/storage -> storage/app/public (safe if it already exists).
php artisan storage:link || true

# Apply database migrations. Guard with RUN_MIGRATIONS=false to disable on
# replicas or when you prefer to run migrations as a separate one-off step.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force
fi

# Hand off to the CMD (php-fpm) as PID 1.
exec "$@"
