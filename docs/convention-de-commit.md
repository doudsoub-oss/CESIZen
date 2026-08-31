# Convention de commit

Format retenu, dérivé des *Conventional Commits*, tel qu'annoncé au dossier (§3.3
et Tableau 18). Il rend l'historique lisible, alimente la génération des **notes
de version** (L23) et permet la **déduction du numéro de version sémantique**.

## Format

```
type(scope): sujet (#numéro-de-ticket)
```

- **type** — nature du changement (liste ci-dessous). Obligatoire.
- **scope** — module ou zone touchée, entre parenthèses. Recommandé.
  Ex. : `comptes`, `informations`, `diagnostics`, `securite`, `infra`, `git`, `ci`.
- **sujet** — impératif présent, minuscule, sans point final. Concis.
- **(#N)** — numéro du ticket GitHub associé, quand il existe. Pour un travail de
  mise en conformité sans ticket dédié, le code de lot figure dans le sujet
  (ex. `L03`).

Un corps de message facultatif peut suivre après une ligne vide (le « pourquoi »,
les détails). Les changements de rupture sont signalés par `!` après le
type/scope (`feat(api)!: …`) et/ou une ligne `BREAKING CHANGE:` dans le corps.

## Types autorisés

| Type | Usage | Effet sur la version (semver) |
| :-- | :-- | :-- |
| `feat` | Nouvelle fonctionnalité | `minor` |
| `fix` | Correction d'anomalie | `patch` |
| `docs` | Documentation seule | aucun |
| `test` | Ajout ou correction de tests | aucun |
| `refactor` | Réécriture sans changement de comportement | aucun |
| `perf` | Amélioration de performance | `patch` |
| `chore` | Maintenance, outillage, dépendances | aucun |
| `ci` | Chaîne d'intégration / déploiement | aucun |
| `build` | Système de construction, image, assets | aucun |
| `style` | Mise en forme (sans logique) | aucun |

- Une **rupture** (`!` ou `BREAKING CHANGE:`) impose une incrémentation `major`.
- Un correctif de **sécurité** est marqué par le scope `securite`
  (ex. `fix(securite): …`) : il est **remonté en tête** des notes de version
  (§3.3, cf. `.github/release.yml` du L23).

## Exemples

```
feat(diagnostics): chiffrer les résultats au repos en AES-256 (#31)
fix(securite): plafonner les tentatives de connexion par compte (#28)
docs(git): définir le modèle de branches et la convention de commit
chore(deps): monter Larastan en niveau 6 (#54)
ci: ajouter le job de détection de secrets à la chaîne (#40)
refactor(comptes): extraire la résolution de rôle dans l'enum Role
```

## Lien avec le changelog et la version

- Les notes de version (`CHANGELOG.md`, format *Keep a Changelog*, L23) sont
  regroupées par catégorie à partir des **types** de commits, les correctifs de
  **sécurité** en tête.
- Le **numéro de version sémantique** posé à chaque mise en production (étiquette
  `vX.Y.Z` sur `main`) se déduit des types depuis la précédente étiquette :
  présence d'une rupture → `major`, d'un `feat` → `minor`, sinon `patch`.

## Note de transition

Les commits antérieurs au lot **L02** utilisent un préfixe entre crochets
(`[FEAT]`, `[FIX]`, `[SECU]`, `[DOC]`…). Cette convention `type(scope): sujet`
s'applique **à compter du L02**. L'historique antérieur n'est pas réécrit.
