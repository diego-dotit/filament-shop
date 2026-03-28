// composables/useSlug.ts
// Provides a pure, synchronous helper that resolves the correct slug string
// from an entity's multi-language slug records.
//
// Usage:
//   const slug = useSlug(product, 'es')
//   const slug = useSlug(category, 'de', 'en')
//
// ---------------------------------------------------------------------------
// WHEN TO USE useSlug vs. entity.slug directly
// ---------------------------------------------------------------------------
//
// ┌────────────────────────────────────────────────────────────────────────┐
// │ Use useSlug()                  │ Use entity.slug directly              │
// ├────────────────────────────────────────────────────────────────────────┤
// │ Phase 3+ entities that expose  │ Current API responses where the       │
// │ a full `slugs: SlugRecord[]`   │ backend already returns a single,     │
// │ relationship (multi-locale     │ pre-localised `slug: string` field.   │
// │ slug resolution at the         │ Examples: ProductResource,            │
// │ front-end layer).              │ CategoryResource from /api/products   │
// │ Examples: future buildUrl()    │ and /api/categories in Phase 1–2.     │
// │ callers, CategoryChip.vue      │                                       │
// │ (forwards-compat pattern).     │                                       │
// └────────────────────────────────────────────────────────────────────────┘
//
// Recommended forward-compatible pattern (works in both phases):
//   const slug = useSlug(entity, locale) ?? entity.slug;
//
// This transparently uses the slugs array when available (Phase 3+) and
// falls back to the API-provided flat slug today (Phase 1–2). This pattern
// is already used by CategoryChip.vue.
//
// NOTE: useSlug() returns null when the entity has no `slugs` array. It
// never reads the flat `slug` string field. The caller is responsible for
// the fallback.

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

/** A single locale/slug pair as stored in the slugs relationship. */
export interface SlugRecord {
    locale: string;
    slug: string;
}

/** Minimum shape an entity must satisfy to be passed to useSlug(). */
interface SlugBearingEntity {
    id: number;
    slugs?: SlugRecord[];
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

/**
 * Resolves a slug string for the given locale from an entity's slugs array.
 *
 * Resolution order:
 *  1. Exact match for `locale`
 *  2. Exact match for `defaultLocale` (falls back to 'en' when not provided)
 *  3. null — no matching slug found
 *
 * @param entity        - Any entity that carries a `slugs` array (product, category, brand, …)
 * @param locale        - The desired locale (e.g. 'es', 'de', 'fr')
 * @param defaultLocale - Fallback locale; defaults to 'en'
 * @returns The resolved slug string, or null if none could be found
 *
 * @see The file-level comment above for guidance on when to call useSlug()
 *      versus reading `entity.slug` directly from the API response.
 */
export function useSlug(
    entity: SlugBearingEntity,
    locale: string,
    defaultLocale = "en"
): string | null {
    const slugs = entity.slugs ?? [];

    // 1. Try the requested locale first
    const match = slugs.find((s) => s.locale === locale);
    if (match) return match.slug;

    // 2. Fall back to the default locale
    const fallback = slugs.find((s) => s.locale === defaultLocale);
    if (fallback) return fallback.slug;

    // 3. Nothing found
    return null;
}
