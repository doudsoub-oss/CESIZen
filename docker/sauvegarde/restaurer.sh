#!/bin/sh
# Restauration d'une sauvegarde chiffrée (L19, engagement 2.8).
#
# Déchiffre une sauvegarde produite par sauvegarder.sh, vérifie son empreinte
# SHA-256 contre le manifeste, restaure avec pg_restore dans une base CIBLE, puis
# contrôle l'intégrité au niveau SQL : présence et comptage des tables
# principales, absence d'orphelins sur une clé étrangère.
#
# Le contrôle « déchiffrement réussi d'un diagnostic » (qui valide en plus la
# cohérence de l'APP_KEY) nécessite le runtime applicatif ; il est exécuté par
# l'orchestrateur tester-restauration.sh via `php artisan
# diagnostics:verifier-dechiffrement` après cette restauration.
#
# SÉCURITÉ : refuse de restaurer sur la base de production (DB_DATABASE) sans le
# drapeau explicite --confirmer-production. Ne jamais afficher la phrase secrète.
#
# Usage :
#   restaurer.sh --base <base_cible> [--fichier <chemin.gpg>] [--confirmer-production]
#   (par défaut, --fichier = la sauvegarde la plus récente de BACKUP_DIR)
set -eu

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-5432}"
DB_USERNAME="${DB_USERNAME:?DB_USERNAME requis}"
DB_PASSWORD="${DB_PASSWORD:?DB_PASSWORD requis}"
DB_DATABASE="${DB_DATABASE:?DB_DATABASE requis}" # base de production, pour le garde-fou
BACKUP_PASSPHRASE="${BACKUP_PASSPHRASE:?BACKUP_PASSPHRASE requis}"
BACKUP_DIR="${BACKUP_DIR:-/backups}"
MANIFEST="${BACKUP_DIR}/manifest.sha256"

BASE_CIBLE=""
FICHIER=""
CONFIRMER_PROD="non"

while [ "$#" -gt 0 ]; do
    case "$1" in
        --base) BASE_CIBLE="$2"; shift 2 ;;
        --fichier) FICHIER="$2"; shift 2 ;;
        --confirmer-production) CONFIRMER_PROD="oui"; shift ;;
        *) echo "Argument inconnu : $1" >&2; exit 2 ;;
    esac
done

[ -n "$BASE_CIBLE" ] || { echo "--base <base_cible> est obligatoire" >&2; exit 2; }

# Garde-fou production : jamais de restauration sur la base vive sans confirmation.
if [ "$BASE_CIBLE" = "$DB_DATABASE" ] && [ "$CONFIRMER_PROD" != "oui" ]; then
    echo "REFUS : « ${BASE_CIBLE} » est la base de production. Ajouter --confirmer-production pour forcer." >&2
    exit 3
fi

# Fichier par défaut : la sauvegarde la plus récente.
if [ -z "$FICHIER" ]; then
    FICHIER="$(ls -1t "${BACKUP_DIR}"/cesizen-*.dump.gpg 2>/dev/null | head -1 || true)"
fi
[ -n "$FICHIER" ] && [ -f "$FICHIER" ] || { echo "Aucun fichier de sauvegarde trouvé (${FICHIER:-néant})" >&2; exit 1; }

BASENAME="$(basename "$FICHIER")"
echo "Restauration de ${BASENAME} → base « ${BASE_CIBLE} »"

# ── 1. Vérification de l'empreinte SHA-256 contre le manifeste ────────────────
if [ -f "$MANIFEST" ]; then
    ATTENDU="$(grep "  ${BASENAME}\$" "$MANIFEST" | tail -1 | cut -d' ' -f1 || true)"
    if [ -n "$ATTENDU" ]; then
        OBTENU="$(sha256sum "$FICHIER" | cut -d' ' -f1)"
        if [ "$ATTENDU" != "$OBTENU" ]; then
            echo "ÉCHEC : empreinte SHA-256 non conforme au manifeste (sauvegarde altérée)" >&2
            exit 1
        fi
        echo "Empreinte SHA-256 conforme au manifeste."
    else
        echo "AVERTISSEMENT : aucune entrée de manifeste pour ${BASENAME} — contrôle d'empreinte ignoré." >&2
    fi
else
    echo "AVERTISSEMENT : manifeste absent — contrôle d'empreinte ignoré." >&2
fi

# ── 2. Déchiffrement + restauration (pg_restore) ──────────────────────────────
PASSFILE=""
nettoyer() { [ -n "$PASSFILE" ] && rm -f "$PASSFILE" 2>/dev/null || true; }
trap nettoyer EXIT INT TERM
PASSFILE="$(mktemp)"; chmod 600 "$PASSFILE"
printf '%s' "$BACKUP_PASSPHRASE" > "$PASSFILE"

if ! gpg --decrypt --batch --quiet --passphrase-file "$PASSFILE" "$FICHIER" 2>/dev/null \
     | PGPASSWORD="$DB_PASSWORD" pg_restore \
         --host="$DB_HOST" --port="$DB_PORT" --username="$DB_USERNAME" \
         --dbname="$BASE_CIBLE" --no-owner --no-privileges --clean --if-exists
then
    echo "ÉCHEC : déchiffrement ou pg_restore" >&2
    exit 1
fi

# ── 3. Contrôles d'intégrité SQL ──────────────────────────────────────────────
psql_cible() {
    PGPASSWORD="$DB_PASSWORD" psql --host="$DB_HOST" --port="$DB_PORT" \
        --username="$DB_USERNAME" --dbname="$BASE_CIBLE" -tAc "$1"
}

# 3a. Présence et comptage des tables principales.
TABLES="users diagnostics diagnostic_responses questionnaires questions answer_options result_interpretations categories contents"
for t in $TABLES; do
    n="$(psql_cible "SELECT count(*) FROM ${t};" 2>/dev/null || echo "ERREUR")"
    if [ "$n" = "ERREUR" ]; then
        echo "ÉCHEC : table principale « ${t} » absente ou illisible après restauration" >&2
        exit 1
    fi
    echo "  table ${t} : ${n} ligne(s)"
done

# 3b. Contrôle d'une clé étrangère : aucun diagnostic_responses orphelin.
ORPHELINS="$(psql_cible "SELECT count(*) FROM diagnostic_responses dr LEFT JOIN diagnostics d ON dr.diagnostic_id = d.id WHERE d.id IS NULL;")"
if [ "${ORPHELINS:-0}" != "0" ]; then
    echo "ÉCHEC : ${ORPHELINS} réponse(s) de diagnostic orpheline(s) — intégrité référentielle rompue" >&2
    exit 1
fi
echo "  intégrité référentielle (diagnostic_responses → diagnostics) : OK"

echo "Restauration et contrôles SQL réussis."
