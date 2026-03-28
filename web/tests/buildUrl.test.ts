/**
 * Tests for URL builder utilities.
 *
 * Acceptance criteria:
 *  - buildProductUrl(category, subcategory, product, locale, defaultLocale?) → '/{cat}/{subcat}/{product}' or null
 *  - buildCategoryUrl(category, subcategory, locale, defaultLocale?) → '/{cat}/{subcat}' or null
 *  - buildBrandUrl(brand, locale, defaultLocale?) → '/brands/{brand}' or null
 *  - buildTagUrl(tag, locale, defaultLocale?) → '/tags/{tag}' or null
 *  - buildPageUrl(page, locale, defaultLocale?) → '/{page}' or null
 *  - Slug resolution uses useSlug() (locale → defaultLocale → null)
 *  - Returns null (not ID-based URL) when any required segment has no resolvable slug
 *  - No language prefix in the URL
 */

import { describe, it, expect } from "vitest";
import {
    buildProductUrl,
    buildCategoryUrl,
    buildBrandUrl,
    buildTagUrl,
    buildPageUrl,
} from "../utils/buildUrl";
import type { SlugRecord } from "../composables/useSlug";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeSlugged(id: number, slugs: SlugRecord[]): { id: number; slugs: SlugRecord[] } {
    return { id, slugs };
}

const catEn = makeSlugged(1, [{ locale: "en", slug: "pla-filaments" }]);
const catEs = makeSlugged(1, [
    { locale: "en", slug: "pla-filaments" },
    { locale: "es", slug: "filamentos-pla" },
]);

const subcatEn = makeSlugged(2, [{ locale: "en", slug: "1-75mm" }]);
const subcatEs = makeSlugged(2, [
    { locale: "en", slug: "1-75mm" },
    { locale: "es", slug: "1-75mm-es" },
]);

const productEn = makeSlugged(3, [{ locale: "en", slug: "pla-red" }]);
const productEs = makeSlugged(3, [
    { locale: "en", slug: "pla-red" },
    { locale: "es", slug: "pla-rojo" },
]);

const noSlug = makeSlugged(99, []);

// ---------------------------------------------------------------------------
// buildProductUrl
// ---------------------------------------------------------------------------

describe("buildProductUrl()", () => {
    it("returns correct 3-segment URL for English locale", () => {
        expect(buildProductUrl(catEn, subcatEn, productEn, "en")).toBe(
            "/pla-filaments/1-75mm/pla-red"
        );
    });

    it("uses requested locale when all segments have it", () => {
        expect(buildProductUrl(catEs, subcatEs, productEs, "es")).toBe(
            "/filamentos-pla/1-75mm-es/pla-rojo"
        );
    });

    it("falls back to default locale (en) when requested locale missing", () => {
        // 'fr' not in slugs → falls back to 'en'
        expect(buildProductUrl(catEs, subcatEs, productEs, "fr")).toBe(
            "/pla-filaments/1-75mm/pla-red"
        );
    });

    it("uses custom defaultLocale when provided", () => {
        const catPt = makeSlugged(1, [{ locale: "pt", slug: "filamentos-pla-pt" }]);
        const subcatPt = makeSlugged(2, [{ locale: "pt", slug: "1-75mm-pt" }]);
        const productPt = makeSlugged(3, [{ locale: "pt", slug: "pla-vermelho" }]);

        expect(buildProductUrl(catPt, subcatPt, productPt, "de", "pt")).toBe(
            "/filamentos-pla-pt/1-75mm-pt/pla-vermelho"
        );
    });

    it("returns null when category slug cannot be resolved", () => {
        expect(buildProductUrl(noSlug, subcatEn, productEn, "en")).toBeNull();
    });

    it("returns null when subcategory slug cannot be resolved", () => {
        expect(buildProductUrl(catEn, noSlug, productEn, "en")).toBeNull();
    });

    it("returns null when product slug cannot be resolved", () => {
        expect(buildProductUrl(catEn, subcatEn, noSlug, "en")).toBeNull();
    });

    it("returns null when no segment has any slug at all", () => {
        expect(buildProductUrl(noSlug, noSlug, noSlug, "en")).toBeNull();
    });

    it("URL has no language prefix", () => {
        const url = buildProductUrl(catEn, subcatEn, productEn, "en");
        expect(url).not.toMatch(/^\/en\//);
        expect(url).not.toMatch(/^\/es\//);
    });
});

// ---------------------------------------------------------------------------
// buildCategoryUrl
// ---------------------------------------------------------------------------

describe("buildCategoryUrl()", () => {
    it("returns correct 2-segment URL for English locale", () => {
        expect(buildCategoryUrl(catEn, subcatEn, "en")).toBe("/pla-filaments/1-75mm");
    });

    it("uses requested locale when both segments have it", () => {
        expect(buildCategoryUrl(catEs, subcatEs, "es")).toBe("/filamentos-pla/1-75mm-es");
    });

    it("falls back to default locale when requested locale missing", () => {
        expect(buildCategoryUrl(catEs, subcatEs, "fr")).toBe("/pla-filaments/1-75mm");
    });

    it("returns null when category slug cannot be resolved", () => {
        expect(buildCategoryUrl(noSlug, subcatEn, "en")).toBeNull();
    });

    it("returns null when subcategory slug cannot be resolved", () => {
        expect(buildCategoryUrl(catEn, noSlug, "en")).toBeNull();
    });

    it("URL has no language prefix", () => {
        const url = buildCategoryUrl(catEn, subcatEn, "en");
        expect(url).not.toMatch(/^\/en\//);
    });
});

// ---------------------------------------------------------------------------
// buildBrandUrl
// ---------------------------------------------------------------------------

describe("buildBrandUrl()", () => {
    const brandEn = makeSlugged(10, [{ locale: "en", slug: "acme" }]);
    const brandEs = makeSlugged(10, [
        { locale: "en", slug: "acme" },
        { locale: "es", slug: "acme-es" },
    ]);

    it("returns /brands/{slug} for English locale", () => {
        expect(buildBrandUrl(brandEn, "en")).toBe("/brands/acme");
    });

    it("uses requested locale when available", () => {
        expect(buildBrandUrl(brandEs, "es")).toBe("/brands/acme-es");
    });

    it("falls back to default locale", () => {
        expect(buildBrandUrl(brandEs, "fr")).toBe("/brands/acme");
    });

    it("returns null when slug cannot be resolved", () => {
        expect(buildBrandUrl(noSlug, "en")).toBeNull();
    });
});

// ---------------------------------------------------------------------------
// buildTagUrl
// ---------------------------------------------------------------------------

describe("buildTagUrl()", () => {
    const tagEn = makeSlugged(20, [{ locale: "en", slug: "flexible" }]);
    const tagEs = makeSlugged(20, [
        { locale: "en", slug: "flexible" },
        { locale: "es", slug: "flexible-es" },
    ]);

    it("returns /tags/{slug} for English locale", () => {
        expect(buildTagUrl(tagEn, "en")).toBe("/tags/flexible");
    });

    it("uses requested locale when available", () => {
        expect(buildTagUrl(tagEs, "es")).toBe("/tags/flexible-es");
    });

    it("falls back to default locale", () => {
        expect(buildTagUrl(tagEs, "fr")).toBe("/tags/flexible");
    });

    it("returns null when slug cannot be resolved", () => {
        expect(buildTagUrl(noSlug, "en")).toBeNull();
    });
});

// ---------------------------------------------------------------------------
// buildPageUrl
// ---------------------------------------------------------------------------

describe("buildPageUrl()", () => {
    const pageEn = makeSlugged(30, [{ locale: "en", slug: "about-us" }]);
    const pageEs = makeSlugged(30, [
        { locale: "en", slug: "about-us" },
        { locale: "es", slug: "sobre-nosotros" },
    ]);

    it("returns /{slug} for English locale", () => {
        expect(buildPageUrl(pageEn, "en")).toBe("/about-us");
    });

    it("uses requested locale when available", () => {
        expect(buildPageUrl(pageEs, "es")).toBe("/sobre-nosotros");
    });

    it("falls back to default locale", () => {
        expect(buildPageUrl(pageEs, "fr")).toBe("/about-us");
    });

    it("returns null when slug cannot be resolved", () => {
        expect(buildPageUrl(noSlug, "en")).toBeNull();
    });
});
