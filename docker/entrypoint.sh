#!/bin/sh

set -eu

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Aplicando migrations..."
    php artisan migrate --force
fi

if [ "${RUN_DB_SEED:-false}" = "true" ]; then
    echo "Executando seeders..."
    php artisan db:seed --force
fi

exec /usr/bin/supervisord -c /app/docker/supervisord.conf
