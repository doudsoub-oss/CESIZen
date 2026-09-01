# Dette technique — analyse statique (paliers PHPStan)

Un ticket par palier restant de la trajectoire décrite dans
[`docs/qualite/analyse-statique.md`](../qualite/analyse-statique.md).
Palier courant au L14 : **niveau 5** (27 erreurs figées).

> Règle commune : un palier n'est franchi que lorsque le baseline du niveau
> courant est **vide**. À promouvoir en issues GitHub au L21.

---

## Ticket — Palier niveau 6

- **Type :** dette technique (qualité) · **Priorité :** P2 · **Statut :** ouvert
- **Objectif :** vider le baseline au niveau 5, passer `level: 6`, régénérer un
  baseline minimal.
- **Critère de clôture :** `phpstan analyse` vert au niveau 6, baseline du
  niveau 5 réduit à 0 avant la montée.

## Ticket — Palier niveau 7

- **Type :** dette technique (qualité) · **Priorité :** P2 · **Statut :** ouvert
- **Objectif :** baseline vide au niveau 6, passer `level: 7`.
- **Critère de clôture :** `phpstan analyse` vert au niveau 7.

## Ticket — Palier niveau 8

- **Type :** dette technique (qualité) · **Priorité :** P3 · **Statut :** ouvert
- **Objectif :** baseline vide au niveau 7, passer `level: 8`.
- **Critère de clôture :** `phpstan analyse` vert au niveau 8.

## Ticket — Palier niveau 9

- **Type :** dette technique (qualité) · **Priorité :** P3 · **Statut :** ouvert
- **Objectif :** baseline vide au niveau 8, passer `level: 9`.
- **Critère de clôture :** `phpstan analyse` vert au niveau 9.

## Ticket — Palier niveau 10 (max)

- **Type :** dette technique (qualité) · **Priorité :** P3 · **Statut :** ouvert
- **Objectif :** baseline vide au niveau 9, passer `level: 10` (max), baseline
  ramené à zéro — cible finale de la trajectoire.
- **Critère de clôture :** `phpstan analyse` vert au niveau max, **baseline vide**.
