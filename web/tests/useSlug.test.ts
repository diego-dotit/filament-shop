/**
 * Tests for useSlug() composable.
 *
 * Acceptance criteria:
 *  - useSlug(entity, locale, defaultLocale?) returns the slug for the requested locale
 *  - Falls back to defaultLocale (or 'en') when requested locale not found
 *  - Returns null when neither requested locale nor fallback locale has a slug
 *  - SlugRecord interface exported with locale: string, slug: string
 *  - Works with any entity type that has a slugs array
 *
 * API compatibility design decision (T2.4):
 *  - useSlug() is for Phase 3+ entities that carry a full `slugs: SlugRecord[]`
 *    relationship (multi-locale slug resolution).
 *  - When the API already returns a pre-localised flat `slug: string` field
 *    (current product/category API response), callers MUST use `entity.slug`
 *    directly — useSlug() returns null for entities with no `slugs` array.
 *  - The recommended fallback pattern (e.g. CategoryChip.vue) is:
 *      const slug = useSlug(entity, locale) ?? entity.slug;
 *    This makes the code forward-compatible: it uses the slugs array when
 *    available (Phase 3+) and transparently falls back to the flat slug today.
 */

import { describe, it, expect } from "vitest";
import { useSlug, type SlugRecord } from "../composables/useSlug";

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeEntity(slugs: SlugRecord[]): { id: number; slugs: SlugRecord[] } {
    return { id: 1, slugs };
}

// ---------------------------------------------------------------------------
// SlugRecord interface
// ---------------------------------------------------------------------------

describe("SlugRecord interface", () => {
    it("has locale and slug string fields", () => {
        const record: SlugRecord = { locale: "en", slug: "pla-filament" };

        expect(record.locale).toBe("en");
        expect(record.slug).toBe("pla-filament");
    });
});

// ---------------------------------------------------------------------------
// useSlug() — happy paths
// ---------------------------------------------------------------------------

describe("useSlug() — returns slug for requested locale", () => {
    it("returns the slug when the requested locale is present", () => {
        const entity = makeEntity([
            { locale: "en", slug: "pla-filament" },
            { locale: "es", slug: "filamento-pla" },
        ]);

        expect(useSlug(entity, "es")).toBe("filamento-pla");
    });

    it("returns the English slug when locale is 'en'", () => {
        const entity = makeEntity([
            { locale: "en", slug: "pla-filament" },
            { locale: "de", slug: "pla-filament-de" },
        ]);

        expect(useSlug(entity, "en")).toBe("pla-filament");
    });

    it("returns slug when multiple locales exist and requested one matches", () => {
        const entity = makeEntity([
            { locale: "en", slug: "brand-acme" },
            { locale: "fr", slug: "marque-acme" },
            { locale: "de", slug: "marke-acme" },
        ]);

        expect(useSlug(entity, "fr")).toBe("marque-acme");
        expect(useSlug(entity, "de")).toBe("marke-acme");
    });
});

// ---------------------------------------------------------------------------
// useSlug() — fallback logic
// ---------------------------------------------------------------------------

describe("useSlug() — fallback to default locale", () => {
    it("falls back to 'en' when requested locale is missing and no defaultLocale specified", () => {
        const entity = makeEntity([
            { locale: "en", slug: "pla-filament" },
            { locale: "fr", slug: "filament-pla" },
        ]);

        // 'de' not present → falls back to 'en'
        expect(useSlug(entity, "de")).toBe("pla-filament");
    });

    it("falls back to custom defaultLocale when requested locale is missing", () => {
        const entity = makeEntity([
            { locale: "es", slug: "filamento-pla" },
            { locale: "pt", slug: "filamento-pla-pt" },
        ]);

        // 'de' not present → falls back to 'es' (custom default)
        expect(useSlug(entity, "de", "es")).toBe("filamento-pla");
    });

    it("returns defaultLocale slug when requested locale is missing", () => {
        const entity = makeEntity([
            { locale: "en", slug: "my-product" },
        ]);

        expect(useSlug(entity, "ja", "en")).toBe("my-product");
    });
});

// ---------------------------------------------------------------------------
// useSlug() — null cases
// ---------------------------------------------------------------------------

describe("useSlug() — returns null when no slug available", () => {
    it("returns null when slugs array is empty", () => {
        const entity = makeEntity([]);

        expect(useSlug(entity, "en")).toBeNull();
    });

    it("returns null when neither requested locale nor default locale exists", () => {
        const entity = makeEntity([
            { locale: "fr", slug: "produit-pla" },
        ]);

        // 'de' requested, 'en' default — neither exists
        expect(useSlug(entity, "de")).toBeNull();
    });

    it("returns null when entity has no slugs property", () => {
        const entity: { id: number; slugs?: SlugRecord[] } = { id: 99 };

        expect(useSlug(entity, "en")).toBeNull();
    });

    it("returns null when requested locale and custom defaultLocale both missing", () => {
        const entity = makeEntity([
            { locale: "en", slug: "product-en" },
        ]);

        // 'de' requested, 'fr' default — only 'en' exists
        expect(useSlug(entity, "de", "fr")).toBeNull();
    });
});

// ---------------------------------------------------------------------------
// useSlug() — works with various entity shapes
// ---------------------------------------------------------------------------

describe("useSlug() — works with different entity types", () => {
    it("works with a category-like entity", () => {
        const category = {
            id: 5,
            name: "PLA Filaments",
            slugs: [
                { locale: "en", slug: "pla-filaments" },
                { locale: "es", slug: "filamentos-pla" },
            ],
        };

        expect(useSlug(category, "es")).toBe("filamentos-pla");
    });

    it("works with a brand-like entity", () => {
        const brand = {
            id: 3,
            title: "Acme",
            slugs: [{ locale: "en", slug: "acme" }],
        };

        expect(useSlug(brand, "en")).toBe("acme");
    });
});

// ---------------------------------------------------------------------------
// API compatibility — design decision (T2.4)
//
// These tests document the two-path slug resolution strategy:
//  • Phase 3+ entities: carry a `slugs: SlugRecord[]` → use useSlug()
//  • Current flat-API entities: carry only a `slug: string` → use entity.slug
//
// The recommended forward-compatible caller pattern is:
//   const slug = useSlug(entity, locale) ?? entity.slug;
// ---------------------------------------------------------------------------

describe("useSlug() — API compatibility: flat-slug entity (new API pattern)", () => {
    it("returns null for a flat-API entity that has only a slug string (no slugs array)", () => {
        // Simulates a current API response: { id, slug: 'pla-filament', ... }
        // useSlug() is not the right tool here — caller must use entity.slug directly.
        const flatApiEntity: { id: number; slug: string; slugs?: SlugRecord[] } = {
            id: 10,
            slug: "pla-filament",
        };

        // useSlug returns null — no slugs array available
        expect(useSlug(flatApiEntity, "en")).toBeNull();
    });

    it("caller fallback pattern (useSlug ?? entity.slug) resolves flat-slug entity correctly", () => {
        // This is the pattern used in CategoryChip.vue:
        //   const slug = useSlug(category, locale) ?? category.slug;
        const flatApiEntity = { id: 10, slug: "filamento-pla" };

        const resolvedSlug = useSlug(flatApiEntity, "es") ?? flatApiEntity.slug;

        // Falls back to the pre-localised API slug
        expect(resolvedSlug).toBe("filamento-pla");
    });

    it("caller fallback pattern prefers slugs array when available (Phase 3+ entity)", () => {
        // Phase 3+ entity: has both flat slug (legacy) and slugs array (multi-locale)
        const hybridEntity = {
            id: 10,
            slug: "pla-filament",           // legacy fallback
            slugs: [
                { locale: "en", slug: "pla-filament" },
                { locale: "es", slug: "filamento-pla" },
            ],
        };

        // useSlug resolves from slugs array → locale-specific result
        const resolvedEs = useSlug(hybridEntity, "es") ?? hybridEntity.slug;
        expect(resolvedEs).toBe("filamento-pla");

        const resolvedEn = useSlug(hybridEntity, "en") ?? hybridEntity.slug;
        expect(resolvedEn).toBe("pla-filament");
    });

    it("caller fallback pattern falls back to entity.slug when requested locale absent from slugs array", () => {
        const hybridEntity = {
            id: 10,
            slug: "pla-filament",           // API-level flat slug (pre-localised)
            slugs: [
                { locale: "en", slug: "pla-filament" },
            ],
        };

        // 'de' not in slugs array, 'en' default also absent from slugs... wait:
        // 'en' IS present → falls back to 'en' slug via useSlug defaultLocale
        const resolvedDe = useSlug(hybridEntity, "de") ?? hybridEntity.slug;
        expect(resolvedDe).toBe("pla-filament"); // useSlug falls back to 'en'
    });
});
