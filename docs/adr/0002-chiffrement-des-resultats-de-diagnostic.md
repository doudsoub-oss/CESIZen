# ADR 0002 — Chiffrement applicatif au repos des résultats de diagnostic

- **Statut :** accepté
- **Date :** 2026-08-31
- **Lot :** L05
- **Renvois dossier :** 1.2, 2.2, 2.6, Tableau 10, conclusion

## Contexte

Les résultats de diagnostic (score de stress, réponses) constituent une **donnée
de santé au sens de l'article 9 du RGPD** (qualification posée en 1.2 et 2.2 du
dossier). Le scénario de risque **R1** — accès direct à la base de données (dump,
sauvegarde volée, accès à la base montée) — a une **gravité 4**. Sans chiffrement
applicatif, un tel accès expose en clair l'ensemble des scores et réponses
rattachés à des comptes nominatifs.

L'étude préalable (`docs/etude-chiffrement-diagnostics.md`, L05a) a établi deux
faits déterminants :

1. Le schéma est **normalisé** : le score vit dans `diagnostics.score_total` et
   `diagnostic_responses.score` ; la réponse choisie est une FK
   `diagnostic_responses.answer_option_id`.
2. **Chiffrer `score_total` seul ne protège pas le score** : la FK
   `answer_option_id` jointe à `answer_options.score` (table de référence en
   clair) le **reconstruit** par un simple `SUM ... GROUP BY`.

Par ailleurs, aucune exploitation statistique du code n'interroge le score
individuel (confirmé en L05a) : le chiffrement n'entre en collision avec aucune
fonctionnalité existante.

## Décision

**Chiffrement applicatif AES-256 au repos** (`AES-256-CBC`, déjà configuré), la
**clé applicative (`APP_KEY`) restant hors de la base**.

Colonnes chiffrées (casts `encrypted`, type `text`) :

- `diagnostics.score_total`
- `diagnostic_responses.score`
- `diagnostic_responses.answer_option_id` — **et** la clé étrangère est retirée,
  pour rendre le score **non reconstructible** par jointure SQL.

`diagnostics.result_interpretation_id` **reste en clair** (voir Conséquences).

Deux commandes accompagnent la mesure : `diagnostics:chiffrer` (chiffrement
idempotent d'un existant en clair) et `diagnostics:rechiffrer --ancienne-cle`
(rotation d'`APP_KEY`).

## Conséquences

**Assumées :**

- Les colonnes chiffrées ne sont **plus filtrables ni agrégeables en SQL**.
  Acceptable : aucune requête applicative n'en dépend (L05a).
- La **lisibilité des données dépend d'`APP_KEY`**. Une rotation impose de
  rechiffrer (procédure et commande : `docs/exploitation/rotation-des-secrets.md`).
- La **clé étrangère `answer_option_id` est retirée** : la cascade
  `answer_options → diagnostic_responses` disparaît. La suppression d'une option
  de réponse encore référencée par un historique laisserait des réponses
  orphelines (cas de bord d'administration). L'affichage de l'historique n'est pas
  affecté : Eloquent collecte les clés déchiffrées en PHP puis charge les libellés
  par `whereIn` sur la clé primaire.
- Le code mort `Diagnostic::calculateScore()` / `complete()` (unique `SUM('score')`
  SQL) est **supprimé** : sous chiffrement il serait devenu silencieusement faux.

**Interprétation laissée en clair — arbitrage explicite.**
`result_interpretation_id` pointe vers une **définition de tranche partagée**, pas
une donnée par personne. Sa valeur (faible/modéré/élevé) est de toute façon
déductible du score ; sous cette décision le score n'est plus reconstructible, la
bande seule a donc une valeur d'inférence limitée. La chiffrer casserait
l'affichage de l'historique pour un gain marginal. C'est un arbitrage assumé, pas
un oubli.

## Alternatives écartées

- **Chiffrement au niveau du disque** : ne protège pas de R1, qui suppose un accès
  à la base montée (le disque est déchiffré à ce moment).
- **`pgcrypto` (chiffrement côté base)** : déporterait la clé vers le serveur de
  base, contredisant « clé hors de la base ».
- **Pas de chiffrement** : incompatible avec l'article 9 revendiqué par le dossier.
- **Périmètre littéral (score seul, option C de L05a)** : laisserait le score
  reconstructible via `answer_option_id` — protection illusoire.
- **Dénormalisation en instantané chiffré (option B de L05a)** : plus proche de la
  formulation du plan, mais impose une migration de dénormalisation et une refonte
  du service ; coût disproportionné au regard de l'option retenue.

## Note sur la règle expansion / contraction (dossier 3.4)

Cette migration **change un type en place** (`integer` → `text`) et retire une
clé étrangère, ce que la règle expansion/contraction proscrit normalement. C'est
légitime **parce qu'aucun environnement n'est déployé à la date de son
application** : il n'existe pas de version antérieure vers laquelle revenir. La
règle expansion/contraction s'applique **à compter de la première version
étiquetée** (voir `docs/exploitation/regle-expansion-contraction.md`, L16).
