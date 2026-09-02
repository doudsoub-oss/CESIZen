# Documentation technique CESIZen

> *La documentation technique est traitée à l'égal du code* (dossier 3.3). Cet
> index recense la documentation ajoutée par le plan de mise en conformité
> (sécurité, déploiement, exploitation, maintenance).

## Décisions d'architecture (`adr/`)

Le *pourquoi* de chaque choix structurant. Voir [`adr/0000-modele.md`](adr/0000-modele.md).

- [0001 — Monolithe Inertia plutôt qu'API séparée](adr/0001-monolithe-inertia.md)
- [0002 — Chiffrement des résultats de diagnostic](adr/0002-chiffrement-des-resultats-de-diagnostic.md)
- [0003 — Sessions, cache et files sur PostgreSQL](adr/0003-persistance-postgresql.md)
- [0004 — Traefik comme proxy d'entrée](adr/0004-traefik-proxy-entree.md)
- [0005 — Un seul environnement déployé, la recette](adr/0005-un-seul-environnement-deploye.md)
- [0006 — Chaîne d'intégration intégrée à la forge](adr/0006-integration-continue-forge.md)

## Exploitation (`exploitation/`)

- [procedure-de-deploiement.md](exploitation/procedure-de-deploiement.md) — déploiement en recette, 9 étapes (L24).
- [runbook-incident.md](exploitation/runbook-incident.md) — gestion d'incident, confinement, CNIL (L24).
- [proces-verbal-de-recette.md](exploitation/proces-verbal-de-recette.md) — modèle de PV de recette (L24).
- [plan-de-communication.md](exploitation/plan-de-communication.md) — modèles de messages d'incident (L24).
- [provisionnement.md](exploitation/provisionnement.md) — préparation de l'instance (L15).
- [composition-deployee.md](exploitation/composition-deployee.md) — composition Docker de déploiement (L12).
- [regle-expansion-contraction.md](exploitation/regle-expansion-contraction.md) — migrations et retour arrière (L16).
- [sauvegardes.md](exploitation/sauvegardes.md) — sauvegardes chiffrées 3-2-1 (L18).
- [registre-restaurations.md](exploitation/registre-restaurations.md) — restaurations éprouvées (L19).
- [supervision.md](exploitation/supervision.md) — sonde de disponibilité (L20).
- [rotation-des-secrets.md](exploitation/rotation-des-secrets.md) — rotation de chaque secret (L01).
- [durees-de-conservation.md](exploitation/durees-de-conservation.md) — durées et purges RGPD (L09).

## Sécurité (`securite/`)

- [limitation-de-debit.md](securite/limitation-de-debit.md) — limitation de débit (L03).
- [en-tetes-securite.md](securite/en-tetes-securite.md) — en-têtes et CSP (L04).
- [cloisonnement-environnements.md](securite/cloisonnement-environnements.md) — cloisonnement (L06).

## Qualité (`qualite/`)

- [analyse-statique.md](qualite/analyse-statique.md) — trajectoire PHPStan par paliers (L14).

## Veille (`veille/`)

- [sources.md](veille/sources.md) — sources et rythmes de veille (L22).
- [journal.md](veille/journal.md) — journal de veille daté (L22).

## Backlog (`backlog/`)

- [audit-rgaa-complet.md](backlog/audit-rgaa-complet.md) — audit d'accessibilité à mener (L10).
- [dette-analyse-statique.md](backlog/dette-analyse-statique.md) — paliers PHPStan restants (L14).

## À la racine du dépôt

- [`CHANGELOG.md`](../CHANGELOG.md) — journal des modifications (Keep a Changelog).
- [`.github/ISSUE_TEMPLATE/`](../.github/ISSUE_TEMPLATE/) — formulaires de demandes (L21).
- [`.github/dependabot.yml`](../.github/dependabot.yml) — robot de mise à jour (L22).
