#!/bin/sh
# Entrypoint de production (L11). POSIX sh : l'image finale est alpine.
#
# Le cache de configuration est constitué ICI, au démarrage, jamais à la
# construction : les variables d'environnement sont injectées à l'exécution.
# Un config:cache au build figerait des valeurs vides (piège classique).
#
# Aucune migration n'est lancée ici : la migration est une étape explicite de
# la chaîne de déploiement (L16), pas un effet de bord du démarrage.
set -eu

APP_PATH="/var/www/html"
cd "${APP_PATH}"

wait_for_db() {
    echo "[entrypoint] Attente de la base ${DB_HOST:-db}:${DB_PORT:-5432}..."
    until php -r '
        try {
            new PDO(
                "pgsql:host=" . getenv("DB_HOST") . ";port=" . getenv("DB_PORT") . ";dbname=" . getenv("DB_DATABASE"),
                getenv("DB_USERNAME"),
                getenv("DB_PASSWORD")
            );
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    ' >/dev/null 2>&1; do
        sleep 2
    done
    echo "[entrypoint] Base joignable."
}

build_caches() {
    echo "[entrypoint] Constitution des caches (config, route, view, event)."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
}

ensure_storage_link() {
    if [ ! -e "${APP_PATH}/public/storage" ]; then
        php artisan storage:link || true
    fi
}

wait_for_db
build_caches
ensure_storage_link

exec "$@"
