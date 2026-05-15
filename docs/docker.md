# Docker setup — CESIZen

This document is the canonical reference for running CESIZen locally with Docker.

## Stack

| Service | Image | Role |
|---|---|---|
| `app` | `cesizen/app:dev` (built from `docker/Dockerfile`) | PHP 8.4-FPM running the Laravel app |
| `web` | `nginx:1.27-alpine` | HTTP front, proxies PHP to `app:9000` |
| `db` | `postgres:16-alpine` | PostgreSQL 16 database |
| `node` | `node:22-alpine` | Vite dev server (HMR) |
| `queue` | `cesizen/app:dev` | `php artisan queue:work` worker |

The stack deviates from the cahier des charges §8.2 (which listed Blade + Alpine.js): the project actually uses **Inertia + Vue 3 + Tailwind v4**. Database engine matches the spec (**PostgreSQL**). The authoritative database schema is `docs/database-schema.md` — the spec PDF's §8.3 is outdated and must not be used.

## Prerequisites

- Docker Engine ≥ 24
- Docker Compose v2 (`docker compose ...`, not `docker-compose`)
- A POSIX shell (Linux / macOS / WSL)

No PHP, Composer, Node or Postgres needed on the host.

## First run

```bash
cp .env.docker.example .env
docker compose up --build
```

On first boot the `app` entrypoint will:
1. Wait until Postgres is reachable.
2. Run `composer install` if `vendor/` is missing.
3. Run `php artisan key:generate` if `APP_KEY` is empty in `.env`.
4. Run `php artisan migrate --force --seed` (one-shot, marked by `storage/.docker-initialized`).
5. Run `php artisan storage:link`.

The `node` service runs `npm install` on first start, then `npm run dev` with HMR on host port `5173`. Vite assets are loaded by the browser directly from `http://localhost:5173` while the page itself is served by Nginx on `http://localhost:8080`.

When `docker compose up` settles, the app is available at:

- **App**: <http://localhost:8080>
- **Vite HMR**: <http://localhost:5173> (used by the browser, not directly by you)
- **Postgres**: `localhost:5432` (user/db/password all `cesizen` by default)

Seeded accounts (password `password`):

- `superadmin@cesizen.fr` — super_admin
- `admin@cesizen.fr` — admin
- `user@cesizen.fr` — user

## Environment variables

`.env.docker.example` is the template. Notable keys beyond defaults:

| Key | Default | Purpose |
|---|---|---|
| `APP_PORT` | `8080` | Host port for Nginx |
| `VITE_PORT` | `5173` | Host port for the Vite dev server |
| `DB_PORT_HOST` | `5432` | Host port for Postgres (only needed if you connect from a host-side client) |
| `UID` / `GID` | `1000` | Used when building the `app` image so bind-mounted files are owned by your host user. Override with `UID=$(id -u) GID=$(id -g) docker compose build` if your host UID differs. |

## Command cheat sheet

| Action | Command |
|---|---|
| Start (foreground) | `docker compose up` |
| Start (detached) | `docker compose up -d` |
| Stop | `docker compose down` |
| Rebuild images | `docker compose build` |
| Tail logs | `docker compose logs -f app web node queue` |
| Shell in app | `docker compose exec app bash` |
| Artisan | `docker compose exec app php artisan <cmd>` |
| Composer | `docker compose exec app composer <cmd>` |
| Tests | `docker compose exec app php artisan test --compact` |
| Pint (format PHP) | `docker compose exec app vendor/bin/pint --format agent` |
| npm | `docker compose exec node npm <cmd>` |
| Postgres CLI | `docker compose exec db psql -U cesizen -d cesizen` |

## Resetting the database

```bash
docker compose down
docker volume rm cesizen_cesizen-pgdata
rm storage/.docker-initialized
docker compose up
```

The marker file `storage/.docker-initialized` triggers re-seeding on the next boot.

## Permissions

The `app` image creates a user `app` with `UID=GID=1000` by default. If `id -u` on your host is not 1000, build with matching UID/GID to avoid bind-mount permission errors:

```bash
UID=$(id -u) GID=$(id -g) docker compose build app queue
```

## Troubleshooting

- **`Connection refused` on first boot** — the `app` service is waiting for the database. Watch `docker compose logs db` and let the healthcheck pass.
- **Vite assets not loading** — confirm `npm run dev` is running (`docker compose logs node`). The browser fetches assets directly from `http://localhost:5173`; if your `APP_URL` host differs, Vite's hot file won't match.
- **`Permission denied` on `storage/` or `bootstrap/cache/`** — rebuild with `UID=$(id -u) GID=$(id -g) docker compose build`.
- **Stuck migration** — `docker compose exec app php artisan migrate:status` then `docker compose exec app php artisan migrate --force` to retry.
