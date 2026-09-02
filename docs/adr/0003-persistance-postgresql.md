# ADR 0003 — Sessions, cache et files d'attente sur PostgreSQL plutôt qu'en mémoire

- **Statut :** accepté
- **Date :** 2026-03-26
- **Lot :** — (architecture initiale, confirmée §3.1)
- **Renvois dossier :** 3.1

## Contexte

Laravel peut porter les sessions, le cache et les files d'attente sur un magasin
**en mémoire** (Redis, Memcached) ou sur la **base de données**. Le dossier (3.1)
dimensionne l'hébergement au strict nécessaire (instance unique, 2 cœurs / 12 Go)
et ne prévoit **aucun composant d'infrastructure en mémoire**. Ajouter Redis
introduirait un service à exploiter, sauvegarder et sécuriser, sans besoin établi
à ce stade.

## Décision

**Tout persister sur PostgreSQL** : `SESSION_DRIVER=database` (chiffrées),
`CACHE_STORE=database`, `QUEUE_CONNECTION=database`. Aucun magasin en mémoire.

## Conséquences

- Un seul service de données à exploiter et à sauvegarder (L18) : la base
  PostgreSQL. La continuité (sauvegarde/restauration) couvre du même coup
  sessions, cache et files.
- Les files d'attente sur base conviennent au volume attendu ; le service
  `queue` consomme la même base par le réseau interne.
- Contrepartie assumée : débit inférieur à un Redis pour des charges très
  élevées. Non pertinent au dimensionnement du dossier ; réévaluable si le
  volume l'imposait (ce serait une nouvelle ADR).

## Alternatives écartées

- **Redis / Memcached :** écartés — composant d'infrastructure supplémentaire non
  prévu par 3.1, à exploiter/sauvegarder/sécuriser sans besoin établi.
- **Sessions en fichier :** mal adaptées à une exécution conteneurisée (plusieurs
  répliques applicatives potentielles, volume partagé à gérer).
