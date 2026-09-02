#!/usr/bin/env bash
# Configuration des étiquettes GitHub du suivi des demandes (L21, dossier 4.1).
#
# Crée (ou met à jour, --force) les étiquettes de type, priorité, module et
# sévérité de sécurité, avec des couleurs COHÉRENTES PAR FAMILLE :
#   type::      bleu        priorite::  rouge (dégradé par gravité)
#   module::    vert        securite::  violet (dégradé par gravité)
#
# Prérequis : gh CLI authentifié (gh auth login) sur le dépôt courant.
# Idempotent : relançable sans effet de bord.
set -euo pipefail

if ! command -v gh >/dev/null 2>&1; then
    echo "gh CLI introuvable. Installer et exécuter 'gh auth login'." >&2
    exit 1
fi

# label <nom> <couleur-hex> <description>
label() {
    gh label create "$1" --color "$2" --description "$3" --force
}

echo "== Étiquettes de type (bleu) =="
label "type:incident"  "1d76db" "Incident d'exploitation en production"
label "type:anomalie"  "1d76db" "Dysfonctionnement de l'application"
label "type:evolution" "1d76db" "Évolution ou nouvelle fonctionnalité"
label "type:dette"     "1d76db" "Dette technique (dont mises à jour de dépendances)"

echo "== Étiquettes de priorité (rouge, dégradé) =="
label "priorite:critique" "b60205" "À traiter immédiatement"
label "priorite:forte"    "d93f0b" "À traiter en priorité"
label "priorite:majeure"  "e99695" "Importante, planifiée"
label "priorite:mineure"  "f9d0c4" "Faible impact"

echo "== Étiquettes de module (vert) =="
label "module:comptes"        "0e8a16" "Module Gestion des comptes"
label "module:informations"   "0e8a16" "Module Informations"
label "module:diagnostics"    "0e8a16" "Module Diagnostics"
label "module:infrastructure" "0e8a16" "Infrastructure et exploitation"

echo "== Étiquettes de sévérité de sécurité (violet, dégradé) =="
label "securite:S1" "5319e7" "Sécurité — critique"
label "securite:S2" "8957e5" "Sécurité — élevée"
label "securite:S3" "b392f0" "Sécurité — modérée"

echo "Étiquettes configurées."
