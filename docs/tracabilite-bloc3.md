# Matrice de traçabilité — Dossier Activité 3 ↔ Dépôt

> Relie chaque engagement du dossier à sa **preuve vérifiable** dans le dépôt
> (fichier, workflow, commande, test ou document). Établie en consolidation du
> plan de mise en conformité (lots L00–L24).
>
> **Colonnes :** Renvoi dossier · Engagement · Preuve · Test associé · État.
>
> **États :** **Conforme** (preuve vérifiable présente) · **Conforme (cible)**
> (décrit et outillé, mise en service relevant d'une décision — production) ·
> **ÉCART ASSUMÉ** (limite reconnue et justifiée, comme le fait la conclusion du
> dossier).
>
> [!IMPORTANT]
> Les libellés de renvoi (Tableau N, ligne) sont reconstitués d'après le travail
> réalisé. **Recouper la numérotation exacte avec le dossier avant la soutenance.**
> Les **preuves**, elles, sont réelles et ouvrables dans le dépôt : c'est ce qui
> fait foi. Aucune ligne n'est marquée « conforme » sans preuve nommée.

---

## Écarts assumés (en tête, comme l'exige le critère d'acceptation)

Ces écarts sont **reconnus et justifiés**, à l'image de l'audit d'accessibilité
non conduit et de l'absence de redondance matérielle déjà assumés en conclusion
du dossier.

| Renvoi | Engagement | Preuve | Test | État |
| :-- | :-- | :-- | :-- | :-- |
| Conclusion (accessibilité) | Audit RGAA complet | Déclaration partielle `resources/js/pages/public/Accessibility.vue` + plan `docs/backlog/audit-rgaa-complet.md` | `tests/Feature/Accessibility/AccessibilityDeclarationTest.php` | **ÉCART ASSUMÉ** — audit non conduit, annoncé honnêtement |
| §3.2 / Tableau 16 | Un seul environnement **déployé** (recette) ; production **décrite en cible** | `docs/adr/0005-un-seul-environnement-deploye.md` · `.github/workflows/deploiement-production.yml` | — | **ÉCART ASSUMÉ** — mise en service = décision du Ministère |
| §3.2 (dimensionnement) | Redondance matérielle / haute disponibilité | Instance unique (2 cœurs / 12 Go) | — | **ÉCART ASSUMÉ** — hors périmètre, reconnu au dossier |

---

## Tableau 8 — Risques (R1–R12) et mesures de traitement

> R1, R10, R11 sont confirmés par les commentaires de code / ADR. Pour les autres,
> le risque est nommé de façon descriptive et **relié à sa mesure vérifiable** ;
> aligner le numéro exact sur le Tableau 8.

| Renvoi | Risque | Mesure — Preuve | Test | État |
| :-- | :-- | :-- | :-- | :-- |
| R1 | Accès direct à la base / fuite de secrets (donnée de santé en clair) | Chiffrement AES-256 des diagnostics (`app/Models/Diagnostic.php`, `DiagnosticResponse.php`, ADR 0002) · gitleaks (`.gitleaks.toml`, `ci.yml` job 1) · sauvegardes chiffrées (`docker/sauvegarde/sauvegarder.sh`) | `ChiffrementDiagnosticTest`, `VerifierDechiffrementDiagnosticTest` | Conforme |
| R2 | Force brute sur l'authentification | Limitation de débit login/2FA/reset/register (`app/Providers/FortifyServiceProvider.php`, `docs/securite/limitation-de-debit.md`) | `tests/Feature/Auth/*` | Conforme |
| R3 | Usurpation / vol de session | 2FA (Fortify), sessions chiffrées (`SESSION_ENCRYPT`), éviction du compte inactif (`EnsureUserIsActive`) | `tests/Feature/Auth/InactiveUserTest.php` | Conforme |
| R4 | Élévation de privilèges | Rôles hiérarchisés + policies + double barrière validation/autorisation (`app/Policies/`, `app/Http/Requests/Admin/Users/`) | `tests/Feature/Policies/UserPolicyTest.php`, `Admin/Users/UserAdminTest.php` | Conforme |
| R5 | Injection SQL | Eloquent / requêtes paramétrées, aucune requête brute avec entrée utilisateur | suites CRUD | Conforme |
| R6 | XSS / injection de contenu | Échappement Vue/Inertia + CSP (`app/Http/Middleware/EnTetesDeSecurite.php`, `docs/securite/en-tetes-securite.md`) | — | Conforme |
| R7 | CSRF | Protection CSRF native Laravel sur les formulaires | suites de formulaires | Conforme |
| R8 | Interception réseau (MITM) | HTTPS forcé + HSTS + Traefik/Let's Encrypt (`compose.prod.yml`, en-têtes L04) | — | Conforme |
| R9 | Indisponibilité de service | Sonde de disponibilité + `/up` vérifiant la base (`docs/exploitation/supervision.md`, `app/Listeners/VerifierAccesBaseDeDonnees.php`) | `tests/Feature/HealthCheckTest.php` | Conforme |
| R10 | Cloisonnement des environnements insuffisant | Garde d'environnement, `noindex` recette, basicAuth recette (`docs/securite/cloisonnement-environnements.md`, `compose.recette.yml`) | tests de cloisonnement | Conforme |
| R11 | Non-respect des droits RGPD (portabilité) | Export JSON des données personnelles (`app/Http/Controllers/Settings/DataExportController.php`) | test d'export | Conforme |
| R12 | Perte de données | Sauvegardes 3-2-1 chiffrées + restauration éprouvée (`docker/sauvegarde/`, `docs/exploitation/registre-restaurations.md`) | `tester-restauration.sh` (drill réel) | Conforme |

---

## Tableau 9 — Mesures de protection (15)

| Renvoi | Engagement | Preuve | Test | État |
| :-- | :-- | :-- | :-- | :-- |
| T9.1 | Rôles hiérarchisés + 2 intergiciels + policies | `app/Enums/Role.php`, `app/Http/Middleware/EnsureUserHasRole.php`, `EnsureUserIsActive.php`, `app/Policies/` | `RoleMiddlewareTest`, `AdminPoliciesTest` | Conforme |
| T9.2 | Interdiction d'auto-administration, pas d'élévation par l'UI | `app/Policies/UserPolicy.php`, `ChangeUserRoleRequest.php` | `UserPolicyTest`, `UserAdminTest` | Conforme |
| T9.3 | Vérif. e-mail · 2FA · complexité mot de passe · anti-fuite en prod | `config/fortify.php`, `app/Providers/AppServiceProvider.php` | suites Auth | Conforme |
| T9.4 | Compte désactivé refusé au login et évincé ensuite | `FortifyServiceProvider.php`, `EnsureUserIsActive.php` | `InactiveUserTest` | Conforme |
| T9.5 | Journal d'audit centralisé + visualiseur lecture seule | `app/Services/AuditLogger.php`, `app/Observers/AuditableObserver.php`, `AuditLogController.php` | `Audit/*`, `AuditLogViewerTest` | Conforme |
| T9.6 | Secrets occultés à l'écriture du journal | `app/Services/AuditLogger.php` (`redact()`), `#[Hidden]` sur `User` | `AuthAuditTest` | Conforme |
| T9.7 | Diagnostic anonyme sans persistance | `app/Http/Controllers/Diagnostic/DiagnosticController.php` | `PublicDiagnosticTest` | Conforme |
| T9.8 | Suppression de compte en cascade, sans soft-delete | migrations `diagnostics`/`diagnostic_responses` (`cascadeOnDelete`) | `UserAdminTest` (cascade) | Conforme |
| T9.9 | Couverture de tests et frontières d'autorisation | suite PHPUnit (`php artisan test`) | ~49 tests d'autorisation | Conforme |
| T9.10 | Limitation de débit (auth + réinitialisation) | `FortifyServiceProvider.php`, `docs/securite/limitation-de-debit.md` | suites Auth | Conforme |
| T9.11 | En-têtes de sécurité + CSP + HTTPS forcé | `app/Http/Middleware/EnTetesDeSecurite.php`, `docs/securite/en-tetes-securite.md` | — | Conforme |
| T9.12 | Chiffrement au repos des données de santé | `app/Models/Diagnostic.php` (casts `encrypted`), ADR 0002 | `ChiffrementDiagnosticTest` | Conforme |
| T9.13 | Cloisonnement des environnements | `docs/securite/cloisonnement-environnements.md`, garde d'environnement | tests de cloisonnement | Conforme |
| T9.14 | Durées de conservation appliquées (préavis + purges) | commandes `PurgeInactiveAccounts`, `SendInactivityWarnings`, `PurgeAuditLogs`, `docs/exploitation/durees-de-conservation.md` | suites de purge | Conforme |
| T9.15 | Détection de secrets + audit des dépendances (CI) | `ci.yml` (jobs 1 et 6), `.gitleaks.toml`, `.github/dependabot.yml` | — | Conforme |

---

## Tableau 10 — Dispositifs cryptographiques (7)

| Renvoi | Engagement | Preuve | Test | État |
| :-- | :-- | :-- | :-- | :-- |
| T10.1 | Chiffrement applicatif AES-256 des résultats de diagnostic, clé hors base | `app/Models/Diagnostic.php`, `DiagnosticResponse.php`, ADR 0002 | `VerifierDechiffrementDiagnosticTest` | Conforme |
| T10.2 | Sauvegardes chiffrées AES-256 **avant** transfert hors serveur | `docker/sauvegarde/sauvegarder.sh` (`gpg --cipher-algo AES256`) | drill L19 | Conforme |
| T10.3 | Transport TLS (HTTPS forcé, HSTS) | Traefik + Let's Encrypt (`compose.prod.yml`), en-têtes L04 | — | Conforme |
| T10.4 | Empreintes de mot de passe (bcrypt) | cast `hashed`, `BCRYPT_ROUNDS=12` | suites Auth | Conforme |
| T10.5 | Secret 2FA protégé | Fortify (`two_factor_secret`), occulté au journal | `AuthAuditTest` | Conforme |
| T10.6 | Sessions chiffrées | `SESSION_ENCRYPT=true` (`.env.deploy.example`) | — | Conforme |
| T10.7 | Gestion et rotation des clés/secrets | `docs/exploitation/rotation-des-secrets.md`, `APP_PREVIOUS_KEYS`, gitleaks | — | Conforme |

---

## Tableau 13 — Droits des personnes (5)

| Renvoi | Engagement | Preuve | Test | État |
| :-- | :-- | :-- | :-- | :-- |
| T13.1 | Droit d'accès | Profil + historique de diagnostic (pages `settings`, `diagnostic/history`) | suites profil/historique | Conforme |
| T13.2 | Droit de rectification | Édition du profil (`Settings/ProfileController`) | suites profil | Conforme |
| T13.3 | Droit à l'effacement | Suppression de compte en cascade (dure) | `UserAdminTest` (cascade) | Conforme |
| T13.4 | Droit à la portabilité (art. 20) | Export JSON (`DataExportController`, L07) | test d'export | Conforme |
| T13.5 | Consentement / information | `consented_at` + politique de confidentialité (L08) | suites de consentement | Conforme |

---

## Tableau 14 — Scénarios de reprise d'activité (5)

| Renvoi | Scénario | Preuve | Test | État |
| :-- | :-- | :-- | :-- | :-- |
| T14.1 | Perte de données → restauration | `docker/sauvegarde/restaurer.sh`, `tester-restauration.sh`, `registre-restaurations.md` | drill réel (registre) | Conforme |
| T14.2 | Déploiement défectueux → retour arrière | `.github/workflows/retour-arriere.yml`, `procedure-de-deploiement.md` | — | Conforme (cible : capture d'un retour arrière réel à la soutenance) |
| T14.3 | Migration risquée → règle expansion/contraction | `regle-expansion-contraction.md`, `.github/workflows/garde-migrations.yml` | — | Conforme |
| T14.4 | Indisponibilité → détection + `/up` | `supervision.md`, `VerifierAccesBaseDeDonnees.php`, Uptime Kuma | `HealthCheckTest` | Conforme |
| T14.5 | Compromission d'un secret → rotation intégrale | `runbook-incident.md`, `rotation-des-secrets.md` | — | Conforme |

---

## Tableau 16 — Caractéristiques des environnements (13)

| Renvoi | Caractéristique | Preuve | État |
| :-- | :-- | :-- | :-- |
| T16.1 | Instance ARM 2 cœurs / 12 Go, région française | `docs/exploitation/provisionnement.md` | Conforme (cible — provisionnement manuel) |
| T16.2 | Un seul environnement déployé : la recette | ADR 0005, `deploiement-recette.yml` | Conforme |
| T16.3 | Production décrite en cible, sans mise en service | `deploiement-production.yml` (sans secrets, approbation manuelle) | Conforme (cible) |
| T16.4 | Base de données sans port publié | `compose.prod.yml` (service `db` sans `ports`) | Conforme |
| T16.5 | Seul le proxy publie 80/443 | `compose.prod.yml` (Traefik) | Conforme |
| T16.6 | HTTPS Let's Encrypt (défi HTTP) | `compose.prod.yml` (résolveur ACME) | Conforme |
| T16.7 | Recette non indexée (`noindex`/robots) | `routes/web.php` (robots.txt recette), L06 | Conforme |
| T16.8 | Accès recette protégé (basicAuth) | `compose.recette.yml` (`RECETTE_BASICAUTH`) | Conforme |
| T16.9 | Déclenchement auto à la fusion sur `dev` (recette) | `deploiement-recette.yml` (`workflow_run` CI) | Conforme |
| T16.10 | Déclenchement à l'étiquette `v*` sur `main` (prod cible) | `deploiement-production.yml` | Conforme (cible) |
| T16.11 | Migrations en étape distincte (`migrate --force`) | `deploiement-recette.yml` | Conforme |
| T16.12 | Sauvegardes : quotidiennes locales (recette) / 3-2-1 externalisées (prod) | `docker/sauvegarde/`, `.env.deploy.example` (`BACKUP_REMOTE_ENABLED`) | Conforme |
| T16.13 | Secrets hors dépôt (environnements de la forge / `.env` 600) | `.env.deploy.example`, `provisionnement.md`, environnements GitHub | Conforme |

---

## Tableau 19 — Chaîne d'intégration (9 étapes)

Toutes dans `.github/workflows/ci.yml`, agrégées par le job « Chaîne verte ».

| Renvoi | Étape | Preuve (job) | Bloquant | État |
| :-- | :-- | :-- | :-- | :-- |
| T19.1 | Détection de secrets | job 1 — gitleaks | oui | Conforme |
| T19.2 | Style et formatage | job 2 — Pint + ESLint + Prettier | oui | Conforme |
| T19.3 | Vérification des types | job 3 — vue-tsc (+ Wayfinder) | oui | Conforme |
| T19.4 | Analyse statique serveur | job 4 — PHPStan/Larastan (baseline gardé) | oui | Conforme |
| T19.5 | Tests automatisés | job 5 — `php artisan test` sur PostgreSQL 16 | oui | Conforme (241 tests) |
| T19.6 | Audit des dépendances | job 6 — composer + npm audit (bloque au critique) | oui (critique) | Conforme |
| T19.7 | Contrôle du fichier de construction | job 7 — hadolint | oui | Conforme |
| T19.8 | Construction de l'image (linux/arm64) | job 8 — build-push-action | oui | Conforme |
| T19.9 | Analyse de vulnérabilités de l'image | job 9 — Trivy (bloque au critique) | oui (critique) | Conforme |

---

## Synthèse

Aucune ligne « conforme » sans preuve nommée et ouvrable dans le dépôt. Les seuls
**écarts sont assumés** et justifiés (audit d'accessibilité non conduit,
environnement unique déployé par décision, absence de redondance matérielle) —
exactement de la même nature que les limites déjà reconnues en conclusion du
dossier. Les mentions **« Conforme (cible) »** concernent la production, décrite
et outillée mais dont la mise en service relève du Ministère.
