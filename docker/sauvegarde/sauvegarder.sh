#!/bin/sh
# Sauvegarde chiffrée de la base CESIZen (L18, engagement 2.8 et Tableau 10).
#
# Chaîne : pg_dump (format custom, compressé) → chiffrement AES-256 par gpg
# AVANT toute écriture destinée à sortir du serveur. La phrase secrète vient de
# l'environnement (BACKUP_PASSPHRASE), jamais d'un fichier versionné, et n'est
# JAMAIS affichée. Empreinte SHA-256 consignée, rétention 30 jours, journal
# d'exécution. Sort en code non nul en cas d'échec.
#
# Externalisation (production seulement, BACKUP_REMOTE_ENABLED=true) : le fichier
# transféré est DÉJÀ chiffré ; la clé ne quitte jamais le serveur.
#
# Usage :
#   sauvegarder.sh            sauvegarde + vérification + purge (+ externalisation)
#   sauvegarder.sh --verifier idem (la vérification est de toute façon toujours
#                             faite) ; forme explicite appelée avant migration en
#                             production (§3.5, condition n°4).
set -eu

# ── Configuration (valeurs par défaut sûres ; secrets via l'environnement) ────
DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-5432}"
DB_DATABASE="${DB_DATABASE:?DB_DATABASE requis}"
DB_USERNAME="${DB_USERNAME:?DB_USERNAME requis}"
DB_PASSWORD="${DB_PASSWORD:?DB_PASSWORD requis}"
BACKUP_PASSPHRASE="${BACKUP_PASSPHRASE:?BACKUP_PASSPHRASE requis (chiffrement AES-256)}"
BACKUP_DIR="${BACKUP_DIR:-/backups}"
BACKUP_RETENTION_DAYS="${BACKUP_RETENTION_DAYS:-30}"
BACKUP_REMOTE_ENABLED="${BACKUP_REMOTE_ENABLED:-false}"
BACKUP_REMOTE="${BACKUP_REMOTE:-}"
BACKUP_REMOTE_PATH="${BACKUP_REMOTE_PATH:-}"
BACKUP_ALERT_COMMAND="${BACKUP_ALERT_COMMAND:-}"

MANIFEST="${BACKUP_DIR}/manifest.sha256"
JOURNAL="${BACKUP_DIR}/journal.log"
ETAT_ECHECS="${BACKUP_DIR}/.echecs_consecutifs"

HORODATAGE="$(date +%Y%m%d-%H%M%S)"
FICHIER="${BACKUP_DIR}/cesizen-${HORODATAGE}.dump.gpg"

# Fichier de phrase secrète : créé à la volée en zone restreinte, jamais versionné,
# supprimé quoi qu'il arrive (trap). Ne transite jamais par un journal.
PASSFILE=""
nettoyer() {
    [ -n "$PASSFILE" ] && rm -f "$PASSFILE" 2>/dev/null || true
}
trap nettoyer EXIT INT TERM

journaliser() {
    # $1 = message ; horodaté, ajouté au journal et à la sortie standard.
    printf '%s  %s\n' "$(date +%Y-%m-%dT%H:%M:%S%z)" "$1" | tee -a "$JOURNAL"
}

# Comptabilise un échec ; alerte à partir de deux échecs consécutifs (→ L20).
enregistrer_echec() {
    n=0
    [ -f "$ETAT_ECHECS" ] && n="$(cat "$ETAT_ECHECS" 2>/dev/null || echo 0)"
    n=$((n + 1))
    echo "$n" > "$ETAT_ECHECS"
    journaliser "ÉCHEC (échecs consécutifs : ${n})"
    if [ "$n" -ge 2 ]; then
        journaliser "ALERTE : ${n} nuits consécutives sans sauvegarde réussie"
        if [ -n "$BACKUP_ALERT_COMMAND" ]; then
            # La commande d'alerte est raccordée à la supervision (L20).
            sh -c "$BACKUP_ALERT_COMMAND" || true
        fi
        # Marqueur lisible par la sonde de disponibilité (L20).
        echo "$(date +%Y-%m-%dT%H:%M:%S%z) ${n}" > "${BACKUP_DIR}/.alerte_sauvegarde"
    fi
}

echec() {
    journaliser "$1"
    enregistrer_echec
    exit 1
}

mkdir -p "$BACKUP_DIR"
DEBUT="$(date +%s)"
journaliser "Début de sauvegarde → $(basename "$FICHIER")"

PASSFILE="$(mktemp)"
chmod 600 "$PASSFILE"
printf '%s' "$BACKUP_PASSPHRASE" > "$PASSFILE"

# ── 1+2+3. Dump custom compressé → chiffrement AES-256, en flux (pas de clair sur disque) ──
# PGPASSWORD passe par l'environnement du seul processus pg_dump, jamais journalisé.
if ! PGPASSWORD="$DB_PASSWORD" pg_dump \
        --host="$DB_HOST" --port="$DB_PORT" \
        --username="$DB_USERNAME" --dbname="$DB_DATABASE" \
        --format=custom --compress=6 --no-owner --no-privileges \
    | gpg --symmetric --cipher-algo AES256 --batch --yes \
          --passphrase-file "$PASSFILE" --output "$FICHIER"
then
    rm -f "$FICHIER" 2>/dev/null || true
    echec "pg_dump/gpg a échoué — aucun fichier conservé"
fi

TAILLE="$(wc -c < "$FICHIER" | tr -d ' ')"
if [ "${TAILLE:-0}" -lt 1 ]; then
    rm -f "$FICHIER" 2>/dev/null || true
    echec "fichier de sauvegarde vide"
fi

# ── 4. Vérification : le fichier chiffré se déchiffre ET est un dump valide ────
# Preuve concrète du critère d'acceptation « n'est pas lisible sans la phrase ».
if ! gpg --decrypt --batch --quiet --passphrase-file "$PASSFILE" "$FICHIER" 2>/dev/null \
     | pg_restore --list > /dev/null 2>&1
then
    rm -f "$FICHIER" 2>/dev/null || true
    echec "vérification échouée (déchiffrement ou dump illisible)"
fi

# ── 5. Empreinte SHA-256 consignée au manifeste ───────────────────────────────
EMPREINTE="$(sha256sum "$FICHIER" | cut -d' ' -f1)"
printf '%s  %s\n' "$EMPREINTE" "$(basename "$FICHIER")" >> "$MANIFEST"

# ── 6. Externalisation (production uniquement) ────────────────────────────────
# Le fichier est DÉJÀ chiffré ; seule la copie chiffrée quitte le serveur.
if [ "$BACKUP_REMOTE_ENABLED" = "true" ]; then
    if [ -z "$BACKUP_REMOTE" ] || [ -z "$BACKUP_REMOTE_PATH" ]; then
        echec "externalisation activée mais BACKUP_REMOTE / BACKUP_REMOTE_PATH manquants"
    fi
    if ! rclone copy "$FICHIER" "${BACKUP_REMOTE}:${BACKUP_REMOTE_PATH}" --no-traverse; then
        echec "transfert rclone échoué"
    fi
    journaliser "Externalisée vers ${BACKUP_REMOTE}:${BACKUP_REMOTE_PATH}"
fi

# ── 7. Purge des sauvegardes de plus de N jours (local) ───────────────────────
find "$BACKUP_DIR" -maxdepth 1 -type f -name 'cesizen-*.dump.gpg' \
    -mtime "+${BACKUP_RETENTION_DAYS}" -print -delete | while read -r f; do
    journaliser "Purge (>${BACKUP_RETENTION_DAYS} j) : $(basename "$f")"
done

# ── Succès : réinitialise le compteur d'échecs et le marqueur d'alerte ────────
echo 0 > "$ETAT_ECHECS"
rm -f "${BACKUP_DIR}/.alerte_sauvegarde" 2>/dev/null || true

DUREE="$(( $(date +%s) - DEBUT ))"
journaliser "SUCCÈS — taille ${TAILLE} o, durée ${DUREE} s, sha256 ${EMPREINTE}"
