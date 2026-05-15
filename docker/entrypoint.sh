#!/usr/bin/env bash
set -euo pipefail

ROLE="${CONTAINER_ROLE:-app}"
APP_PATH="/var/www/html"
INIT_MARKER="${APP_PATH}/storage/.docker-initialized"

cd "${APP_PATH}"

wait_for_db() {
    echo "[entrypoint] Waiting for database ${DB_HOST}:${DB_PORT}..."
    until php -r "
        try {
            new PDO(
                'pgsql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_DATABASE'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD')
            );
            exit(0);
        } catch (Throwable \$e) {
            exit(1);
        }
    " >/dev/null 2>&1; do
        sleep 2
    done
    echo "[entrypoint] Database is reachable."
}

ensure_vendor() {
    if [ ! -f "${APP_PATH}/vendor/autoload.php" ]; then
        echo "[entrypoint] vendor/ missing — running composer install."
        composer install --no-interaction --prefer-dist --no-progress
    fi
}

ensure_app_key() {
    if ! grep -qE '^APP_KEY=base64:' "${APP_PATH}/.env" 2>/dev/null; then
        echo "[entrypoint] Generating APP_KEY."
        php artisan key:generate --force
    fi
}

run_migrations() {
    if [ ! -f "${INIT_MARKER}" ]; then
        echo "[entrypoint] First boot — running migrate --seed."
        php artisan migrate --force --seed
        mkdir -p "$(dirname "${INIT_MARKER}")"
        touch "${INIT_MARKER}"
    else
        echo "[entrypoint] Running pending migrations."
        php artisan migrate --force
    fi
}

ensure_storage_link() {
    if [ ! -e "${APP_PATH}/public/storage" ]; then
        php artisan storage:link || true
    fi
}

wait_for_vendor() {
    echo "[entrypoint] Waiting for vendor/autoload.php (initialized by the app service)..."
    while [ ! -f "${APP_PATH}/vendor/autoload.php" ]; do
        sleep 2
    done
    echo "[entrypoint] vendor ready."
}

case "${ROLE}" in
    app)
        ensure_vendor
        wait_for_db
        ensure_app_key
        run_migrations
        ensure_storage_link
        ;;
    queue)
        wait_for_vendor
        wait_for_db
        ;;
    node)
        wait_for_vendor
        ;;
esac

exec "$@"
