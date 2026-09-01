# Analyse statique — trajectoire par paliers (L14)

L'analyse statique serveur (PHPStan + Larastan) est **introduite à titre
informatif puis rendue bloquante par paliers** (Tableau 19, étape 4). Ce document
rend la trajectoire visible et vérifiable.

## Palier actuel

| | |
|---|---|
| Outil | PHPStan 2 + Larastan 3 |
| Configuration | `phpstan.neon` |
| Périmètre analysé | `app/` |
| **Palier courant** | **niveau 5** |
| Baseline | `phpstan-baseline.neon` — **27 erreurs figées / 23 entrées** |
| Statut CI (job 4) | **bloquant** au niveau 5 + garde de non-croissance du baseline |

## Palier cible

**Niveau 10 (max), baseline vide.** L'objectif final est une analyse au niveau
maximal sans aucune erreur figée.

## Règle du baseline

> Le baseline ne doit **que décroître**. Le job 4 de la CI échoue si le nombre
> d'entrées augmente d'une révision à l'autre
> (`.github/scripts/phpstan-baseline-guard.sh`). On corrige les nouvelles
> erreurs, on ne les fige pas.

## Critère de passage au palier suivant

On monte d'un niveau **uniquement lorsque le baseline du niveau courant est vide**
(0 entrée). Concrètement :

1. Réduire le baseline à zéro au niveau courant (corrections successives).
2. Incrémenter `level` dans `phpstan.neon`.
3. Régénérer un baseline au nouveau niveau
   (`vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon`).
4. Ouvrir/mettre à jour le ticket de dette du palier suivant.

## Trajectoire

| Palier | Niveau | État |
|---|---|---|
| 1 | 5 | **courant** (27 erreurs figées) |
| 2 | 6 | à venir — ticket ouvert |
| 3 | 7 | à venir — ticket ouvert |
| 4 | 8 | à venir — ticket ouvert |
| 5 | 9 | à venir — ticket ouvert |
| 6 | 10 (max) | cible — ticket ouvert |

Tickets de dette : `docs/backlog/dette-analyse-statique.md`.

## Revue

- **Établi le :** 2026-09-01 (L14)
- **Prochaine revue :** 2026-12-01 (trimestrielle), ou à chaque baseline ramené à zéro.
