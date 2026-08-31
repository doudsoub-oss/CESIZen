# Contribuer à CESIZen

Ce document décrit le **modèle de branches**, les **règles de protection** et la
**chaîne de traçabilité** du projet. Il matérialise le Tableau 18 et les sections
2.7 et 3.3 du dossier Activité 3.

> **Principe directeur (§2.7).** Aucune modification ne rejoint une branche
> protégée sans **demande de fusion, revue et chaîne d'intégration verte**.

---

## 1. Chaîne de traçabilité

Un besoin = un ticket = une branche = une demande de fusion. C'est la chaîne qui
est regardée à la soutenance (§4.4).

```
Ticket GitHub  →  branche feature/…  →  commits « type(scope): sujet (#N) »
               →  demande de fusion (PR)  →  revue + chaîne verte  →  fusion sur dev
```

- **Le déploiement est automatisé ; la gestion de version ne l'est pas (§3.3).**
  Les fusions sont faites **manuellement par une personne identifiée**. Pas de
  fusion automatique, pas de bot de merge, pas d'auto-merge.

---

## 2. Modèle de branches (Tableau 18)

| Branche | Rôle | Durée de vie | Part de | Fusionne dans |
| :-- | :-- | :-- | :-- | :-- |
| `main` | Reflète la **production**. Chaque mise en production y est étiquetée (`vX.Y.Z`). | Permanente | — | — |
| `dev` | Branche d'**intégration**. Cible par défaut du travail courant. | Permanente | — | `main` (via release) |
| `feature/<NN>-<sujet>` | Nouvelle fonctionnalité ou évolution. | Le temps du ticket | `dev` | `dev` |
| `fix/<NN>-<sujet>` | Correction d'anomalie hors urgence. | Le temps du ticket | `dev` | `dev` |
| `hotfix/<NN>-<sujet>` | Correctif **urgent** de production. | Très courte | `main` | `main` **et** `dev` |
| `docs/<NN>-<sujet>` | Documentation seule. | Courte | `dev` | `dev` |
| `chore/<NN>-<sujet>` | Maintenance, outillage, dépendances (dette technique). | Courte | `dev` | `dev` |

- `<NN>` = numéro du ticket GitHub associé (ou code de lot pour les travaux de
  mise en conformité, ex. `feature/L03-limitation-de-debit`).
- `<sujet>` en minuscules, mots séparés par des tirets.

**Flux nominal**

1. Créer le ticket (gabarit anomalie ou évolution, cf. L21).
2. Créer la branche depuis `dev` (ou `main` pour un `hotfix`).
3. Committer selon [la convention de commit](docs/convention-de-commit.md).
4. Ouvrir une demande de fusion vers `dev` (gabarit `.github/pull_request_template.md`).
5. Attendre la **chaîne verte** (`chaine-verte`, cf. L13) et la revue.
6. Fusionner manuellement. Supprimer la branche de fonctionnalité.
7. La mise en production se fait par étiquetage d'une version sur `main` (cf. L17, L23).

---

## 3. Règles de protection

Ces règles se configurent sur GitHub (elles ne sont pas versionnables). Elles
doivent être **actives** et **capturées en image** pour la soutenance (§3.3).

| Branche | Réglage |
| :-- | :-- |
| `main` | Poussée directe interdite · PR obligatoire · **1 approbation** · toutes les vérifications requises (`chaine-verte`) · **historique linéaire** |
| `dev` | Poussée directe interdite · PR obligatoire · toutes les vérifications requises (`chaine-verte`) |

> La vérification requise unique est le job final `chaine-verte` de la chaîne
> d'intégration (L13), qui dépend de toutes les autres étapes.

---

## 4. Migrations : règle expansion / contraction

Toute demande de fusion comportant une migration renseigne son **impact** dans le
gabarit de PR (expansion / contraction). Un renommage ou une suppression de
colonne se fait en **deux versions** (expansion puis contraction), afin qu'un
retour arrière par redéploiement de l'image précédente reste possible sans
rejouer les migrations à l'envers. Détail : `docs/exploitation/regle-expansion-contraction.md` (L16).

---

## 5. Revue

- Au moins une relecture avant fusion sur `main`.
- La revue vérifie : couverture de test, respect des conventions, impact
  migration, points de sécurité et d'accessibilité signalés dans la PR.
- Un test ajouté doit **échouer avant le correctif** et passer après (§ règle de
  travail du projet).
