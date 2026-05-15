# CESIZen Database Schema

## Entity Relationship Diagram

```mermaid
erDiagram
    %% ===== USER MANAGEMENT =====
    users {
        bigint id PK
        string name
        string email UK
        timestamp email_verified_at
        string password
        enum role "user, admin, super_admin"
        boolean is_active
        text two_factor_secret
        text two_factor_recovery_codes
        timestamp two_factor_confirmed_at
        timestamp created_at
        timestamp updated_at
    }

    %% ===== CONTENT MANAGEMENT =====
    categories {
        bigint id PK
        string name
        string slug UK
        text description
        bigint parent_id FK
        int position
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    contents {
        bigint id PK
        bigint category_id FK
        string title
        string slug UK
        text excerpt
        longtext body
        enum type "page, article, resource"
        boolean is_published
        timestamp published_at
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
    }

    menus {
        bigint id PK
        string name
        enum location "main, footer, sidebar"
        timestamp created_at
        timestamp updated_at
    }

    menu_items {
        bigint id PK
        bigint menu_id FK
        bigint parent_id FK
        string title
        string url
        bigint content_id FK
        int position
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    %% ===== DIAGNOSTIC MODULE =====
    questionnaires {
        bigint id PK
        string title
        string slug UK
        text description
        text instructions
        boolean is_active
        bigint created_by FK
        timestamp created_at
        timestamp updated_at
    }

    questions {
        bigint id PK
        bigint questionnaire_id FK
        text text
        text description
        int position
        boolean is_required
        timestamp created_at
        timestamp updated_at
    }

    answer_options {
        bigint id PK
        bigint question_id FK
        string label
        int score
        int position
        timestamp created_at
        timestamp updated_at
    }

    result_interpretations {
        bigint id PK
        bigint questionnaire_id FK
        int min_score
        int max_score
        string title
        text description
        text recommendations
        string color
        timestamp created_at
        timestamp updated_at
    }

    diagnostics {
        bigint id PK
        bigint user_id FK
        bigint questionnaire_id FK
        int score_total
        bigint result_interpretation_id FK
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }

    diagnostic_responses {
        bigint id PK
        bigint diagnostic_id FK
        bigint question_id FK
        bigint answer_option_id FK
        int score
        timestamp created_at
    }

    %% ===== AUDIT & SECURITY =====
    audit_logs {
        bigint id PK
        bigint user_id FK
        string action
        string auditable_type
        bigint auditable_id
        json old_values
        json new_values
        string ip_address
        text user_agent
        timestamp created_at
    }

    %% ===== RELATIONSHIPS =====

    %% User relationships
    users ||--o{ diagnostics : "completes"
    users ||--o{ contents : "creates"
    users ||--o{ questionnaires : "creates"
    users ||--o{ audit_logs : "generates"

    %% Category relationships
    categories ||--o{ categories : "has children"
    categories ||--o{ contents : "contains"

    %% Content relationships
    contents ||--o{ menu_items : "linked from"

    %% Menu relationships
    menus ||--o{ menu_items : "contains"
    menu_items ||--o{ menu_items : "has children"

    %% Questionnaire relationships
    questionnaires ||--o{ questions : "contains"
    questionnaires ||--o{ result_interpretations : "defines"
    questionnaires ||--o{ diagnostics : "used in"

    %% Question relationships
    questions ||--o{ answer_options : "has"
    questions ||--o{ diagnostic_responses : "answered in"

    %% Diagnostic relationships
    diagnostics ||--o{ diagnostic_responses : "contains"
    diagnostics }o--|| result_interpretations : "interpreted as"

    %% Answer relationships
    answer_options ||--o{ diagnostic_responses : "selected in"
```

## Simplified View by Module

### User Management
```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        string password
        enum role "user, admin, super_admin"
        boolean is_active
    }

    audit_logs {
        bigint id PK
        bigint user_id FK
        string action
        string auditable_type
        json old_values
        json new_values
    }

    users ||--o{ audit_logs : "generates"
```

### Content Management
```mermaid
erDiagram
    categories {
        bigint id PK
        string name
        string slug UK
        bigint parent_id FK
    }

    contents {
        bigint id PK
        bigint category_id FK
        string title
        string slug UK
        longtext body
        boolean is_published
    }

    menus {
        bigint id PK
        string name
        enum location "main, footer, sidebar"
    }

    menu_items {
        bigint id PK
        bigint menu_id FK
        bigint parent_id FK
        string title
        bigint content_id FK
    }

    categories ||--o{ categories : "parent"
    categories ||--o{ contents : "contains"
    menus ||--o{ menu_items : "contains"
    menu_items ||--o{ menu_items : "parent"
    contents ||--o{ menu_items : "linked"
```

### Diagnostic Module (Core Feature)
```mermaid
erDiagram
    questionnaires {
        bigint id PK
        string title
        text description
        boolean is_active
    }

    questions {
        bigint id PK
        bigint questionnaire_id FK
        text text
        int position
    }

    answer_options {
        bigint id PK
        bigint question_id FK
        string label
        int score
    }

    result_interpretations {
        bigint id PK
        bigint questionnaire_id FK
        int min_score
        int max_score
        string title
        text recommendations
    }

    diagnostics {
        bigint id PK
        bigint user_id FK
        bigint questionnaire_id FK
        int score_total
    }

    diagnostic_responses {
        bigint id PK
        bigint diagnostic_id FK
        bigint question_id FK
        bigint answer_option_id FK
        int score
    }

    questionnaires ||--o{ questions : "contains"
    questionnaires ||--o{ result_interpretations : "defines"
    questionnaires ||--o{ diagnostics : "used in"
    questions ||--o{ answer_options : "has"
    questions ||--o{ diagnostic_responses : "answered"
    answer_options ||--o{ diagnostic_responses : "selected"
    diagnostics ||--o{ diagnostic_responses : "contains"
    diagnostics }o--|| result_interpretations : "result"
```

## Example Data Flow

### How a Diagnostic Works

```mermaid
sequenceDiagram
    participant U as User
    participant Q as Questionnaire
    participant Qs as Questions
    participant AO as Answer Options
    participant D as Diagnostic
    participant DR as Diagnostic Responses
    participant RI as Result Interpretation

    U->>Q: Start questionnaire
    Q->>Qs: Load questions (ordered by position)
    Qs->>AO: Load answer options for each question

    loop For each question
        U->>AO: Select an answer
        AO->>DR: Store response with score
    end

    DR->>D: Calculate total score
    D->>RI: Find matching score range
    RI->>U: Display result & recommendations
```

## Tables Summary

| Module | Table | Purpose |
|--------|-------|---------|
| **Users** | `users` | User accounts with roles |
| **Users** | `audit_logs` | Security logging |
| **Content** | `categories` | Content organization |
| **Content** | `contents` | Information pages |
| **Content** | `menus` | Navigation menus |
| **Content** | `menu_items` | Menu entries |
| **Diagnostic** | `questionnaires` | Stress questionnaires |
| **Diagnostic** | `questions` | Questions in questionnaire |
| **Diagnostic** | `answer_options` | Possible answers with scores |
| **Diagnostic** | `result_interpretations` | Score range meanings |
| **Diagnostic** | `diagnostics` | Completed questionnaires |
| **Diagnostic** | `diagnostic_responses` | User's answers |

## Key Improvements Over Original Schema

1. **Proper scoring system**: Questions have multiple `answer_options`, each with its own score
2. **Response tracking**: `diagnostic_responses` stores every answer the user gave
3. **Result configuration**: `result_interpretations` allows admins to define what score ranges mean
4. **Content hierarchy**: `categories` with `parent_id` for nested organization
5. **Navigation**: `menus` and `menu_items` for configurable navigation
6. **Audit trail**: `audit_logs` for RGPD compliance and security
7. **Soft delete**: `is_active` flags instead of hard deletes

## Foreign Key Cascade Rules

Cascade rules are aligned with the RGPD "right to be forgotten" decision: when a `users` row is deleted, every row that references it is hard-deleted too. Within a questionnaire, the question/option/result hierarchy cascades on parent deletion.

| Child table → parent | On parent DELETE | Reason |
|---|---|---|
| `categories.parent_id` → `categories.id` | SET NULL | Detach children, don't lose them. |
| `contents.category_id` → `categories.id` | SET NULL | Content survives category deletion. |
| `contents.created_by` → `users.id` | **CASCADE** | RGPD erasure — user's authored content is removed. |
| `menu_items.menu_id` → `menus.id` | CASCADE | A menu's items are meaningless without the menu. |
| `menu_items.parent_id` → `menu_items.id` | SET NULL | Detach children. |
| `menu_items.content_id` → `contents.id` | SET NULL | Menu item becomes a dead link, but survives. |
| `questionnaires.created_by` → `users.id` | **CASCADE** | RGPD erasure. |
| `questions.questionnaire_id` → `questionnaires.id` | CASCADE | Questions belong to one questionnaire. |
| `answer_options.question_id` → `questions.id` | CASCADE | Options belong to one question. |
| `result_interpretations.questionnaire_id` → `questionnaires.id` | CASCADE | Interpretations belong to one questionnaire. |
| `diagnostics.user_id` → `users.id` | **CASCADE** | RGPD erasure. `user_id` is NOT NULL because anonymous visitor diagnostics are never persisted. |
| `diagnostics.questionnaire_id` → `questionnaires.id` | CASCADE | If the questionnaire goes, its diagnostics go. |
| `diagnostics.result_interpretation_id` → `result_interpretations.id` | SET NULL | Keep the diagnostic + score even if the interpretation row is replaced. |
| `diagnostic_responses.diagnostic_id` → `diagnostics.id` | CASCADE | |
| `diagnostic_responses.question_id` → `questions.id` | CASCADE | |
| `diagnostic_responses.answer_option_id` → `answer_options.id` | CASCADE | |
| `audit_logs.user_id` → `users.id` | **CASCADE** | RGPD erasure. `user_id` stays nullable to allow logging failed logins where the user is unknown. |

## Deactivation Policy (`is_active`)

The schema deliberately avoids `SoftDeletes` (`deleted_at`) — the single visibility lever is `is_active`. Hard delete is reserved for super-admin cleanup tooling. Per-table semantics:

| Table | Public view (visitor / user) | Admin view |
|---|---|---|
| `users` | n/a (no public listing) | Inactive users are listed but cannot log in (`EnsureUserIsActive` middleware). Hard delete: super-admin only. |
| `categories` | Hidden when `is_active = false`. Descendants follow the parent's visibility. | All visible; toggle is a dedicated admin action. |
| `contents` | Hidden when `is_active = false` OR `is_published = false` OR `published_at` is in the future. | All visible. Publishing is separate from activation. |
| `menus` | Always rendered if a public renderer asks for `location`. (No `is_active` column.) | Manage `menu_items` directly. |
| `menu_items` | Hidden when `is_active = false`. Children of an inactive item are also hidden. | All visible. |
| `questionnaires` | Only `is_active = true` shows up in the public diagnostic picker. | All visible. |
| `questions` / `answer_options` / `result_interpretations` | No `is_active` flag — visibility follows the parent questionnaire. | Same. |

## Migration ↔ Table Mapping

| Migration file | Table created / modified |
|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2025_08_14_170933_add_two_factor_columns_to_users_table` | `users` (+3 columns) |
| `2026_03_25_120813_create_categories_table` | `categories` |
| `2026_03_25_120818_add_role_and_is_active_to_users_table` | `users` (+2 columns) |
| `2026_03_25_120822_create_contents_table` | `contents` |
| `2026_03_25_120824_create_menus_table` | `menus` |
| `2026_03_25_120840_create_menu_items_table` | `menu_items` |
| `2026_03_25_120842_create_questionnaires_table` | `questionnaires` |
| `2026_03_25_120845_create_questions_table` | `questions` |
| `2026_03_25_120848_create_answer_options_table` | `answer_options` |
| `2026_03_25_120859_create_result_interpretations_table` | `result_interpretations` |
| `2026_03_25_120903_create_diagnostics_table` | `diagnostics` |
| `2026_03_25_120907_create_diagnostic_responses_table` | `diagnostic_responses` |
| `2026_03_25_120910_create_audit_logs_table` | `audit_logs` |
