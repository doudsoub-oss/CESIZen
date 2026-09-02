#!/bin/sh
# Sonde locale de supervision (L20, annexe C.5).
#
# Contrôle ce que la sonde HTTP externe (Uptime Kuma) ne voit pas depuis
# l'extérieur : la FRAÎCHEUR DES SAUVEGARDES (L18) et l'ESPACE DISQUE. Signale le
# résultat à Uptime Kuma par un moniteur « push » (heartbeat). Si le contrôle
# échoue — ou si la sonde ne s'exécute plus du tout — Uptime Kuma notifie.
#
# KUMA_PUSH_URL (l'URL du moniteur push, avec son jeton) vient de l'environnement,
# jamais d'un fichier versionné. Sans elle, la sonde journalise et sort proprement.
set -eu

BACKUP_DIR="${BACKUP_DIR:-/backups}"
BACKUP_MAX_AGE_HOURS="${BACKUP_MAX_AGE_HOURS:-26}" # une quotidienne doit avoir < 26 h
DISK_MAX_PERCENT="${DISK_MAX_PERCENT:-85}"
KUMA_PUSH_URL="${KUMA_PUSH_URL:-}"

STATUT="up"
MESSAGES=""

ajouter() { MESSAGES="${MESSAGES:+$MESSAGES ; }$1"; }

# 1. Fraîcheur des sauvegardes.
if find "$BACKUP_DIR" -maxdepth 1 -type f -name 'cesizen-*.dump.gpg' \
        -mmin "-$((BACKUP_MAX_AGE_HOURS * 60))" 2>/dev/null | grep -q .; then
    ajouter "sauvegarde fraiche (<${BACKUP_MAX_AGE_HOURS}h)"
else
    STATUT="down"
    ajouter "AUCUNE sauvegarde de moins de ${BACKUP_MAX_AGE_HOURS}h"
fi

# 1bis. Marqueur d'échecs consécutifs déposé par sauvegarder.sh (L18).
if [ -f "${BACKUP_DIR}/.alerte_sauvegarde" ]; then
    STATUT="down"
    ajouter "alerte sauvegardes (echecs consecutifs)"
fi

# 2. Espace disque du volume de sauvegarde.
PCT="$(df -P "$BACKUP_DIR" 2>/dev/null | awk 'NR==2 {gsub("%","",$5); print $5}')"
if [ -n "${PCT:-}" ] && [ "$PCT" -ge "$DISK_MAX_PERCENT" ]; then
    STATUT="down"
    ajouter "disque ${PCT}% >= seuil ${DISK_MAX_PERCENT}%"
else
    ajouter "disque ${PCT:-?}%"
fi

echo "$(date +%Y-%m-%dT%H:%M:%S%z) sonde-locale: ${STATUT} — ${MESSAGES}"

# 3. Signalement à Uptime Kuma (moniteur push).
if [ -z "$KUMA_PUSH_URL" ]; then
    echo "KUMA_PUSH_URL non définie — signalement ignoré (sonde locale seule)."
    [ "$STATUT" = "up" ] && exit 0 || exit 1
fi

# encodage minimal des espaces pour le paramètre msg
MSG_ENC="$(printf '%s' "$MESSAGES" | sed 's/ /%20/g')"
curl -fsS --max-time 10 "${KUMA_PUSH_URL}?status=${STATUT}&msg=${MSG_ENC}" >/dev/null 2>&1 || \
    echo "AVERTISSEMENT : push Uptime Kuma injoignable"

[ "$STATUT" = "up" ] && exit 0 || exit 1
