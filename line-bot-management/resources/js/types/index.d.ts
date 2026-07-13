export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export interface Flash {
    success: string | null;
    error: string | null;
}

export interface PageProps {
    auth: { user: AuthUser | null };
    flash: Flash;
    appName: string;
    [key: string]: unknown;
}

/** A single numbered paginator link. */
export interface PaginatedLink {
    url: string | null;
    label: string;
    active: boolean;
}

/**
 * Shape produced by an API Resource collection wrapping a LengthAwarePaginator:
 * `{ data, meta: { ...page info..., links } }`.
 */
export interface Paginated<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: PaginatedLink[];
    };
}

/** Laravel validation errors keyed by field name. */
export type FormErrors = Record<string, string>;

export interface PackageCategory {
    id: number;
    name_th: string;
    name_en: string | null;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    packages_count?: number;
}

export interface ServicePackage {
    id: number;
    category_id: number | null;
    category?: Pick<PackageCategory, 'id' | 'name_th'>;
    code: string | null;
    name_th: string;
    name_en: string | null;
    description_th: string | null;
    description_en: string | null;
    price: number | null;
    sale_price: number | null;
    currency: string;
    duration_minutes: number | null;
    terms: string | null;
    keywords: string | null;
    is_active: boolean;
    is_published: boolean;
    effective_from: string | null;
    effective_until: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface Faq {
    id: number;
    question_th: string;
    answer_th: string;
    question_en: string | null;
    answer_en: string | null;
    category: string | null;
    tags: string | null;
    is_active: boolean;
    created_at: string | null;
    updated_at: string | null;
}

export interface KnowledgeEntry {
    id: number;
    title: string;
    body: string;
    type: string;
    category: string | null;
    tags: string | null;
    source_url: string | null;
    version: number;
    is_active: boolean;
    reviewed_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}
