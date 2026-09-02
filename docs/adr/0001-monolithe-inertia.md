# ADR 0001 — Monolithe Inertia plutôt qu'une API séparée

- **Statut :** accepté
- **Date :** 2026-03-26
- **Lot :** — (architecture initiale, Bloc 2)
- **Renvois dossier :** 1.3

## Contexte

L'application devait offrir une interface moderne (Vue 3, TypeScript, Tailwind)
tout en restant proportionnée à l'équipe et au périmètre (§1.3). Deux familles
d'architecture s'opposaient : un back-end exposant une **API REST séparée**
consommée par une application front autonome, ou un **monolithe** rendant les
pages côté serveur avec une couche client. Le dossier (1.3) écarte l'API séparée
comme **disproportionnée** au regard des besoins : elle imposerait une double
gestion de l'authentification, de la validation et du versionnement d'API, pour
une seule application cliente.

## Décision

**Monolithe Laravel + Inertia.js (Vue 3).** Inertia relie le routage et les
contrôleurs Laravel à des composants Vue sans construire d'API REST : les
contrôleurs renvoient des *props* typées, le client rend des pages sans point de
terminaison JSON public à maintenir.

## Conséquences

- Une seule base de code, une seule couche d'authentification (Fortify), une
  seule validation (Form Requests) : la surface à sécuriser est réduite.
- Pas d'API publique à versionner ni à documenter séparément — cohérent avec le
  refus d'API REST séparée réaffirmé tout au long du projet.
- Le typage bout-en-bout est obtenu par Wayfinder (routes typées) plutôt que par
  un contrat OpenAPI.
- Contrepartie assumée : un client tiers (mobile natif, par ex.) nécessiterait
  d'ajouter une API dédiée — hors périmètre actuel.

## Alternatives écartées

- **API REST séparée + SPA autonome :** écartée par 1.3 comme disproportionnée
  (double authentification/validation, versionnement d'API pour un seul client).
- **Blade sans couche client riche :** ne répond pas à l'exigence d'interface
  moderne et interactive.
