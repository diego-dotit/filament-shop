/**
 * Tests for [...slug].vue shadcn migration (T4.7)
 * Verifies:
 * - No <style> or <style scoped> block remains in the source
 * - No BEM CSS class references remain
 * - shadcn Button used for Add to Cart and pagination
 * - shadcn Select used for variant selector
 * - shadcn Breadcrumb wrapper used for category breadcrumb
 * - All Tailwind utility classes present for layout/typography
 * - Product gallery thumbnails use Tailwind border utilities
 * - Responsive layout via Tailwind grid-cols breakpoints
 */

import { describe, it, expect } from "vitest";
import { readFileSync, existsSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const pagePath = resolve(__dirname, "../pages/[...slug].vue");

function readPage(): string {
    return readFileSync(pagePath, "utf-8");
}

// ---------------------------------------------------------------------------
// File existence
// ---------------------------------------------------------------------------

describe("[...slug].vue — file", () => {
    it("page file exists", () => {
        expect(existsSync(pagePath)).toBe(true);
    });
});

// ---------------------------------------------------------------------------
// Structural: no raw CSS
// ---------------------------------------------------------------------------

describe("[...slug].vue — no style blocks", () => {
    it("has NO <style> block", () => {
        const src = readPage();
        expect(src).not.toMatch(/<style/);
    });

    it("has NO <style scoped> block", () => {
        const src = readPage();
        expect(src).not.toMatch(/<style\s+scoped/);
    });
});

// ---------------------------------------------------------------------------
// Structural: no BEM class names
// ---------------------------------------------------------------------------

describe("[...slug].vue — no BEM class names", () => {
    it("has NO .product-detail__ BEM classes in template", () => {
        const src = readPage();
        expect(src).not.toMatch(/product-detail__/);
    });

    it("has NO .gallery__ BEM classes in template", () => {
        const src = readPage();
        expect(src).not.toMatch(/gallery__/);
    });

    it("has NO .category-page__ BEM classes in template", () => {
        const src = readPage();
        expect(src).not.toMatch(/category-page__/);
    });

    it("has NO .slug-page__ BEM classes in template", () => {
        const src = readPage();
        expect(src).not.toMatch(/slug-page__/);
    });

    it("has NO .specs-list BEM class in template", () => {
        const src = readPage();
        expect(src).not.toMatch(/specs-list/);
    });

    it("has NO .product-grid class in template", () => {
        const src = readPage();
        expect(src).not.toMatch(/product-grid/);
    });
});

// ---------------------------------------------------------------------------
// shadcn imports
// ---------------------------------------------------------------------------

describe("[...slug].vue — shadcn imports", () => {
    it("imports Button from @/components/ui/button", () => {
        const src = readPage();
        expect(src).toMatch(/from ['"]@\/components\/ui\/button['"]/);
    });

    it("imports Select components from @/components/ui/select", () => {
        const src = readPage();
        expect(src).toMatch(/from ['"]@\/components\/ui\/select['"]/);
    });
});

// ---------------------------------------------------------------------------
// shadcn component usage in template
// ---------------------------------------------------------------------------

describe("[...slug].vue — shadcn component usage", () => {
    it("uses <Button> for Add to Cart", () => {
        const src = readPage();
        expect(src).toMatch(/<Button/);
    });

    it("uses <Select> for variant selector", () => {
        const src = readPage();
        expect(src).toMatch(/<Select/);
    });

    it("uses <SelectTrigger> sub-component", () => {
        const src = readPage();
        expect(src).toMatch(/<SelectTrigger/);
    });

    it("uses <SelectContent> sub-component", () => {
        const src = readPage();
        expect(src).toMatch(/<SelectContent/);
    });

    it("uses <SelectItem> sub-component", () => {
        const src = readPage();
        expect(src).toMatch(/<SelectItem/);
    });

    it("no bare HTML <select> element for variant selector", () => {
        const src = readPage();
        // The template should not contain a raw <select (without SelectTrigger)
        // We look for class="product-detail__select" which would be the old pattern
        expect(src).not.toMatch(/class="product-detail__select"/);
    });

    it("no bare HTML <button> for Add to Cart", () => {
        const src = readPage();
        // The old button had data-testid="add-to-cart" as a plain <button>
        // After migration it should be a <Button> component
        expect(src).not.toMatch(/<button[^>]*data-testid="add-to-cart"/);
    });
});

// ---------------------------------------------------------------------------
// Category breadcrumb: uses Breadcrumb component (not manual nav)
// ---------------------------------------------------------------------------

describe("[...slug].vue — category breadcrumb", () => {
    it("uses <Breadcrumb> component for category page breadcrumb", () => {
        const src = readPage();
        // Should use Breadcrumb component for category page, not manual nav with category-page__breadcrumb
        // The product detail already used <Breadcrumb>, so the category section should too
        expect(src).toMatch(/<Breadcrumb/);
    });

    it("does NOT have manual breadcrumb nav with category-page__breadcrumb-sep", () => {
        const src = readPage();
        expect(src).not.toMatch(/category-page__breadcrumb-sep/);
    });

    it("has categoryBreadcrumb computed for category page", () => {
        const src = readPage();
        expect(src).toMatch(/categoryBreadcrumb/);
    });
});

// ---------------------------------------------------------------------------
// Tailwind utility classes
// ---------------------------------------------------------------------------

describe("[...slug].vue — Tailwind classes", () => {
    it("uses Tailwind responsive grid for product detail layout", () => {
        const src = readPage();
        expect(src).toMatch(/md:grid-cols-2/);
    });

    it("uses Tailwind for gallery primary image", () => {
        const src = readPage();
        // Should have object-cover and rounded
        expect(src).toMatch(/object-cover/);
        expect(src).toMatch(/rounded/);
    });

    it("uses Tailwind for gallery thumbnails container (flex/gap)", () => {
        const src = readPage();
        expect(src).toMatch(/flex.*gap|gap.*flex/);
    });

    it("uses Tailwind border classes for thumbnail active state", () => {
        const src = readPage();
        expect(src).toMatch(/border-blue-500/);
        expect(src).toMatch(/border-transparent/);
    });

    it("uses Tailwind text utilities for price", () => {
        const src = readPage();
        expect(src).toMatch(/text-lg|text-xl|font-semibold|font-bold/);
    });

    it("uses Tailwind text-green-600 for in-stock", () => {
        const src = readPage();
        expect(src).toMatch(/text-green-600/);
    });

    it("uses Tailwind text-red-600 for out-of-stock", () => {
        const src = readPage();
        expect(src).toMatch(/text-red-600/);
    });

    it("uses Tailwind grid for specs list", () => {
        const src = readPage();
        expect(src).toMatch(/grid-cols-\[max-content/);
    });

    it("uses Tailwind border-t for review/specs section dividers", () => {
        const src = readPage();
        expect(src).toMatch(/border-t/);
    });

    it("uses Tailwind grid for product grid in category page", () => {
        const src = readPage();
        expect(src).toMatch(/grid-cols-\[repeat\(auto-fill/);
    });

    it("uses Tailwind flex for pagination layout", () => {
        const src = readPage();
        expect(src).toMatch(/justify-center/);
    });
});

// ---------------------------------------------------------------------------
// Logic preserved: data-testid attributes
// ---------------------------------------------------------------------------

describe("[...slug].vue — functional attributes preserved", () => {
    it("preserves data-testid='add-to-cart' on Button", () => {
        const src = readPage();
        expect(src).toMatch(/data-testid="add-to-cart"/);
    });

    it("preserves data-testid='pagination' on pagination nav", () => {
        const src = readPage();
        expect(src).toMatch(/data-testid="pagination"/);
    });

    it("preserves data-testid='subcategories'", () => {
        const src = readPage();
        expect(src).toMatch(/data-testid="subcategories"/);
    });

    it("preserves :disabled binding on Add to Cart Button", () => {
        const src = readPage();
        expect(src).toMatch(/:disabled="!canAddToCart"|:disabled="!\s*canAddToCart"/);
    });

    it("preserves handleAddToCart click handler", () => {
        const src = readPage();
        expect(src).toMatch(/@click="handleAddToCart"/);
    });

    it("preserves v-model on variant Select", () => {
        const src = readPage();
        // v-model on Select component
        expect(src).toMatch(/v-model/);
    });
});
