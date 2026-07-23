#!/bin/bash
set -e

cd /var/www/html

set_env() {
    key="$1"
    value="$2"
    if grep -q "^${key}=" .env 2>/dev/null; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

wait_for_app() {
    until [ -f artisan ] && [ -f vendor/autoload.php ]; do
        echo "Waiting for Laravel application to be bootstrapped by the app container..."
        sleep 3
    done
}

if [ "${CONTAINER_ROLE}" = "app" ]; then
    if [ ! -f artisan ]; then
        echo "No Laravel application found in ./backend — scaffolding a new one..."
        composer create-project laravel/laravel tmp_laravel_app --prefer-dist --no-interaction
        cp -a tmp_laravel_app/. .
        rm -rf tmp_laravel_app
    fi

    if [ ! -f .env ]; then
        cp .env.example .env
    fi

    composer install --no-interaction --prefer-dist --optimize-autoloader

    set_env "DB_CONNECTION" "pgsql"
    set_env "DB_HOST" "${DB_HOST:-db}"
    set_env "DB_PORT" "${DB_PORT:-5432}"
    set_env "DB_DATABASE" "${DB_DATABASE:-laravel}"
    set_env "DB_USERNAME" "${DB_USERNAME:-laravel}"
    set_env "DB_PASSWORD" "${DB_PASSWORD:-secret}"
    set_env "REDIS_HOST" "${REDIS_HOST:-redis}"
    set_env "REDIS_PORT" "${REDIS_PORT:-6379}"
    set_env "QUEUE_CONNECTION" "redis"
    set_env "SESSION_DRIVER" "redis"
    set_env "CACHE_STORE" "redis"
    if [ -n "${APP_URL}" ]; then
        set_env "APP_URL" "${APP_URL}"
    fi

    if ! grep -q "^APP_KEY=base64" .env 2>/dev/null; then
        php artisan key:generate --force
    fi

    # Ensure Laravel writable dirs exist (bind mounts on Windows often lack them / correct ownership).
    mkdir -p \
        storage/framework/{cache/data,sessions,views,testing} \
        storage/app/{public,private} \
        storage/logs \
        bootstrap/cache

    # PHP-FPM workers run as www-data; without write access, Laravel's Filesystem::replace()
    # calls tempnam() which falls back to /tmp and emits:
    # "tempnam(): file created in the system's temporary directory"
    chown -R www-data:www-data storage bootstrap/cache || true
    chmod -R ug+rwx storage bootstrap/cache || true

    echo "Waiting for database to accept connections..."
    until php -r "new PDO('pgsql:host=${DB_HOST:-db};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-laravel}', '${DB_USERNAME:-laravel}', '${DB_PASSWORD:-secret}');" 2>/dev/null; do
        sleep 2
    done

    php artisan migrate --force || true
else
    wait_for_app
fi

# Always fix writable dirs for app + queue (volume mounts can reset ownership).
if [ -f artisan ]; then
    mkdir -p \
        storage/framework/{cache/data,sessions,views,testing} \
        storage/app/{public,private} \
        storage/logs \
        bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true
fi

exec "$@"
