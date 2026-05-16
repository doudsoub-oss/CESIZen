# CESIZen — Backend Behaviour & Test Coverage

This document is the single source of truth for what the backend currently does. Use it to:

- **Verify** that every expected behaviour is enforced by a test (no silent regressions).
- **Plan the frontend**: each section lists the routes a Vue page must call, the inputs/outputs, and the rules already enforced server-side.

Counts as of step 06: **152 tests / 478 assertions / 67 named routes**.

Run everything: `docker compose exec app php artisan test --compact`.

## Conventions

- **Auth gates**: *guest* (anyone), *auth* (logged-in), *verified* (auth + email verified), *active* (auth + `is_active=true`), *admin* (≥ `Role::Admin`), *super_admin* (= `Role::SuperAdmin`). Admin routes are gated by `auth+verified+active+role:admin`.
- **Inertia responses** point at a Vue page (e.g. `public/Information/Index`). The Vue file does **not** have to exist for the route to function — tests just probe HTTP + props. Vue files are step 07.
- "RGPD cascade" means deleting the parent row hard-deletes children per the FK rules in `docs/database-schema.md` § *Foreign Key Cascade Rules*.

---

## 1. Authentication & Authorisation

### 1.1 Fortify endpoints (scaffolded)

| Method | Path | Name | Gate | Purpose |
|---|---|---|---|---|
| GET | `/login` | `login` | guest | Render the login form. |
| POST | `/login` | `login.store` | guest | Email+password login. Throws `ValidationException` for `is_active=false`. Redirects to 2FA challenge when configured. |
| POST | `/logout` | `logout` | auth | Logout + redirect home. |
| GET | `/register` | `register` | guest | Render registration form. |
| POST | `/register` | — | guest | Create a `Role::User` account via `CreateNewUser` action. |
| GET | `/forgot-password` | `password.request` | guest | Render reset request form. |
| POST | `/forgot-password` | `password.email` | guest | Send reset link. |
| GET | `/reset-password/{token}` | `password.reset` | guest | Render reset form. |
| POST | `/reset-password` | `password.update` | guest | Apply new password via `ResetUserPassword` action. |
| GET | `/email/verify` | `verification.notice` | auth | Render "verify your email" page. |
| GET | `/email/verify/{id}/{hash}` | `verification.verify` | auth | Mark email verified. |
| POST | `/email/verification-notification` | `verification.send` | auth | Resend verification email. |
| GET | `/two-factor-challenge` | `two-factor.login` | session-after-login | Render the 2FA challenge form. |
| POST | `/two-factor-challenge` | — | session-after-login | Submit OTP / recovery code. |
| GET | `/confirm-password` | `password.confirm` | auth | Render password reconfirmation form. |
| POST | `/confirm-password` | `password.confirm.store` | auth | Reconfirm before sensitive actions. |

**Behaviour verified by tests**

| Test | What it proves |
|---|---|
| `AuthenticationTest::test_login_screen_can_be_rendered` | `/login` returns 200 for guests. |
| `AuthenticationTest::test_users_can_authenticate_using_the_login_screen` | Valid email+password logs in and redirects to `/dashboard`. |
| `AuthenticationTest::test_users_with_two_factor_enabled_are_redirected_to_two_factor_challenge` | When 2FA is configured, login redirects to `two-factor.login` instead of completing. |
| `AuthenticationTest::test_users_can_not_authenticate_with_invalid_password` | Wrong password ⇒ remains guest. |
| `AuthenticationTest::test_users_can_logout` | `POST /logout` ⇒ guest + redirect home. |
| `AuthenticationTest::test_users_are_rate_limited` | After 5 failures the throttle kicks in (HTTP 429). |
| `RegistrationTest::test_registration_screen_can_be_rendered` | `/register` returns 200. |
| `RegistrationTest::test_new_users_can_register` | A POST creates a user and logs them in. |
| `PasswordResetTest::test_reset_password_link_screen_can_be_rendered` | `/forgot-password` returns 200. |
| `PasswordResetTest::test_reset_password_link_can_be_requested` | POST sends a Notification (asserted via `Notification::assertSentTo`). |
| `PasswordResetTest::test_reset_password_screen_can_be_rendered` | `/reset-password/{token}` returns 200. |
| `PasswordResetTest::test_password_can_be_reset_with_valid_token` | Valid token + new password ⇒ password updated. |
| `PasswordResetTest::test_password_cannot_be_reset_with_invalid_token` | Bogus token ⇒ validation error on token. |
| `EmailVerificationTest::test_email_verification_screen_can_be_rendered` | `/email/verify` returns 200 for auth-but-unverified users. |
| `EmailVerificationTest::test_email_can_be_verified` | Signed verify URL marks `email_verified_at`. |
| `EmailVerificationTest::test_email_is_not_verified_with_invalid_hash` | Tampered hash ⇒ rejected. |
| `EmailVerificationTest::test_email_is_not_verified_with_invalid_user_id` | Wrong user id ⇒ rejected. |
| `EmailVerificationTest::test_verified_user_is_redirected_to_dashboard_from_verification_prompt` | Already-verified users skip the prompt. |
| `EmailVerificationTest::test_already_verified_user_visiting_verification_link_is_redirected_without_firing_event_again` | Idempotent verification (no duplicate events). |
| `VerificationNotificationTest::test_sends_verification_notification` | Resend POST dispatches a Notification. |
| `VerificationNotificationTest::test_does_not_send_verification_notification_if_email_is_verified` | No notification sent if already verified. |
| `PasswordConfirmationTest::test_confirm_password_screen_can_be_rendered` | `/confirm-password` returns 200 for auth users. |
| `PasswordConfirmationTest::test_password_confirmation_requires_authentication` | Guests are redirected. |
| `TwoFactorChallengeTest::test_two_factor_challenge_redirects_to_login_when_not_authenticated` | `/two-factor-challenge` 302s to `/login` without a session. |
| `TwoFactorChallengeTest::test_two_factor_challenge_can_be_rendered` | With a `login.id` session entry, the page renders. |
| `DashboardTest::test_guests_are_redirected_to_the_login_page` | `/dashboard` redirects guests to `/login`. |
| `DashboardTest::test_authenticated_users_can_visit_the_dashboard` | Logged-in user sees the dashboard. |

### 1.2 Inactive-account block (step 03)

| Layer | Mechanism |
|---|---|
| At login | `FortifyServiceProvider::configureAuthentication` throws `ValidationException` (field `email`, French message) when the user exists + password matches but `is_active=false`. |
| Per request | `EnsureUserIsActive` middleware (appended to the `web` stack) logs out + invalidates session + redirects to `/login` if any in-flight session belongs to a deactivated user. |

| Test | What it proves |
|---|---|
| `InactiveUserTest::test_active_user_can_log_in` | Baseline — `is_active=true` works. |
| `InactiveUserTest::test_inactive_user_is_blocked_at_login_with_a_field_error` | POST `/login` for inactive user ⇒ guest + `email` field error. |
| `InactiveUserTest::test_authenticated_user_who_becomes_inactive_is_logged_out_on_next_request` | If an admin deactivates someone mid-session, their next request bounces them to `/login`. |

### 1.3 Role middleware (step 03)

`Route::middleware('role:admin')` → `EnsureUserHasRole`. Accepts variadic role names; the user's role must be `isAtLeast()` ≥ at least one of them.

| Test | What it proves |
|---|---|
| `RoleMiddlewareTest::test_guest_hitting_role_protected_route_is_redirected_to_login` | Unauth + `role:` middleware ⇒ redirect to `/login`. |
| `RoleMiddlewareTest::test_regular_user_is_forbidden_from_admin_route` | `role=user` hitting `role:admin` ⇒ 403. |
| `RoleMiddlewareTest::test_admin_is_allowed_through_admin_role_check` | `role=admin` passes `role:admin`. |
| `RoleMiddlewareTest::test_super_admin_is_allowed_through_admin_role_check` | Super-admin auto-passes admin checks (hierarchy via `isAtLeast`). |
| `RoleMiddlewareTest::test_admin_is_forbidden_from_super_admin_only_route` | `role:super_admin` rejects regular admins. |
| `RoleMiddlewareTest::test_super_admin_is_allowed_through_super_admin_only_route` | Super-admin passes its own check. |

### 1.4 `Role` enum (`App\Enums\Role`)

| Test | What it proves |
|---|---|
| `RoleTest::test_levels_form_a_strict_hierarchy` | `User < Admin < SuperAdmin`. |
| `RoleTest::test_super_admin_is_at_least_admin_and_user` | `SuperAdmin->isAtLeast()` true for all three cases. |
| `RoleTest::test_admin_is_at_least_admin_and_user_but_not_super_admin` | Admin can't impersonate super-admin checks. |
| `RoleTest::test_user_is_only_at_least_user` | User can't impersonate admin checks. |
| `RoleTest::test_each_case_has_a_french_label` | UI label exists for every case. |

### 1.5 Policies (step 03)

`AdminPoliciesTest` proves the **uniform admin matrix** across the 8 administered models: `Category`, `Content`, `Menu`, `MenuItem`, `Questionnaire`, `Question`, `AnswerOption`, `ResultInterpretation`.

For each model × {regular user, admin, super-admin} × {viewAny, view, create, update, delete}:

| Test (data-provider per model) | What it proves |
|---|---|
| `AdminPoliciesTest::test_regular_user_is_denied_all_admin_actions` | Regular user denied every action on every model. |
| `AdminPoliciesTest::test_admin_is_allowed_all_actions` | Admin allowed every action on every model. |
| `AdminPoliciesTest::test_super_admin_is_allowed_all_actions` | Super-admin allowed every action on every model. |

`UserPolicyTest` proves the nuanced user matrix:

| Test | What it proves |
|---|---|
| `UserPolicyTest::test_regular_user_cannot_perform_any_admin_action_on_users` | Regular users are denied viewAny/view/create/update/delete on `User`. |
| `UserPolicyTest::test_admin_can_manage_regular_users_only` | Admin can view/update/delete only `role=user`; can't act on admins or super-admins. |
| `UserPolicyTest::test_super_admin_can_manage_users_and_admins_but_not_other_super_admins` | Super-admin can act on users + admins; can't update another super-admin. |
| `UserPolicyTest::test_no_user_can_admin_themselves` | Self-admin (update/delete on self) is always denied — use Settings. |
| `UserPolicyTest::test_change_role_is_super_admin_only_and_never_creates_or_modifies_super_admin` | `changeRole`: super-admin only; never to/from `super_admin`; not on self. Admins can't change roles at all. |

---

## 2. Information module (`§5.2`)

### 2.1 Public endpoints

| Method | Path | Name | Returns (Inertia) | Purpose |
|---|---|---|---|---|
| GET | `/informations` | `informations.index` | `public/Information/Index` | Landing: active top-level categories with their children + 3 latest published contents each. |
| GET | `/informations/{category:slug}` | `informations.category` | `public/Information/Category` | Category detail: parent, active children, all published contents. |
| GET | `/informations/{category:slug}/{content:slug}` | `informations.content` | `public/Information/Content` | Single content view, **scope-bound** to its category. |
| GET | `/pages/{content:slug}` | `pages.show` | `public/Pages/Show` | Generic viewer for uncategorised pages (e.g. mentions légales). |

**Visibility rules** (enforced server-side):
- Categories: 404 unless `is_active=true`.
- Contents: 404 unless `is_published=true` **and** (`published_at IS NULL` OR `published_at <= NOW()`).
- Nested URL: 404 if `content.category_id !== category.id` (scopeBindings).

| Test | What it proves |
|---|---|
| `PublicInformationTest::test_informations_landing_is_publicly_accessible` | `/informations` returns 200 even for guests. |
| `PublicInformationTest::test_active_category_page_is_accessible` | Active category returns 200. |
| `PublicInformationTest::test_inactive_category_returns_404` | `is_active=false` ⇒ 404. |
| `PublicInformationTest::test_published_content_under_its_category_is_accessible` | Nested URL works when content really belongs to the category. |
| `PublicInformationTest::test_content_under_wrong_category_returns_404` | Scope-binding rejects mismatched (category, content). |
| `PublicInformationTest::test_unpublished_content_returns_404` | `is_published=false` ⇒ 404 on nested URL. |
| `PublicInformationTest::test_future_dated_content_returns_404` | `published_at` in the future ⇒ 404. |
| `PublicInformationTest::test_page_route_works_for_uncategorised_pages` | `/pages/{slug}` serves `type=page` contents. |
| `PublicInformationTest::test_page_route_returns_404_for_unpublished` | `/pages/{slug}` honours the publish flag. |

### 2.2 Admin: Categories

| Method | Path | Name | Returns | Validation / behaviour |
|---|---|---|---|---|
| GET | `/admin/categories` | `admin.categories.index` | `admin/categories/Index` | Paginated 20, ordered by `parent_id, position`. |
| GET | `/admin/categories/create` | `admin.categories.create` | `admin/categories/Create` | Returns `parentOptions`. |
| POST | `/admin/categories` | `admin.categories.store` | redirect index | `StoreCategoryRequest`: name (required), slug (auto-gen from name when empty, unique), description, parent_id, position, is_active. |
| GET | `/admin/categories/{category}/edit` | `admin.categories.edit` | `admin/categories/Edit` | `parentOptions` excludes self. |
| PUT/PATCH | `/admin/categories/{category}` | `admin.categories.update` | redirect index | `UpdateCategoryRequest`: same + parent cycle rejection (BFS over descendants). |
| DELETE | `/admin/categories/{category}` | `admin.categories.destroy` | redirect index | Hard delete; `contents.category_id` is `SET NULL`. |
| PATCH | `/admin/categories/{category}/toggle-active` | `admin.categories.toggle-active` | back | Flips `is_active`. |

| Test | What it proves |
|---|---|
| `CategoryControllerTest::test_guest_is_redirected_from_admin_categories` | Auth middleware (302 → `/login`). |
| `CategoryControllerTest::test_regular_user_is_forbidden` | Role middleware (403). |
| `CategoryControllerTest::test_admin_sees_the_index` | Admin GET index ⇒ 200. |
| `CategoryControllerTest::test_admin_can_store_a_category` | POST creates row + redirects + slug auto-gen. |
| `CategoryControllerTest::test_slug_is_auto_generated_from_name_when_empty` | Slug = `Str::slug(name)` if not provided. |
| `CategoryControllerTest::test_duplicate_slug_is_rejected` | Unique constraint surfaces as a validation error on `slug`. |
| `CategoryControllerTest::test_admin_can_update_a_category` | PUT modifies the row. |
| `CategoryControllerTest::test_parent_cycle_is_rejected_on_update` | Setting a descendant as parent ⇒ validation error on `parent_id`. |
| `CategoryControllerTest::test_admin_can_delete_a_category` | DELETE removes the row. |
| `CategoryControllerTest::test_toggle_active_flips_the_flag` | PATCH toggle-active flips `is_active`. |

### 2.3 Admin: Contents

| Method | Path | Name | Validation / behaviour |
|---|---|---|---|
| GET | `/admin/contents` | `admin.contents.index` | Paginated 20, ordered `updated_at desc`. |
| GET | `/admin/contents/create` | `admin.contents.create` | Returns `categories` + `types` options. |
| POST | `/admin/contents` | `admin.contents.store` | `StoreContentRequest`: title, slug (auto-gen), body (required), `type` ∈ ContentType, `category_id` (**required unless type=page**), `is_published`, `published_at`. Sets `created_by` from current user. |
| GET | `/admin/contents/{content}/edit` | `admin.contents.edit` | |
| PUT/PATCH | `/admin/contents/{content}` | `admin.contents.update` | Same rules as Store + slug uniqueness ignores self. |
| DELETE | `/admin/contents/{content}` | `admin.contents.destroy` | Hard delete. |

| Test | What it proves |
|---|---|
| `ContentControllerTest::test_regular_user_cannot_access_admin_contents` | 403 for `role=user`. |
| `ContentControllerTest::test_admin_can_store_an_article_in_a_category` | Article with category ⇒ row created + `created_by` set. |
| `ContentControllerTest::test_article_without_category_is_rejected` | `type=article` without `category_id` ⇒ validation error on `category_id`. |
| `ContentControllerTest::test_page_without_category_is_allowed` | `type=page` may have `category_id=null`. |
| `ContentControllerTest::test_admin_can_update_and_delete_a_content` | PUT then DELETE round-trip. |

### 2.4 Admin: Menus & MenuItems

| Method | Path | Name | Validation / behaviour |
|---|---|---|---|
| GET | `/admin/menus` | `admin.menus.index` | Ordered `location, name`, with `items_count`. |
| GET | `/admin/menus/create` | `admin.menus.create` | |
| POST | `/admin/menus` | `admin.menus.store` | `StoreMenuRequest`: name, location ∈ {main, footer, sidebar}. |
| GET | `/admin/menus/{menu}/edit` | `admin.menus.edit` | Loads `items.content`. |
| PUT/PATCH | `/admin/menus/{menu}` | `admin.menus.update` | |
| DELETE | `/admin/menus/{menu}` | `admin.menus.destroy` | Hard delete; items cascade. |
| GET | `/admin/menus/{menu}/items/create` | `admin.menus.items.create` | |
| POST | `/admin/menus/{menu}/items` | `admin.menus.items.store` | `StoreMenuItemRequest`: title, optional url/content_id (≥1 required via `after()`), parent_id (must belong to same menu), position, is_active. |
| GET | `/admin/menus/{menu}/items/{item}/edit` | `admin.menus.items.edit` | Scope-bound to menu. |
| PUT/PATCH | `/admin/menus/{menu}/items/{item}` | `admin.menus.items.update` | Same + parent cycle rejection. |
| DELETE | `/admin/menus/{menu}/items/{item}` | `admin.menus.items.destroy` | |

| Test | What it proves |
|---|---|
| `MenuControllerTest::test_admin_can_crud_a_menu` | Full store → update → delete sequence on a menu. |
| `MenuControllerTest::test_admin_can_add_a_menu_item_to_a_menu` | POST nested item ⇒ row created with `menu_id` set, redirect to edit. |
| `MenuControllerTest::test_menu_item_without_url_or_content_is_rejected` | Cross-field validation: missing target ⇒ error on `target`. |
| `MenuControllerTest::test_menu_item_parent_must_belong_to_same_menu` | Parent from a different menu ⇒ error on `parent_id`. |
| `MenuControllerTest::test_menu_item_parent_cycle_is_rejected` | Setting a descendant as parent ⇒ error on `parent_id`. |

---

## 3. Diagnostic module (`§5.3`)

### 3.1 Public flow

| Method | Path | Name | Auth | Returns | Behaviour |
|---|---|---|---|---|---|
| GET | `/diagnostic` | `diagnostic.index` | guest | `public/Diagnostic/Index` OR redirect | Picker; **auto-redirects to `diagnostic.show` when exactly one questionnaire is active**. |
| GET | `/diagnostic/{questionnaire:slug}` | `diagnostic.show` | guest | `public/Diagnostic/Run` | 404 if `is_active=false`. Loads `questions.answerOptions` ordered by `position`. |
| POST | `/diagnostic/{questionnaire:slug}/submit` | `diagnostic.submit` | guest | `public/Diagnostic/Result` | See below. |
| GET | `/diagnostic/history` | `diagnostic.history` | **auth** | `public/Diagnostic/History` | Paginated, current-user-only. |
| GET | `/diagnostic/history/{diagnostic}` | `diagnostic.history.show` | **auth** | `public/Diagnostic/HistoryShow` | 403 if `diagnostic.user_id !== current_user.id`. |

**Submission payload**: `{ "answers": { "<question_id>": <answer_option_id>, ... } }`.

**Submit validation (`SubmitDiagnosticRequest`)**:
- 404 if questionnaire is inactive (via `authorize()`).
- `answers` is a required array.
- Each required question must have an answer.
- Each `(question_id, answer_option_id)` pair must be consistent (option belongs to question, question belongs to questionnaire).

**Persistence**:
- **Auth user**: `Diagnostic` row created with `score_total`, `result_interpretation_id`, `completed_at`; one `DiagnosticResponse` per answer with the **score snapshot** (per schema doc). All in one transaction.
- **Anonymous**: no DB write. Result returned as Inertia props (`score`, `interpretation`, `saved: false`).

| Test | What it proves |
|---|---|
| `PublicDiagnosticTest::test_index_redirects_when_only_one_active_questionnaire` | `/diagnostic` ⇒ 302 to `diagnostic.show` when N=1. |
| `PublicDiagnosticTest::test_show_works_for_active_questionnaire` | Active questionnaire GET ⇒ 200. |
| `PublicDiagnosticTest::test_show_returns_404_for_inactive_questionnaire` | Inactive ⇒ 404. |
| `PublicDiagnosticTest::test_anonymous_submission_returns_result_without_db_write` | POST as guest ⇒ 200 + `diagnostics` table stays empty. |
| `PublicDiagnosticTest::test_authenticated_submission_persists_diagnostic_and_responses` | Auth POST creates `Diagnostic` with the right score + `DiagnosticResponse` with snapshot score. |
| `PublicDiagnosticTest::test_missing_required_answer_is_rejected` | Empty `answers` ⇒ validation errors. |
| `PublicDiagnosticTest::test_option_from_another_question_is_rejected` | Cross-question option ⇒ error on `answers.{questionId}`. |
| `PublicDiagnosticTest::test_history_index_requires_auth` | Guest hitting `/diagnostic/history` ⇒ 302 to `/login`. |
| `PublicDiagnosticTest::test_user_sees_only_their_own_history` | Index works for the owner; detail page 403s for someone else's diagnostic. |

### 3.2 `DiagnosticScoringService` (`App\Services\DiagnosticScoringService`)

Pure service used by the submit controller. Validates ownership chain, sums scores, looks up interpretation.

| Test | What it proves |
|---|---|
| `DiagnosticScoringServiceTest::test_sums_scores_across_answers` | `total` = sum of every answered option's `score`. |
| `DiagnosticScoringServiceTest::test_finds_matching_interpretation` | Returns the `ResultInterpretation` whose `[min,max]` brackets the total. |
| `DiagnosticScoringServiceTest::test_boundary_scores_match_inclusive_ranges` | `min_score <= score <= max_score` is inclusive on both ends. |
| `DiagnosticScoringServiceTest::test_rejects_option_from_a_different_question` | Throws `InvalidArgumentException` if an option's `question_id` mismatches the keyed question. |
| `DiagnosticScoringServiceTest::test_rejects_option_from_a_different_questionnaire` | Throws if the option belongs to a foreign questionnaire. |

### 3.3 Admin: Questionnaires → Questions → Answer Options

Routes (nested, scope-bound):
```
/admin/questionnaires                                                                 # CRUD
/admin/questionnaires/{q}/questions                                                   # nested
/admin/questionnaires/{q}/questions/{question}/answer-options                         # doubly-nested
/admin/questionnaires/{q}/interpretations                                             # ranges
```

Key validation rules:
- **Questionnaire**: title, slug (auto-gen + unique), description, instructions, is_active. `created_by` set on store.
- **Question**: text (required), description, position, is_required.
- **AnswerOption**: label, score (int), position. Param name is `answerOption` so scope-binding can resolve `$question->answerOptions()`.
- **ResultInterpretation**: min_score ≥ 0, max_score `gte:min_score`, title, description, optional recommendations + color. **No overlap** with sibling ranges on the same questionnaire (self-excluded on update).

| Test | What it proves |
|---|---|
| `QuestionnaireAdminTest::test_regular_user_cannot_access_questionnaires_admin` | 403 for `role=user`. |
| `QuestionnaireAdminTest::test_admin_can_create_a_questionnaire` | POST stores row; `created_by` = current admin; slug auto-gen. |
| `QuestionnaireAdminTest::test_admin_can_nest_question_under_questionnaire` | Nested POST creates question with correct `questionnaire_id`. |
| `QuestionnaireAdminTest::test_admin_can_nest_option_under_question` | Doubly-nested POST creates option. |
| `QuestionnaireAdminTest::test_scope_bindings_reject_mismatched_question_or_option` | Wrong nesting ⇒ 404 (not 200 with foreign data). |
| `QuestionnaireAdminTest::test_interpretation_overlap_is_rejected_on_store` | Overlapping `[min,max]` for the same questionnaire ⇒ validation error. |
| `QuestionnaireAdminTest::test_interpretation_overlap_allows_editing_the_same_row` | Updating the existing range to `[0,12]` while `[0,10]` is in DB is allowed (self-excluded). |
| `QuestionnaireAdminTest::test_min_greater_than_max_is_rejected` | `min > max` ⇒ error on `max_score`. |

---

## 4. Account management (`§5.1` + `§5.4`)

### 4.1 Self-service (Fortify + Settings, scaffolded)

| Method | Path | Name | Behaviour |
|---|---|---|---|
| GET | `/settings/profile` | `profile.edit` | Render profile form. |
| PATCH | `/settings/profile` | `profile.update` | Update name/email; resets `email_verified_at` if email changed. |
| DELETE | `/settings/profile` | `profile.destroy` | Requires `password`. Logs out, hard-deletes the user. **All user-owned rows cascade away.** |
| GET | `/settings/security` | `security.edit` | 2FA setup + password update entry. |
| PUT | `/settings/password` | `user-password.update` | Throttled (6/min). |

| Test | What it proves |
|---|---|
| `ProfileUpdateTest::test_profile_page_is_displayed` | 200 for auth users. |
| `ProfileUpdateTest::test_profile_information_can_be_updated` | PATCH updates name/email. |
| `ProfileUpdateTest::test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged` | No-op when email doesn't change. |
| `ProfileUpdateTest::test_user_can_delete_their_account` | DELETE + correct password ⇒ user gone. |
| `ProfileUpdateTest::test_correct_password_must_be_provided_to_delete_account` | Wrong password ⇒ user not deleted. |
| `SecurityTest::test_security_page_is_displayed` | 200 for auth users. |
| `SecurityTest::test_security_page_requires_password_confirmation_when_enabled` | Hits `password.confirm` when feature flag is on. |
| `SecurityTest::test_security_page_does_not_require_password_confirmation_when_disabled` | Skips confirmation when feature flag is off. |
| `SecurityTest::test_security_page_renders_without_two_factor_when_feature_is_disabled` | Page degrades gracefully without 2FA. |
| `SecurityTest::test_password_can_be_updated` | PUT `/settings/password` updates password. |
| `SecurityTest::test_correct_password_must_be_provided_to_update_password` | Wrong current password ⇒ rejected. |
| `UserAdminTest::test_self_deletion_via_settings_cascades` | **RGPD check**: Fortify's profile-destroy path also cascades user-owned rows (diagnostics, etc.). |

### 4.2 Admin user management

| Method | Path | Name | Behaviour |
|---|---|---|---|
| GET | `/admin/users` | `admin.users.index` | Paginated 20. Admin sees `role=user` only; super-admin sees all. Filterable by `?role=...` and `?active=0|1`. |
| GET | `/admin/users/create` | `admin.users.create` | Returns `roleOptions` gated by actor's role. |
| POST | `/admin/users` | `admin.users.store` | `StoreUserRequest`: name, email (unique), password (confirmed, default rules), `role` (admin ⇒ `{user}`; super-admin ⇒ `{user, admin}`; never `super_admin`), `is_active`. Sets `email_verified_at=now()`. |
| GET | `/admin/users/{user}` | `admin.users.show` | `view` policy: admin can view `role=user`; super-admin can view anyone. |
| DELETE | `/admin/users/{user}` | `admin.users.destroy` | `delete` policy: same matrix as `update`. **Cascade**: diagnostics, contents, audit_logs, questionnaires all hard-delete. |
| PATCH | `/admin/users/{user}/toggle-active` | `admin.users.toggle-active` | Flips `is_active`. Self-target denied. |
| PATCH | `/admin/users/{user}/role` | `admin.users.change-role` | Super-admin only; `role` ∈ `{user, admin}`; never to/from super-admin; not self. Policy denies before validation. |

| Test | What it proves |
|---|---|
| `UserAdminTest::test_regular_user_cannot_access_user_admin` | 403 for `role=user`. |
| `UserAdminTest::test_admin_index_only_shows_regular_users` | Index for admin returns total = (# of `role=user`). |
| `UserAdminTest::test_super_admin_index_shows_everyone` | Super-admin's index includes all 4 roles. |
| `UserAdminTest::test_admin_cannot_view_an_admin_account` | Admin GET show on another admin ⇒ 403. |
| `UserAdminTest::test_super_admin_can_view_any_account` | Super-admin can view admins. |
| `UserAdminTest::test_admin_creates_a_user_account` | POST with `role=user` works. |
| `UserAdminTest::test_admin_cannot_create_an_admin_account` | POST with `role=admin` ⇒ validation error on `role` (allowed list excludes admin for non-super). |
| `UserAdminTest::test_super_admin_can_create_an_admin_account` | Super-admin can create admins. |
| `UserAdminTest::test_nobody_can_create_a_super_admin_via_the_admin` | `role=super_admin` is never in the allowed list. |
| `UserAdminTest::test_admin_can_toggle_a_regular_users_active_flag` | PATCH toggle-active flips for a regular user. |
| `UserAdminTest::test_admin_cannot_toggle_another_admin` | Admin → admin toggle ⇒ 403. |
| `UserAdminTest::test_admin_cannot_toggle_themselves` | Self-target ⇒ 403. |
| `UserAdminTest::test_super_admin_can_change_a_user_role_to_admin` | PATCH role works for super-admin. |
| `UserAdminTest::test_admin_cannot_change_roles` | Admin attempting role change ⇒ 403. |
| `UserAdminTest::test_role_change_to_super_admin_is_rejected` | Even super-admin can't promote anyone to `super_admin` (403 from policy). |
| `UserAdminTest::test_hard_delete_cascades_user_data` | DELETE removes the user **and** all their `diagnostics` rows. |
| `UserAdminTest::test_admin_cannot_delete_themselves` | Self DELETE ⇒ 403. |

---

## 5. Health / Misc

| Method | Path | Behaviour |
|---|---|---|
| GET | `/up` | Laravel health endpoint (returns 200). |
| GET | `/` | Welcome page (`Welcome` Inertia component); passes `canRegister`. |
| GET | `/dashboard` | Auth+verified-only landing for logged-in users. |

---

## 6. Frontend API map (cheat sheet)

What each Vue page (to come in step 07) will need to call:

| Vue page (planned) | HTTP calls | Wayfinder action |
|---|---|---|
| `Welcome` | — | none (already exists) |
| `public/Information/Index` | GET `informations.index` | `routes/informations.index` |
| `public/Information/Category` | GET `informations.category` | `routes/informations.category` |
| `public/Information/Content` | GET `informations.content` | `routes/informations.content` |
| `public/Pages/Show` | GET `pages.show` | `routes/pages.show` |
| `public/Diagnostic/Index` | GET `diagnostic.index` | |
| `public/Diagnostic/Run` | GET `diagnostic.show` + POST `diagnostic.submit` | `routes/diagnostic.show`, `routes/diagnostic.submit` (form variant) |
| `public/Diagnostic/Result` | (rendered as the response of submit) | n/a — props only |
| `public/Diagnostic/History` | GET `diagnostic.history` | |
| `public/Diagnostic/HistoryShow` | GET `diagnostic.history.show` | |
| `Dashboard` | — | already exists |
| `settings/Profile` | PATCH `profile.update`, DELETE `profile.destroy` | (existing) |
| `settings/Security` | PUT `user-password.update`, plus Fortify 2FA endpoints | (existing) |
| `admin/Dashboard` | — | placeholder; could surface counts |
| `admin/categories/Index` | GET `admin.categories.index` | |
| `admin/categories/Create` | POST `admin.categories.store` | form |
| `admin/categories/Edit` | PUT `admin.categories.update`, DELETE `admin.categories.destroy`, PATCH `admin.categories.toggle-active` | form + actions |
| `admin/contents/Index/Create/Edit` | corresponding `admin.contents.*` | form variants |
| `admin/menus/Index/Create/Edit` | `admin.menus.*` | |
| `admin/menu-items/Create/Edit` (under a menu) | `admin.menus.items.*` (nested) | |
| `admin/questionnaires/Index/Create/Edit` | `admin.questionnaires.*` | |
| `admin/questions/Create/Edit` | `admin.questionnaires.questions.*` (nested) | |
| `admin/answer-options/Create/Edit` | `admin.questionnaires.questions.answer-options.*` (doubly-nested) | |
| `admin/interpretations/Create/Edit` | `admin.questionnaires.interpretations.*` (nested) | |
| `admin/users/Index/Show/Create` | `admin.users.*` + `toggle-active` + `change-role` | |

When you build a Vue form, import the form variant of the route from `@/actions/...` (Wayfinder) and bind it to the Inertia `<Form>` or `useForm`. The server-side validation rules listed in this doc are exactly what the form will surface as field errors.

---
