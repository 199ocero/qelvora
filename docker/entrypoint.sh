#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

# Rebuild the framework caches against the current environment.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Only the primary web container runs migrations, so the worker and
# scheduler containers don't race it on startup.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force
fi

exec "$@"
