# Étude préalable — chiffrement des résultats de diagnostic (L05a)

> **Reconnaissance, aucune modification de code.** Prépare la mise en œuvre
> (L05b) et la décision d'architecture (L05c) du chiffrement applicatif au repos
> des résultats de diagnostic (Tableau 10 du dossier, article 9 RGPD).
>
> Date : 2026-08-31 · Chiffrement configuré : `config/app.php` →
> `'cipher' => 'AES-256-CBC'` (**déjà conforme**, aucun changement requis).

---

## 1. Modèle des résultats de diagnostic

Le résultat n'est **pas** un objet unique : il est **normalisé sur deux tables**.

### Table `diagnostics` (migration `2026_03_25_120903_create_diagnostics_table.php`)

| Colonne | Type | Rôle | Candidate au chiffrement |
| :-- | :-- | :-- | :-- |
| `id` | bigint (PK) | — | non |
| `user_id` | bigint (FK → users, cascade) | rattachement au compte | non (jointure d'accès) |
| `questionnaire_id` | bigint (FK → questionnaires, cascade) | questionnaire | non |
| `score_total` | **integer** (défaut 0) | **score total** | **OUI** |
| `result_interpretation_id` | bigint (FK → result_interpretations, nullOnDelete) | **interprétation résolue** | à décider (§4) |
| `completed_at` | timestamp nullable | horodatage | non |
| `created_at` / `updated_at` | timestamp | — | non |

### Table `diagnostic_responses` (migration `2026_03_25_120907_...`)

| Colonne | Type | Rôle | Candidate au chiffrement |
| :-- | :-- | :-- | :-- |
| `id` | bigint (PK) | — | non |
| `diagnostic_id` | bigint (FK → diagnostics, cascade) | rattachement | non |
| `question_id` | bigint (FK → questions, cascade) | question | non |
| `answer_option_id` | bigint (FK → answer_options, cascade) | **réponse choisie** | à décider (§4) |
| `score` | **integer** (défaut 0) | **score de la réponse** | **OUI** |
| `created_at` | timestamp nullable | — | non |

**Aucun index** n'est posé sur `score_total` ni sur `diagnostic_responses.score`
(seuls les index de clés étrangères existent). Le chiffrement ne casse donc
aucun index applicatif.

### Divergence avec l'énoncé du plan (à intégrer en L05b)

Le prompt L05b suppose des colonnes nommées `score`, `interpretation`,
`answers` (`encrypted:array`) sur **un seul** modèle. Le schéma réel étant
normalisé, la correspondance est :

| Énoncé du plan | Réalité du schéma |
| :-- | :-- |
| `score` → `encrypted` | `diagnostics.score_total` **et** `diagnostic_responses.score` |
| `interpretation` → `encrypted` | `diagnostics.result_interpretation_id` (**FK**, pas un texte) — voir §4 |
| `answers` → `encrypted:array` | `diagnostic_responses.answer_option_id` (**FK**, une ligne par réponse) — voir §4 |

L05b doit donc **adapter** les casts à ces colonnes réelles, pas les appliquer
littéralement.

---

## 2. Inventaire des occurrences des colonnes candidates

Recherche exhaustive dans `app/`, `resources/js/` (where, orderBy, groupBy,
having, avg, sum, count, scope, index, requêtes d'administration / de statistique,
tri et filtre côté Vue).

| # | Emplacement | Opération | Survit au chiffrement ? | Parade |
| :-- | :-- | :-- | :-- | :-- |
| 1 | `app/Models/Diagnostic.php:46` `calculateScore()` = `responses()->sum('score')` | **SUM SQL** sur `diagnostic_responses.score` | **NON** | **Code mort** (aucun appelant, voir §3). Recalculer en PHP après hydratation, ou supprimer. |
| 2 | `app/Models/Diagnostic.php:51-53` `complete()` lit `score_total` en PHP | lecture d'attribut (PHP) | OUI | Aucune — l'attribut est déchiffré par le cast. Mais `complete()` est **du code mort** (§3). |
| 3 | `app/Http/Controllers/Diagnostic/HistoryController.php:17` `with('resultInterpretation:id,title,color')` | jointure via FK `result_interpretation_id` | **NON si la FK est chiffrée** | Voir §4 : ne pas chiffrer la FK, ou stocker un instantané chiffré. |
| 4 | `HistoryController.php:36` `with('responses.answerOption:id,label,score')` | jointure via FK `answer_option_id` | **NON si la FK est chiffrée** | Voir §4. |
| 5 | `HistoryController.php:19` `latest('completed_at')` · `:16` `where('user_id', …)` | tri/filtre sur colonnes **non chiffrées** | OUI | Aucune. |
| 6 | `app/Services/DiagnosticScoringService.php:59,63` `$total += $option->score` | somme **en PHP** sur `answer_options.score` (table **non** chiffrée) | OUI | Aucune — le score est calculé en clair côté service, à partir des options, puis écrit. |
| 7 | `app/Services/DiagnosticScoringService.php:76-79` / `Questionnaire::getInterpretationForScore()` | `where('min_score' ..)('max_score' ..)` sur **`result_interpretations`** | OUI | Aucune — filtre les **définitions** de tranches, pas le score chiffré ; le score comparé est un `int` PHP. |
| 8 | `app/Http/Controllers/Diagnostic/DiagnosticController.php:74-78` | **écriture** de `score_total` et création des `responses` | OUI | Aucune — l'écriture passe par le modèle (les casts chiffrent à l'écriture). |
| 9 | `resources/js/pages/public/Diagnostic/History.vue:103`, `HistoryShow.vue:63` `{{ diagnostic.score_total }}` | **affichage** (valeur déjà déchiffrée par le serveur) | OUI | Aucune — Vue reçoit la valeur en clair via Inertia ; **aucun tri/filtre serveur** sur le score. |
| 10 | `resources/js/pages/public/Diagnostic/History.vue:85-98`, `HistoryShow.vue:40` `result_interpretation.title` | affichage de l'interprétation | OUI (si §4 préserve la lisibilité) | Dépend de la décision §4. |

**Aucune** occurrence de `groupBy` / `having` / `avg` sur les scores.
**Aucune** requête d'administration ou de statistique n'agrège les scores.
**Aucun** tri ou filtre côté serveur ne porte sur `score_total`.

---

## 3. Code mort : `calculateScore()` / `complete()`

`Diagnostic::calculateScore()` (SUM SQL) et `Diagnostic::complete()` **n'ont
aucun appelant** dans `app/`, `tests/` ni `database/` (vérifié par recherche). Le
chemin d'écriture réel (`DiagnosticController::submit`) passe par
`DiagnosticScoringService` (somme **en PHP**) puis écrit `score_total` et les
`responses` directement.

→ **Recommandation L05b :** supprimer `calculateScore()` et `complete()` (ou, si
on les conserve, remplacer le `sum('score')` SQL par une somme en mémoire après
hydratation). Sans cela, ces méthodes deviendraient silencieusement fausses sous
chiffrement — un piège pour un futur développeur.

---

## 4. Le point critique : reconstructibilité du score

> **Chiffrer `score_total` seul ne protège pas le score.**

`diagnostic_responses.answer_option_id` est une FK vers `answer_options`, dont la
colonne `score` est **en clair** (table de référence du questionnaire, non
sensible en soi). Un accès direct à la base permet donc :

```sql
SELECT dr.diagnostic_id, SUM(ao.score)
FROM diagnostic_responses dr
JOIN answer_options ao ON ao.id = dr.answer_option_id
GROUP BY dr.diagnostic_id;
```

… ce qui **reconstruit le score total** de chaque diagnostic, même si
`score_total` et `diagnostic_responses.score` sont chiffrés. C'est précisément le
scénario R1 (accès direct à la base) que le chiffrement doit contrer.

**Conséquence :** pour que « une inspection SQL directe ne révèle aucun score »
soit vrai *et non reconstructible*, il faut aussi rendre non-joignable la réponse
choisie. Trois options, à trancher en **L05c (ADR)** :

| Option | Description | Coût | Effet sur les occurrences #3/#4/#10 |
| :-- | :-- | :-- | :-- |
| **A. Chiffrer les scores + `answer_option_id`** | `score_total`, `diagnostic_responses.score` et `answer_option_id` en `text` chiffré. | La jointure #4 casse : charger `answerOption` après hydratation (le cast rend la FK en clair côté PHP), au prix de N requêtes (volumétrie faible, acceptable). | #4 : charger en PHP ; #3 idem pour l'interprétation. |
| **B. Dénormaliser en instantané chiffré** | Stocker sur chaque réponse un instantané `{label, score}` chiffré (`encrypted:array`) et sur le diagnostic le titre d'interprétation chiffré ; les FK peuvent rester pour l'affichage courant OU être retirées. | Migration de données + alignement du service ; colle à l'intention du plan (`answers => encrypted:array`). | #3/#4/#10 lues depuis l'instantané déchiffré, sans jointure. |
| **C. Périmètre littéral minimal** | Chiffrer seulement `score_total` et `diagnostic_responses.score`. | Faible. | Satisfait la lettre du critère (« aucun score en clair ») **mais laisse le score reconstructible** via #4 — écart de sécurité à assumer explicitement. |

**Recommandation :** **Option A** — chiffrer les deux scores **et**
`answer_option_id`, avec chargement en PHP des libellés de réponse. C'est le plus
proche du modèle existant, ça rend le score réellement non reconstructible, et ça
n'impose pas de migration de dénormalisation. L'interprétation
(`result_interpretation_id`, §voir ci-dessous) est traitée à part.

### Cas de l'interprétation (`result_interpretation_id`)

`result_interpretation_id` pointe vers une **définition de tranche partagée**
(`result_interpretations`), pas vers une donnée par personne. Elle révèle la
**bande** (faible/modéré/élevé), qui est une inférence de santé, mais qui est
**déductible du score** de toute façon. La chiffrer casserait la jointure #3.

**Recommandation :** conserver `result_interpretation_id` en clair (FK), car (a)
sa valeur est une tranche générique partagée par tous, (b) sous l'option A le
score n'est plus reconstructible donc la bande seule a une valeur d'inférence
limitée, (c) chiffrer une FK pour un gain marginal casse l'affichage de
l'historique. **À acter dans l'ADR L05c** comme un arbitrage explicite, pas un
oubli.

---

## 5. Vérification de l'affirmation du dossier

> Dossier : « la seule exploitation statistique prévue porte sur des volumétries
> anonymes, qui n'exigent pas d'interroger le score individuel ».

**VRAI dans le code actuel.** Recherche exhaustive :
- **aucune** agrégation (`avg`/`sum`/`groupBy`/`count`) de scores rattachés à des
  comptes dans `app/` ;
- **aucun** écran d'administration n'affiche ni ne trie des scores individuels ;
- la seule somme est **en PHP**, au moment du scoring (occurrence #6), à partir de
  la table de référence non chiffrée `answer_options`.

→ **Aucun écart à traiter avant le chiffrement** de ce point de vue. Le chiffrement
n'entre en collision avec aucune exploitation statistique existante.

---

## 6. Impact sur les tests (à préparer pour L05b)

| Test | Ligne | Impact | Adaptation L05b |
| :-- | :-- | :-- | :-- |
| `tests/Feature/Diagnostic/PublicDiagnosticTest.php` | 107-116 `assertDatabaseHas('diagnostics', ['score_total' => 5])` et `assertDatabaseHas('diagnostic_responses', ['score' => 5, 'answer_option_id' => …])` | **Cassera** : la base stocke du chiffré, pas `5` | Remplacer par : la valeur brute en base ≠ `5` (assertion sur la chaîne chiffrée), et le modèle rechargé restitue `5`. |
| `tests/Feature/Diagnostic/DiagnosticScoringServiceTest.php` | — | Non impacté (le service somme en PHP sur `answer_options`) | Aucune. |
| `tests/Feature/Diagnostic/PublicDiagnosticTest.php` (soumission anonyme) | — | Non impacté | Conserver : un diagnostic anonyme n'est toujours pas persisté. |

Le nouveau test central de L05b : après création, `DB::table('diagnostics')->first()->score_total`
ne contient **pas** `5` (chiffré) ; le modèle rechargé restitue exactement `5`.

---

## 7. Synthèse des décisions à porter en L05b / L05c

1. **Colonnes à chiffrer** (recommandé, option A) : `diagnostics.score_total`,
   `diagnostic_responses.score`, `diagnostic_responses.answer_option_id` →
   passer en `text`, casts `encrypted` (et `encrypted` pour la FK stockée en
   texte).
2. **Interprétation** : `result_interpretation_id` **reste en clair** (FK),
   arbitrage à acter dans l'ADR.
3. **Code mort** : supprimer `calculateScore()` / `complete()` (SUM SQL).
4. **Migration** réversible, index inexistants (rien à supprimer), types `integer`
   → `text`.
5. **Commande** `diagnostics:chiffrer` idempotente (lots de 500, transaction,
   détection du déjà-chiffré), pour un environnement déjà peuplé.
6. **Chargement des réponses** (occurrence #4) : après chiffrement de
   `answer_option_id`, charger `answerOption` en PHP par réponse plutôt que par
   jointure `whereIn`.
7. **Tests** : adapter `PublicDiagnosticTest` (§6) et ajouter les tests de
   non-lisibilité en base + idempotence de la commande.
8. **APP_CIPHER** : déjà `AES-256-CBC`, aucune modification.

> **Rappel du plan.** Chiffrer **avant** de peupler la recette (L06 `RecetteSeeder`
> ne doit être lancé qu'après L05). Sur un environnement neuf, la migration change
> le type en place — l'ADR L05c justifiera cette entorse à la règle
> expansion/contraction (aucun environnement n'est déployé à cette date).
