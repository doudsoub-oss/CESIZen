# Journal de veille

> Une entrée **datée** par information traitée (4.5). Chaque entrée précise la
> **source**, le **composant** concerné, son **applicabilité** (le composant
> est-il réellement utilisé, et où ?), une **décision explicite** (*traiter* /
> *surveiller* / *écarter*) et son **motif**. Une décision « écarter » sans motif
> est un défaut. Sources et rythmes : [`sources.md`](sources.md).

---

## 2026-09-02 — PHP 8.4 : calendrier de fin de support

- **Source :** php.net/supported-versions.
- **Composant :** PHP 8.4 — langage d'exécution (image `php:8.4-fpm-alpine`),
  cœur de l'application.
- **Applicabilité :** oui, central, en production comme en développement.
- **Repère :** support actif (corrections de bogues) jusqu'au **2026-12-31**,
  correctifs de sécurité jusqu'au **2028-12-31** (à revérifier à la source).
- **Décision : surveiller.** Motif : encore couvert plus de deux ans. Planifier
  l'étude de montée vers PHP 8.5+ **avant la fin du support actif** (fin 2026)
  pour ne pas passer en phase « sécurité seule » sans plan.

## 2026-09-02 — Laravel 13 : fenêtre de support

- **Source :** laravel.com/docs/releases (politique de support).
- **Composant :** `laravel/framework` 13 — cadriciel applicatif.
- **Applicabilité :** oui, cœur applicatif.
- **Repère :** cadence annuelle ; par version majeure, **corrections de bogues
  ≈ 18 mois** et **correctifs de sécurité ≈ 2 ans** après la sortie (début 2026).
  Dates exactes à confirmer à la source.
- **Décision : surveiller.** Motif : version courante et soutenue. Réévaluer à
  l'approche de la fin des corrections de bogues ; suivre les PR Dependabot
  `composer` pour rester à jour dans la branche 13.

## 2026-09-02 — PostgreSQL 16 : fin de vie

- **Source :** postgresql.org/support/versioning.
- **Composant :** PostgreSQL 16 — SGBD (production et service `postgres:16` en CI).
- **Applicabilité :** oui, donnée de production.
- **Repère :** politique de 5 ans ; PostgreSQL 16 (sorti en 09/2023) en **fin de
  vie ≈ novembre 2028**.
- **Décision : surveiller.** Motif : large marge. La montée de version majeure
  impose un `pg_dump`/`pg_restore` (ou `pg_upgrade`) : à planifier ~1 an avant
  l'échéance, en s'appuyant sur la restauration éprouvée (L19).

## 2026-09-02 — shell-quote : vulnérabilités critiques

- **Source :** `npm audit` (chaîne CI, étape 6) · GitHub Advisory Database
  (GHSA-395f-4hp3-45gv « quadratic DoS in parse() » ; GHSA-w7jw-789q-3m8p).
- **Composant :** `shell-quote` — dépendance **transitive** via `concurrently`
  (devDependency).
- **Applicabilité :** **développement uniquement.** `concurrently` n'est pas
  installé dans l'image de production (`npm ci` sans devDependencies, assets
  compilés à la construction) : le composant n'est jamais livré.
- **Décision : traiter (fait).** Motif : bien que non exploitable en production,
  la chaîne bloque au niveau critique. Résolu par un `overrides` npm forçant
  `shell-quote` ≥ 1.10.0 (version corrigée). `npm audit` : 0 critique.

## 2026-09-02 — vite : vulnérabilités de sévérité élevée

- **Source :** `npm audit` (chaîne CI, étape 6) · GitHub Advisory Database
  (path traversal du serveur de dev, GHSA-4w7w-66w2-5vf9 et voisines).
- **Composant :** `vite` ^8 — outil de construction et serveur de développement.
- **Applicabilité :** **construction et développement uniquement.** L'image de
  production ne contient que les **assets compilés**, pas vite ni son serveur ;
  le serveur de dev n'est jamais exposé publiquement.
- **Décision : surveiller / traiter au prochain relèvement.** Motif : sévérité
  élevée mais **non critique** (ne bloque pas la chaîne) et surface nulle en
  production. Sera appliqué via la PR Dependabot `npm` de montée de vite.

## 2026-09-02 — CERT-FR : multiples vulnérabilités dans PHP

- **Source :** CERT-FR (cert.ssi.gouv.fr) — bulletins récurrents « Multiples
  vulnérabilités dans PHP ».
- **Composant :** PHP (image `php:8.4-fpm-alpine`).
- **Applicabilité :** oui, en production.
- **Référence de l'avis :** ‹à renseigner avec l'avis CERT-FR en vigueur au jour
  de la consultation, format CERTFR-AAAA-AVI-NNNN›.
- **Décision : surveiller (application automatique).** Motif : les correctifs de
  sécurité PHP arrivent par les versions correctives de l'image de base ; ils
  sont tirés par la PR Dependabot `docker` et contrôlés par Trivy (étape 9). Un
  avis signalant une vulnérabilité **activement exploitée** ferait basculer la
  décision en *traiter* (montée immédiate de l'image).
