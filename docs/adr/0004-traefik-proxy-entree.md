# ADR 0004 — Traefik comme proxy d'entrée

- **Statut :** accepté
- **Date :** 2026-09-02
- **Lot :** L12
- **Renvois dossier :** annexe C.4, Tableau 10

## Contexte

La composition déployée doit terminer TLS, forcer HTTPS, poser les en-têtes de
sécurité au bord et obtenir/renouveler automatiquement les certificats
(Let's Encrypt). L'annexe C.4 retient un **proxy d'entrée léger, intégré à
Docker**, plutôt qu'un nginx en terminaison TLS configuré à la main ou une
solution lourde. Le service doit être le **seul** à publier des ports sur l'hôte
(80/443), la base et l'application restant sur le réseau interne.

## Décision

**Traefik v3** en proxy d'entrée : découverte des services par étiquettes Docker,
résolveur ACME Let's Encrypt (défi HTTP), redirection permanente 80 → 443,
intergiciel d'en-têtes de sécurité **en complément** du middleware applicatif
(L04). Seul Traefik publie 80/443 ; `acme.json` est stocké sur volume nommé.

## Conséquences

- Certificats émis et renouvelés automatiquement ; le premier passage sur le
  port 80 déclenche le défi ACME (d'où l'importance du DNS et du pare-feu, L15).
- Configuration par **étiquettes** dans la composition : lisible, versionnée,
  fusionnable par surcouche (recette).
- Défense en profondeur : en-têtes posés **au bord** (Traefik) **et** dans
  l'application (L04) — l'un ne remplace pas l'autre.
- Tableau de bord Traefik désactivé (`--api.dashboard=false`) : surface réduite.

## Alternatives écartées

- **nginx en terminaison TLS + acme.sh/certbot manuels :** davantage de
  configuration et d'entretien du cycle de vie des certificats ; écarté par C.4
  au profit d'une intégration Docker native.
- **Caddy :** proche de Traefik, mais Traefik est la solution nommée par C.4 et
  s'intègre par étiquettes à la composition existante.
- **Terminaison TLS chez un fournisseur externe :** contredit l'objectif d'un
  déploiement autoporté sur instance unique.
