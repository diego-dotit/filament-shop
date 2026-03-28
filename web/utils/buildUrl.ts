// utils/buildUrl.ts
// Pure utility functions for building language-aware URL strings from entity
// slug records. Each function uses useSlug() to resolve a slug for the
// requested locale (with fallback to defaultLocale). Returns null when any
// required segment has no resolvable slug — ID-based fallback URLs must never
// be generated because they are not recognised by the backend slug router.
//
// Usage:
//   const url = buildProductUrl(category, subcategory, product, 'es')
//   // → '/filamentos-pla/1-75mm-es/pla-rojo'  or  null

import { useSlug } from "~/composables/useSlug";
import type { SlugRecord } from "~/composables/useSlug";

// ---------------------------------------------------------------------------
// Shared entity shape
// ---------------------------------------------------------------------------

/** Minimum shape required by every URL builder. */
export interface SlugBearingEntity {
    id: number;
    slugs: SlugRecord[];
}

// ---------------------------------------------------------------------------
// Internal helper
// ---------------------------------------------------------------------------

/**
 * Resolves a single slug segment using useSlug().
 * Returns the slug string, or null when neither locale resolves.
 */
function resolveSegment(
    entity: SlugBearingEntity,
    locale: string,
    defaultLocale: string
): string | null {
    return useSlug(entity, locale, defaultLocale);
}

// ---------------------------------------------------------------------------
// buildProductUrl
// ---------------------------------------------------------------------------

/**
 * Builds a 3-segment product URL: /{category}/{subcategory}/{product}
 *
 * @param category      - Category entity with slugs array
 * @param subcategory   - Subcategory entity with slugs array
 * @param product       - Product entity with slugs array
 * @param locale        - Desired locale (e.g. 'es', 'de')
 * @param defaultLocale - Fallback locale; defaults to 'en'
 * @returns URL string or null when any segment cannot be resolved
 */
export function buildProductUrl(
    category: SlugBearingEntity,
    subcategory: SlugBearingEntity,
    product: SlugBearingEntity,
    locale: string,
    defaultLocale = "en"
): string | null {
    const catSlug = resolveSegment(category, locale, defaultLocale);
    if (!catSlug) return null;

    const subcatSlug = resolveSegment(subcategory, locale, defaultLocale);
    if (!subcatSlug) return null;

    const productSlug = resolveSegment(product, locale, defaultLocale);
    if (!productSlug) return null;

    return `/${catSlug}/${subcatSlug}/${productSlug}`;
}

// ---------------------------------------------------------------------------
// buildCategoryUrl
// ---------------------------------------------------------------------------

/**
 * Builds a 2-segment category URL: /{category}/{subcategory}
 *
 * @param category      - Category entity with slugs array
 * @param subcategory   - Subcategory entity with slugs array
 * @param locale        - Desired locale
 * @param defaultLocale - Fallback locale; defaults to 'en'
 * @returns URL string or null when any segment cannot be resolved
 */
export function buildCategoryUrl(
    category: SlugBearingEntity,
    subcategory: SlugBearingEntity,
    locale: string,
    defaultLocale = "en"
): string | null {
    const catSlug = resolveSegment(category, locale, defaultLocale);
    if (!catSlug) return null;

    const subcatSlug = resolveSegment(subcategory, locale, defaultLocale);
    if (!subcatSlug) return null;

    return `/${catSlug}/${subcatSlug}`;
}

// ---------------------------------------------------------------------------
// buildBrandUrl
// ---------------------------------------------------------------------------

/**
 * Builds a brand URL: /brands/{slug}
 *
 * @param brand         - Brand entity with slugs array
 * @param locale        - Desired locale
 * @param defaultLocale - Fallback locale; defaults to 'en'
 * @returns URL string or null when the slug cannot be resolved
 */
export function buildBrandUrl(
    brand: SlugBearingEntity,
    locale: string,
    defaultLocale = "en"
): string | null {
    const slug = resolveSegment(brand, locale, defaultLocale);
    if (!slug) return null;

    return `/brands/${slug}`;
}

// ---------------------------------------------------------------------------
// buildTagUrl
// ---------------------------------------------------------------------------

/**
 * Builds a tag URL: /tags/{slug}
 *
 * @param tag           - Tag entity with slugs array
 * @param locale        - Desired locale
 * @param defaultLocale - Fallback locale; defaults to 'en'
 * @returns URL string or null when the slug cannot be resolved
 */
export function buildTagUrl(
    tag: SlugBearingEntity,
    locale: string,
    defaultLocale = "en"
): string | null {
    const slug = resolveSegment(tag, locale, defaultLocale);
    if (!slug) return null;

    return `/tags/${slug}`;
}

// ---------------------------------------------------------------------------
// buildPageUrl
// ---------------------------------------------------------------------------

/**
 * Builds a static page URL: /{slug}
 *
 * @param page          - Page entity with slugs array
 * @param locale        - Desired locale
 * @param defaultLocale - Fallback locale; defaults to 'en'
 * @returns URL string or null when the slug cannot be resolved
 */
export function buildPageUrl(
    page: SlugBearingEntity,
    locale: string,
    defaultLocale = "en"
): string | null {
    const slug = resolveSegment(page, locale, defaultLocale);
    if (!slug) return null;

    return `/${slug}`;
}
