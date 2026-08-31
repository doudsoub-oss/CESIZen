# Audit de conformité — Dossier Activité 3 ↔ Code (Lot L00)

> **Objet.** Vérifier, avant tout ajout, que les mesures que le dossier Activité 3 déclare
> **opérationnelles (✔ au Tableau 9)** existent réellement dans le dépôt. Ce document ne modifie
> aucun code : il constate.
>
> **Date de l'audit.** 2026-08-31 · **Branche.** `dev` · **Dernier commit.** `18839c7` (RGPD audit_logs).
>
> **Légende.** **VÉRIFIÉ** = présent et localisé · **PARTIEL** = présent mais incomplet au regard
> de l'affirmation · **ABSENT** = non trouvé.

---

## Synthèse

| # | Affirmation (résumé) | État |
| :-- | :-- | :-- |
| 1 | Rôles hiérarchisés · 2 intergiciels · 9 policies | **VÉRIFIÉ** (nuance : 10 fichiers de policy, voir §1) |
| 2 | Auto-administration interdite · pas d'élévation UI · double barrière | **VÉRIFIÉ** |
| 3 | Vérif. e-mail · 2FA · complexité mdp partout · `uncompromised` en prod | **VÉRIFIÉ** |
| 4 | Compte désactivé refusé au login **et** évincé à la requête suivante | **VÉRIFIÉ** |
| 5 | Journal centralisé · observer sur 9 modèles · événements auth · visualiseur lecture seule | **VÉRIFIÉ** |
| 6 | Empreintes / secrets 2FA / jetons occultés **à l'écriture** | **VÉRIFIÉ** |
| 7 | Diagnostic utilisable sans compte, aucun résultat conservé alors | **VÉRIFIÉ** |
| 8 | Suppression de compte → cascade diagnostics + réponses, sans soft-delete | **VÉRIFIÉ** |
| 9 | Décompte des tests PHPUnit et frontières d'autorisation | **VÉRIFIÉ** — 163 méthodes / 184 cas / ~49 d'autorisation |

**Aucune affirmation ✔ du Tableau 9 n'est ABSENTE.** Les écarts relevés (§ « Écarts à corriger »)
portent sur des mesures que le dossier annonce comme *à mettre en place* (Tableau 9 « ○ »), pas
comme faites — ils sont le périmètre des lots suivants, non des mensonges du dossier.

---

## 1. Rôles hiérarchisés · deux intergiciels · neuf règles d'autorisation — **VÉRIFIÉ**

**Enum de rôle hiérarchisé.** `app/Enums/Role.php`
- Trois cas `User` / `Admin` / `SuperAdmin` — lignes 7-9.
- Niveau numérique `level()` (0 / 10 / 20) — lignes 20-27.
- `isAtLeast()` (comparaison de niveau) — lignes 29-32.

**Deux intergiciels de contrôle d'accès**, avec alias déclarés dans `bootstrap/app.php:21-23` :
- `role` → `EnsureUserHasRole` — `app/Http/Middleware/EnsureUserHasRole.php:11-39`
  (401 si non authentifié, 403 sinon ; accepte plusieurs rôles).
- `active` → `EnsureUserIsActive` — `app/Http/Middleware/EnsureUserIsActive.php:10-32`
  (ajouté au groupe `web`, `bootstrap/app.php:30`).

**Neuf règles d'autorisation (policies) sur les modèles administrés** — `app/Policies/` :

| # | Policy | Fichier |
| :-- | :-- | :-- |
| 1 | UserPolicy | `app/Policies/UserPolicy.php` |
| 2 | CategoryPolicy | `app/Policies/CategoryPolicy.php` |
| 3 | ContentPolicy | `app/Policies/ContentPolicy.php` |
| 4 | MenuPolicy | `app/Policies/MenuPolicy.php` |
| 5 | MenuItemPolicy | `app/Policies/MenuItemPolicy.php` |
| 6 | QuestionnairePolicy | `app/Policies/QuestionnairePolicy.php` |
| 7 | QuestionPolicy | `app/Policies/QuestionPolicy.php` |
| 8 | AnswerOptionPolicy | `app/Policies/AnswerOptionPolicy.php` |
| 9 | ResultInterpretationPolicy | `app/Policies/ResultInterpretationPolicy.php` |

> **Point d'attention à réconcilier avant soutenance (écart favorable).** Le dossier annonce
> **neuf** policies. Le dépôt en contient **dix** : les neuf ci-dessus (qui correspondent
> exactement aux neuf modèles administrés observés, §5) **plus** `AuditLogPolicy`
> (`app/Policies/AuditLogPolicy.php`), qui gouverne le visualiseur d'audit en lecture seule
> mentionné à l'**affirmation 5**. Le compte « 9 » est cohérent si l'on rattache `AuditLogPolicy`
> à l'affirmation 5 (autorisation du journal) plutôt qu'à l'affirmation 1 (policies des modèles
> administrés). **Formulation de soutenance :** « neuf policies sur les neuf modèles administrés,
> plus une dixième dédiée à l'autorisation du journal d'audit en lecture seule ». Ne pas prétendre
> qu'il n'y en a que neuf si le jury ouvre le dossier `app/Policies/`.

---

## 2. Interdiction d'auto-administration · pas d'élévation par l'UI · double barrière — **VÉRIFIÉ**

**Nul ne modifie son propre compte administrateur.** `app/Policies/UserPolicy.php`
- `update()` refuse `$user->id === $target->id` — lignes 39-41.
- `delete()` et `toggleActive()` délèguent à `update()` — lignes 54-62 (donc même garde).
- `changeRole()` refuse aussi le self — lignes 70-72.

**L'élévation au rôle le plus élevé est impossible via l'interface.**
- Policy : `changeRole()` refuse si la cible est déjà `SuperAdmin` **ou** si le nouveau rôle est
  `SuperAdmin` — `app/Policies/UserPolicy.php:78-80`.
- Création : `StoreUserRequest::allowedRoles()` n'autorise jamais `super_admin`
  (`user` pour un admin, `user`+`admin` pour un super-admin) — `app/Http/Requests/Admin/Users/StoreUserRequest.php:37-44`.

**Double barrière validation + autorisation.**
- Validation (form request) : `ChangeUserRoleRequest::rules()` = `Rule::in([User, Admin])`
  — `app/Http/Requests/Admin/Users/ChangeUserRoleRequest.php:24-29` ; `StoreUserRequest` idem.
- Autorisation (policy) : `ChangeUserRoleRequest::authorize()` appelle
  `can('changeRole', [$target, $role])` — même fichier, lignes 12-22 ;
  `StoreUserRequest::authorize()` appelle `can('create', User::class)` — `StoreUserRequest.php:16-19`.

Preuves de test : `tests/Feature/Policies/UserPolicyTest.php`
(`test_no_user_can_admin_themselves`, `test_change_role_is_super_admin_only_and_never_creates_or_modifies_super_admin`)
et `tests/Feature/Admin/Users/UserAdminTest.php`
(`test_nobody_can_create_a_super_admin_via_the_admin`, `test_role_change_to_super_admin_is_rejected`,
`test_admin_cannot_toggle_themselves`, `test_admin_cannot_delete_themselves`).

---

## 3. Vérification e-mail · double facteur · complexité mdp partout · anti-fuite en prod — **VÉRIFIÉ**

`config/fortify.php:147-156` — fonctionnalités actives :
- `Features::emailVerification()` — vérification d'adresse.
- `Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true])` — 2FA avec confirmation.
- `Features::registration()`, `Features::resetPasswords()`.

**Complexité imposée dans tous les environnements**, durcie en production —
`app/Providers/AppServiceProvider.php:78-90` :
- Base (tous environnements) : `min(8)->mixedCase()->numbers()->symbols()` — lignes 81-84.
- Production uniquement : `->min(12)->uncompromised()` (contrôle contre les bases de mots de passe
  compromis) — lignes 87-89.
- Règles appliquées via `Password::default()` dans `app/Concerns/PasswordValidationRules.php:15-18`,
  réutilisé par inscription, réinitialisation, création admin et changement de mot de passe.

> **Note.** L'anti-fuite (`uncompromised`) est bien conditionné à la production seule, exactement
> comme l'affirme le dossier (« activé en production uniquement »).

---

## 4. Compte désactivé refusé au login **et** évincé à la requête suivante — **VÉRIFIÉ**

**Refus à la connexion** — `app/Providers/FortifyServiceProvider.php:53-70` :
`authenticateUsing()` lève une `ValidationException` (« Votre compte a été désactivé… ») si
`! $user->is_active`, après vérification du mot de passe (le message ne distingue pas mot de passe
faux et compte inactif du point de vue d'un attaquant, mais informe l'usager légitime).

**Éviction à la requête suivante** — `app/Http/Middleware/EnsureUserIsActive.php:20-28` :
si l'utilisateur authentifié devient inactif, `logout()` + `session()->invalidate()` +
`regenerateToken()` puis redirection vers `login`. Middleware ajouté au groupe `web`
(`bootstrap/app.php:30`), donc actif sur toute requête authentifiée.

Preuves : `tests/Feature/Auth/InactiveUserTest.php`
(`test_inactive_user_is_blocked_at_login_with_a_field_error`,
`test_authenticated_user_who_becomes_inactive_is_logged_out_on_next_request`).

---

## 5. Journal centralisé · observer sur 9 modèles · événements auth · visualiseur lecture seule — **VÉRIFIÉ**

**Écriture centralisée** — `app/Services/AuditLogger.php` : point d'entrée unique pour la
capture acteur / IP / user-agent et la rédaction. Méthodes `log()` (modèle, lignes 35-51) et
`auth()` (événements, lignes 61-76).

**Observer générique sur les neuf modèles administrés** — `app/Observers/AuditableObserver.php`
(événements `created`/`updated`/`deleted`, action `{model}.{event}`, diff old/new),
enregistré en boucle dans `app/Providers/AppServiceProvider.php:48-62` sur :
`User, Category, Content, Menu, MenuItem, Questionnaire, Question, AnswerOption, ResultInterpretation`
(= les neuf ; `AuditLog` n'est volontairement pas observé, c'est la cible du journal).
- Enregistrement du **différentiel** : `updated()` compare `getRawOriginal()` et `getChanges()`
  — `AuditableObserver.php:44-63`.
- Colonnes gérées par les listeners d'auth exclues du doublon — lignes 23-29, 51-54.

**Journalisation des événements d'authentification** — `app/Listeners/AuditAuthEvents.php`
(souscription `AppServiceProvider.php:64`) : login, logout, failed, password_reset,
password_updated, 2FA enabled/confirmed/disabled — lignes 25-83.

**Visualiseur filtrable et EN LECTURE SEULE** :
- `app/Http/Controllers/Admin/AuditLogController.php` : **une seule action `index()`**
  (aucune `store`/`update`/`destroy`), filtres action / user / type / dates — lignes 13-55.
- Route unique `GET admin/audit-logs` (`admin.audit-logs.index`) — confirmé via `route:list`.
- Derrière `AuditLogPolicy::viewAny` (admin+) — `app/Policies/AuditLogPolicy.php:13-16`,
  appelée en tête d'`index()` (`AuditLogController.php:15`).

Preuves : `tests/Feature/Audit/ModelAuditTest.php`, `tests/Feature/Audit/AuthAuditTest.php`,
`tests/Feature/Admin/AuditLogViewerTest.php` (`test_regular_user_cannot_view_the_audit_trail`).

---

## 6. Empreintes / secrets 2FA / jetons occultés **à l'écriture** — **VÉRIFIÉ**

`app/Services/AuditLogger.php` :
- Constante `REDACTED_KEYS = [password, two_factor_secret, two_factor_recovery_codes, remember_token]`
  — lignes 22-27.
- `redact()` remplace toute valeur sensible par `'[redacted]'` **avant** l'écriture — lignes 85-98.
- Appliquée sur `old_values`/`new_values` dans `log()` (lignes 46-47) **et** sur le contexte de
  `auth()` (ligne 72). La rédaction est donc faite à l'écriture, jamais à la lecture : la valeur
  (même chiffrée) n'est jamais consignée.

Renforts cohérents : `#[Hidden([...])]` sur le modèle `User` (`app/Models/User.php:17`) et
exclusion de ces colonnes du diff de l'observer (`AuditableObserver.php:23-29`).

Preuve : `tests/Feature/Audit/AuthAuditTest.php` (assertions d'absence des secrets dans le journal).

---

## 7. Diagnostic utilisable sans compte, aucun résultat conservé alors — **VÉRIFIÉ**

`app/Http/Controllers/Diagnostic/DiagnosticController.php:59-91` :
- Le scoring est calculé pour tous — ligne 66.
- La persistance (`Diagnostic::create` + `responses()->createMany`) est **conditionnée à
  `$request->user() !== null`** — lignes 68-82. Un usager anonyme reçoit le résultat inline,
  **sans aucune écriture en base**.
- Les routes publiques `index`/`show`/`submit` ne sont pas derrière `auth` (surface publique).

Preuve : `tests/Feature/Diagnostic/PublicDiagnosticTest.php`
(`test_anonymous_submission_returns_result_without_db_write`).

> **Note technique cohérente avec le chiffrement à venir (L05).** `diagnostics.user_id` est
> `NOT NULL` + `cascadeOnDelete` (`…_create_diagnostics_table.php:16`), ce qui interdit
> structurellement un diagnostic anonyme persistant — la garde applicative et la contrainte
> de schéma se renforcent.

---

## 8. Suppression de compte → cascade diagnostics + réponses, sans soft-delete — **VÉRIFIÉ**

**Cascades FK** :
- `diagnostics.user_id` → `constrained()->cascadeOnDelete()`
  — `database/migrations/2026_03_25_120903_create_diagnostics_table.php:16`.
- `diagnostic_responses.diagnostic_id` → `cascadeOnDelete()`
  — `database/migrations/2026_03_25_120907_create_diagnostic_responses_table.php:16`.
- `audit_logs.user_id` → `nullable()->constrained()->cascadeOnDelete()`
  — `…_create_audit_logs_table.php:16`.

Donc la suppression d'un `User` efface ses `diagnostics`, qui effacent leurs `diagnostic_responses`.

**Aucun soft-delete** : recherche `softDelete|SoftDeletes|deleted_at` sur `database/migrations` et
`app/Models` → **aucun résultat**. `is_active` est le seul levier de visibilité, la suppression est
dure, conformément au dossier.

Preuves : `tests/Feature/Admin/Users/UserAdminTest.php`
(`test_hard_delete_cascades_user_data`, `test_self_deletion_via_settings_cascades`).

---

## 9. Décompte des tests et frontières d'autorisation — **VÉRIFIÉ**

**Méthodes de test PHPUnit : 163** (toutes en style `test_*`, aucune sur attribut `#[Test]`).
→ **Correspond exactement au chiffre annoncé au dossier (163 à la date de dépôt).**

**Cas exécutés : 184, tous verts** (615 assertions, `php artisan test`, 2026-08-31).
L'écart 163 → 184 vient des **jeux de données paramétrés** (data providers), qui comptent chaque
jeu comme un cas. Aucun test en échec, aucun ignoré.

**Tests couvrant spécifiquement des frontières d'autorisation : ~49.**

Suites dédiées (42) :

| Fichier | Méthodes | Portée |
| :-- | --: | :-- |
| `tests/Feature/Admin/Users/UserAdminTest.php` | 18 | matrice de privilèges admin / super-admin, self, élévation |
| `tests/Feature/Auth/RoleMiddlewareTest.php` | 6 | intergiciel `role` (guest/user/admin/super-admin) |
| `tests/Feature/Policies/UserPolicyTest.php` | 5 | frontières de la `UserPolicy` |
| `tests/Feature/Policies/AdminPoliciesTest.php` | 3 | policies des modèles administrés |
| `tests/Feature/Admin/DashboardAccessTest.php` | 3 | accès au tableau de bord admin |
| `tests/Feature/Auth/InactiveUserTest.php` | 3 | refus login + éviction du compte inactif |
| `tests/Unit/Enums/RoleTest.php` | 4 | hiérarchie des niveaux de rôle (1 test hors sujet : libellés) |

Gardes d'autorisation intégrées aux suites CRUD (7) : `AuditLogViewerTest` (2),
`CategoryControllerTest` (2), `QuestionnaireAdminTest` (1), `ContentControllerTest` (1),
`PublicDiagnosticTest` (1, `history_index_requires_auth`).

> **Point de soutenance (rappelé par le plan).** 163 méthodes à la date de dépôt, le chiffre
> augmentera avec les lots de sécurisation (L03-L09 notamment). Ne pas corriger le dossier :
> « 163 à la date de dépôt, N aujourd'hui, l'écart correspond aux tests ajoutés par les lots de
> sécurisation ».

---

## Écarts à corriger (classés par gravité)

> Ces écarts ne contredisent **pas** le dossier : ils portent tous sur des mesures qu'il annonce
> comme *à réaliser* (Tableau 9 « ○ ») et constituent le périmètre des lots suivants. Ils sont
> listés ici pour cadrer l'ordre de travail, pas comme des défauts d'un état déclaré « fait ».

### Gravité — à réconcilier avant soutenance (cohérence du discours)

1. **Nombre de policies : 10 fichiers vs « 9 » annoncés.** Voir §1. Écart favorable, mais le
   discours de soutenance doit être fixé : « 9 modèles administrés + 1 policy de journal ».
   *Aucune correction de code. Décision de formulation.*

### Gravité — cœur de l'activité 3, non commencé (lots P0)

2. **Limitation de débit incomplète (L03).** `app/Providers/FortifyServiceProvider.php:106-117` :
   le limiteur `login` existe (**5/min par email+IP**) et `two-factor` (5/min), mais il **manque**
   la seconde contrainte cumulée « par compte » (10/h par email), et **aucun** limiteur
   `password-reset` ni `register`. Le dossier exige un plafonnement *par adresse **et** par compte*
   sur authentification **et** réinitialisation. → **Lot L03.**
3. **En-têtes de sécurité / CSP / HSTS absents (L04).** Aucun middleware d'en-têtes, pas de
   `Vite::useCspNonce()`, pas de `trustProxies`, pas de `URL::forceScheme('https')`. → **Lot L04.**
4. **Chiffrement AES-256 des résultats de diagnostic absent (L05).** `diagnostics.score_total`,
   interprétation et réponses sont en clair (aucun cast `encrypted`). C'est **la** mesure qui
   soutient l'argumentation article 9 du dossier. → **Lot L05** (à traiter avant tout peuplement
   de recette).
5. **Cloisonnement des environnements non outillé (L06).** Pas de garde-fou « `APP_DEBUG=true`
   hors dev empêche le démarrage », pas de `RecetteSeeder`, pas de `noindex` recette. → **Lot L06.**
6. **Aucune chaîne d'intégration ni image de production (L11-L13).** Pas de `Dockerfile.prod`,
   pas de `.github/workflows/`. → **Lots L11-L13.**

### Gravité — engagements RGPD écrits mais non effectifs (lots P1)

7. **Export des données / portabilité absent (L07)** — Tableau 13 marqué « ○ ».
8. **Consentement explicite au rattachement d'un diagnostic non matérialisé (L08)** — pas de
   colonne `consented_at`, pas de politique de confidentialité liée à l'inscription.
9. **Durées de conservation non appliquées (L09)** — aucune commande de préavis/purge, à vérifier :
   présence d'un `last_login_at` alimenté (prérequis de L09).
10. **Déclaration d'accessibilité absente (L10)** — page `/accessibilite` à créer (honnête :
    audit non conduit).

---

## Conclusion

Les **neuf affirmations ✔ du Tableau 9 sont confirmées dans le code**, avec une seule nuance de
décompte (policies : 10 fichiers pour « 9 » annoncés, écart favorable à expliquer). La suite de
tests est **intégralement verte** (184 cas), et le décompte de **163 méthodes** coïncide exactement
avec le chiffre du dossier. Le socle de sécurité applicative décrit comme *fait* l'est
effectivement ; le travail restant (§ Écarts) correspond aux mesures que le dossier annonce comme
*à réaliser* dans le cadre de l'activité 3 — c'est le périmètre des lots L01 et suivants.
