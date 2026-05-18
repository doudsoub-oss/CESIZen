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
    category?: CategorySummary | null;
    author?: {
        id: number;
        name: string;
    } | null;
    created_at: string;
    updated_at: string;
};
