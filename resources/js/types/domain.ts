/**
 * Public-facing payload types — mirror Eloquent serializations sent through
 * Inertia. Keep these in sync with the controllers under
 * `app/Http/Controllers/Information` and the shared payload in
 * `App\Http\Middleware\HandleInertiaRequests::navigationPayload`.
 */

export type MenuItemPayload = {
    id: number;
    title: string;
    url: string | null;
    children: MenuItemPayload[];
};

export type NavigationPayload = {
    main: MenuItemPayload[];
    footer: MenuItemPayload[];
};

export type ContentType = 'page' | 'article' | 'resource';

export type CategorySummary = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    parent_id: number | null;
    is_active: boolean;
    position: number;
    children?: CategorySummary[];
    contents?: ContentSummary[];
};

export type ContentSummary = {
    id: number;
    category_id: number | null;
    title: string;
    slug: string;
    excerpt: string | null;
    type: ContentType;
    is_published: boolean;
    published_at: string | null;
};

export type ContentDetail = ContentSummary & {
    body: string;
    body_html?: string;
    category?: CategorySummary | null;
    author?: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
};

/* -------------------------------------------------------------------------
 * Information module — admin payloads
 * ---------------------------------------------------------------------- */

/** Generic `{ id, name }` option used by category/content selects. */
export type IdNameOption = { id: number; name: string };

/** Generic `{ value, label }` option (content types, etc.). */
export type SelectOption = { value: string; label: string };

/** Category row in the admin listing (parent + content count). */
export type CategoryRow = CategorySummary & {
    parent?: { id: number; name: string } | null;
    contents_count?: number;
};

/** Content row in the admin listing (category + author). */
export type ContentRow = ContentSummary & {
    category?: { id: number; name: string; slug: string } | null;
    author?: { id: number; name: string } | null;
    updated_at?: string;
};

export type MenuLocation = 'main' | 'footer' | 'sidebar';

export type MenuItem = {
    id: number;
    menu_id: number;
    parent_id: number | null;
    title: string;
    url: string | null;
    content_id: number | null;
    position: number;
    is_active: boolean;
    content?: { id: number; title: string } | null;
};

export type Menu = {
    id: number;
    name: string;
    location: MenuLocation;
    items_count?: number;
    items?: MenuItem[];
};

/* -------------------------------------------------------------------------
 * Diagnostic module
 * ---------------------------------------------------------------------- */

export type AnswerOption = {
    id: number;
    question_id: number;
    label: string;
    score: number;
    position: number;
};

export type Question = {
    id: number;
    questionnaire_id: number;
    text: string;
    position: number;
    is_required: boolean;
    answer_options?: AnswerOption[];
};

export type ResultInterpretation = {
    id: number;
    questionnaire_id: number;
    min_score: number;
    max_score: number;
    title: string;
    description: string;
    recommendations: string | null;
    color: string | null;
};

export type Questionnaire = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    instructions: string | null;
    is_active: boolean;
    created_by: number | null;
    questions?: Question[];
    interpretations?: ResultInterpretation[];
    questions_count?: number;
    interpretations_count?: number;
    diagnostics_count?: number;
};

export type DiagnosticResponse = {
    id: number;
    diagnostic_id: number;
    question_id: number;
    answer_option_id: number;
    score: number;
    question?: Pick<Question, 'id' | 'text'>;
    answer_option?: Pick<AnswerOption, 'id' | 'label' | 'score'>;
};

export type Diagnostic = {
    id: number;
    user_id: number;
    questionnaire_id: number;
    score_total: number;
    result_interpretation_id: number | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
    questionnaire?: Pick<Questionnaire, 'id' | 'title' | 'slug'>;
    result_interpretation?: Pick<
        ResultInterpretation,
        'id' | 'title' | 'color'
    > | null;
    resultInterpretation?: ResultInterpretation | null;
    responses?: DiagnosticResponse[];
};

/* -------------------------------------------------------------------------
 * Account management
 * ---------------------------------------------------------------------- */

export type RoleOption = {
    value: string;
    label: string;
};

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    role: string;
    is_active: boolean;
    diagnostics_count?: number;
    created_at?: string;
    email_verified_at?: string | null;
};

export type AuditLogEntry = {
    id: number;
    action: string;
    auditable_type: string | null;
    auditable_id: number | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
};

/* -------------------------------------------------------------------------
 * Laravel paginator shape
 * ---------------------------------------------------------------------- */

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    first_page_url: string | null;
    last_page_url: string | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    path: string;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};
