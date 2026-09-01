#!/usr/bin/env bash
# Garde « par paliers » (L14) : le nombre d'entrées du baseline PHPStan ne doit
# JAMAIS augmenter d'une révision à l'autre. C'est le mécanisme concret qui
# donne un sens à la trajectoire d'analyse statique.
set -euo pipefail

BASELINE="phpstan-baseline.neon"

count_entries() {
    # Une entrée = une occurrence de « count: » sous ignoreErrors.
    if [ -f "$1" ]; then
        grep -c 'count:' "$1" || true
    else
        echo 0
    fi
}

CURRENT="$(count_entries "$BASELINE")"

# Résolution de la référence de base.
if [ -n "${GITHUB_BASE_REF:-}" ]; then
    # Pull request : on compare à la branche cible.
    git fetch --quiet --depth=1 origin "${GITHUB_BASE_REF}" || true
    BASE_REF="origin/${GITHUB_BASE_REF}"
elif [ -n "${GITHUB_EVENT_BEFORE:-}" ] && \
     [ "${GITHUB_EVENT_BEFORE}" != "0000000000000000000000000000000000000000" ]; then
    # Push : on compare au commit précédent.
    BASE_REF="${GITHUB_EVENT_BEFORE}"
else
    echo "Pas de référence de base exploitable — garde ignorée."
    exit 0
fi

if git cat-file -e "${BASE_REF}:${BASELINE}" 2>/dev/null; then
    BASE_COUNT="$(git show "${BASE_REF}:${BASELINE}" | grep -c 'count:' || true)"
else
    echo "Aucun baseline sur la base (${BASE_REF}) — première introduction, garde ignorée."
    exit 0
fi

echo "Entrées du baseline — base : ${BASE_COUNT}, courant : ${CURRENT}"

if [ "${CURRENT}" -gt "${BASE_COUNT}" ]; then
    echo "::error::Le baseline PHPStan a augmenté (${BASE_COUNT} → ${CURRENT})."
    echo "Le baseline ne doit que décroître : corrigez les nouvelles erreurs plutôt que de les figer."
    exit 1
fi

echo "OK : le baseline ne croît pas (${BASE_COUNT} → ${CURRENT})."
