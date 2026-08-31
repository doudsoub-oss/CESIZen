# CESIZen

Application web de santé mentale et de gestion du stress. Monolithe
**Laravel 13 / Inertia v2 / Vue 3 / PostgreSQL 16**, servi sous Docker Compose.

## Démarrage en développement

Le poste de développement tourne sous Docker Compose. Le runbook complet est
dans [`docs/docker.md`](docs/docker.md).

```bash
cp .env.docker.example .env        # configuration de développement
docker compose up -d               # premier boot : install, migrate, seed automatiques
```

L'application écoute sur http://localhost:8080, Mailpit sur http://localhost:8025.

Pour un lancement hors Docker, copier `.env.example` en `.env`, renseigner la
connexion PostgreSQL, puis `php artisan key:generate` et `php artisan migrate`.

## Tests

```bash
php artisan test --compact                       # toute la suite
php artisan test --compact --filter=NomDuTest    # un test ciblé
```

## Sécurité — détection de secrets (gitleaks)

Aucun secret n'est versionné : les variables sont documentées **sans valeur**
dans [`.env.example`](.env.example), et injectées à l'exécution. La rotation de
chaque secret est décrite dans
[`docs/exploitation/rotation-des-secrets.md`](docs/exploitation/rotation-des-secrets.md).

Le dépôt est analysé par [gitleaks](https://github.com/gitleaks/gitleaks),
configuré dans [`.gitleaks.toml`](.gitleaks.toml).

**Activer le hook pre-commit** (une fois par clone) :

```bash
git config core.hooksPath .githooks
```

Le hook exécute `gitleaks protect --staged` avant chaque commit. Installe
d'abord gitleaks localement ; à défaut, le hook avertit sans bloquer, la chaîne
d'intégration (lot L13) restant le filet de sécurité.

Analyser l'historique complet à la demande :

```bash
gitleaks detect --source . --log-opts="--all"
```

Un faux positif se documente dans l'allowlist de `.gitleaks.toml` — on ne baisse
jamais la sensibilité globale.

## Contribuer

Modèle de branches, règles de protection et chaîne de traçabilité :
[`CONTRIBUTING.md`](CONTRIBUTING.md). Convention de commit :
[`docs/convention-de-commit.md`](docs/convention-de-commit.md).

## Documentation

- [`CONTRIBUTING.md`](CONTRIBUTING.md) — modèle de branches et de contribution (L02)
- [`docs/convention-de-commit.md`](docs/convention-de-commit.md) — convention de commit (L02)
- [`docs/audit-conformite-bloc3.md`](docs/audit-conformite-bloc3.md) — audit dossier ↔ code (L00)
- [`docs/exploitation/rotation-des-secrets.md`](docs/exploitation/rotation-des-secrets.md) — rotation des secrets (L01)
- [`docs/docker.md`](docs/docker.md) — runbook Docker (développement)
- [`docs/database-schema.md`](docs/database-schema.md) — schéma de base de données faisant autorité
