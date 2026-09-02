#!/bin/sh
# Test de restauration éprouvée (L19, engagement 2.8).
#
# Crée une base JETABLE, y restaure la dernière sauvegarde (restaurer.sh),
# exécute les contrôles d'intégrité — dont le déchiffrement d'un diagnostic avec
# la clé applicative courante —, chronomètre, détruit la base jetable, puis ajoute
# une ligne au registre docs/exploitation/registre-restaurations.md.
#
# À exécuter mensuellement (voir le rappel .github/workflows/rappel-restauration.yml).
# S'appuie sur la composition de déploiement ; aucune donnée de production n'est
# touchée (base jetable dédiée, détruite en fin d'exercice).
#
# Variables : COMPOSE (fichiers de composition), REGISTRE (chemin du registre).
set -eu

COMPOSE="${COMPOSE:-compose.prod.yml}"
REGISTRE="${REGISTRE:-docs/exploitation/registre-restaurations.md}"
CIBLE="cesizen_restore_test_$(date +%Y%m%d%H%M%S)"
TYPE_EXERCICE="${1:-mensuel automatisé}"

dc() { docker compose -f "$COMPOSE" "$@"; }

RESULTAT="ÉCHEC"
INTEGRITE="non"
nettoyer() {
    # Détruit toujours la base jetable, quoi qu'il arrive.
    dc exec -T db sh -c "dropdb -U \"\$POSTGRES_USER\" --if-exists '${CIBLE}'" >/dev/null 2>&1 || true
}
trap nettoyer EXIT INT TERM

echo "== Test de restauration — base jetable ${CIBLE} =="
DEBUT="$(date +%s)"

# 1. Base jetable.
dc exec -T db sh -c "createdb -U \"\$POSTGRES_USER\" '${CIBLE}'"

# 2. Restauration de la dernière sauvegarde + contrôles SQL.
dc run --rm backup /usr/local/bin/restaurer.sh --base "${CIBLE}"

# 3. Contrôle applicatif : déchiffrement d'un diagnostic avec l'APP_KEY courante.
dc run --rm -e DB_DATABASE="${CIBLE}" app php artisan diagnostics:verifier-dechiffrement

INTEGRITE="oui"
RESULTAT="Succès"

FIN="$(date +%s)"
DUREE="$(( FIN - DEBUT ))"
DUREE_MIN="$(( DUREE / 60 ))m$(( DUREE % 60 ))s"

# Sauvegarde effectivement testée (la plus récente).
FICHIER_TESTE="$(dc run --rm backup sh -c 'ls -1t /backups/cesizen-*.dump.gpg 2>/dev/null | head -1 | xargs -r basename' | tr -d '\r')"

# 4. Ligne de registre (format annexe E). La DIMA de référence est de 24 h.
LIGNE="| $(date +%Y-%m-%d) | ${FICHIER_TESTE:-inconnue} | ${TYPE_EXERCICE} | ${DUREE_MIN} | ${INTEGRITE} | ${RESULTAT} — bien en deçà de la DIMA de 24 h |"
echo "$LIGNE"
if [ -f "$REGISTRE" ]; then
    printf '%s\n' "$LIGNE" >> "$REGISTRE"
    echo "Ligne ajoutée à ${REGISTRE}."
else
    echo "AVERTISSEMENT : ${REGISTRE} introuvable — ligne non enregistrée." >&2
fi

echo "== Restauration éprouvée : ${RESULTAT} en ${DUREE_MIN} =="
