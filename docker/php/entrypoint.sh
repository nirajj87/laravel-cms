#!/bin/sh
set -e

cd /var/www/html

if [ "$INSTALL_DEPS" = "true" ] && [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if [ "$INSTALL_DEPS" != "true" ]; then
    while [ ! -f vendor/autoload.php ]; do
        echo "Waiting for Composer dependencies..."
        sleep 2
    done
fi

if [ -d storage ] && [ -d bootstrap/cache ]; then
    chmod -R a+rwx storage bootstrap/cache 2>/dev/null || true
fi

if [ -f artisan ] && [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

exec "$@"
