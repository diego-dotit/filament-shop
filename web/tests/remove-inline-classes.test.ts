/**
 * Tests for T2.5 — Remove inline Tailwind class attributes from custom components.
 * Verifies that static `class="..."` attributes are removed while:
 * - `:class` bindings are preserved (ReviewForm)
 * - Component props, event handlers, v-for loops, and slot structure remain intact
 */

import { describe, it, expect } from "vitest";
import { readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));

function read(name: string): string {
    return readFileSync(resolve(__dirname, `../components/${name}`), "utf-8");
}

// ---------------------------------------------------------------------------
// Helper: count static class="..." attributes (not :class)
// ---------------------------------------------------------------------------
function countStaticClassAttrs(src: string): number {
    // Match class="..." but NOT :class="..."
    const matches = src.match(/(?<!:)class="[^"]*"/g);
    return matches ? matches.length : 0;
}

// ---------------------------------------------------------------------------
// Header.vue
// ---------------------------------------------------------------------------
describe("Header.vue — inline class removal", () => {
    it("has 0 static class attributes", () => {
        const src = read("Header.vue");
        expect(countStaticClassAttrs(src)).toBe(0);
    });

    it("preserves Select model-value binding and update handler", () => {
        const src = read("Header.vue");
        expect(src).toContain(":model-value=\"language\"");
        expect(src).toContain("@update:model-value");
    });

    it("preserves v-for loops on SelectItem", () => {
        const src = read("Header.vue");
        expect(src).toMatch(/v-for="lang in availableLanguages"/);
        expect(src).toMatch(/v-for="curr in availableCurrencies"/);
    });

    it("preserves NuxtLink to props", () => {
        const src = read("Header.vue");
        expect(src).toContain('to="/"');
        expect(src).toContain('to="/cart"');
    });

    it("preserves Button variant and size props and logout handler", () => {
        const src = read("Header.vue");
        expect(src).toContain('variant="outline"');
        expect(src).toContain('size="sm"');
        expect(src).toContain("@click=\"logout\"");
    });

    it("preserves v-if / v-else auth conditionals", () => {
        const src = read("Header.vue");
        expect(src).toContain("v-if=\"isAuthenticated\"");
        expect(src).toContain("v-else");
    });

    it("SelectTrigger has no class prop", () => {
        const src = read("Header.vue");
        // SelectTrigger lines must not contain class="..."
        const lines = src.split("\n").filter((l) => l.includes("SelectTrigger"));
        for (const line of lines) {
            expect(line).not.toMatch(/(?<!:)class="/);
        }
    });

    it("SelectContent has no class prop", () => {
        const src = read("Header.vue");
        const lines = src.split("\n").filter((l) => l.includes("SelectContent"));
        for (const line of lines) {
            expect(line).not.toMatch(/(?<!:)class="/);
        }
    });
});

// ---------------------------------------------------------------------------
// Footer.vue
// ---------------------------------------------------------------------------
describe("Footer.vue — inline class removal", () => {
    it("has 0 static class attributes", () => {
        const src = read("Footer.vue");
        expect(countStaticClassAttrs(src)).toBe(0);
    });

    it("preserves NuxtLink to props for all footer links", () => {
        const src = read("Footer.vue");
        expect(src).toContain('to="/about"');
        expect(src).toContain('to="/contact"');
        expect(src).toContain('to="/terms"');
        expect(src).toContain('to="/privacy"');
    });

    it("preserves year expression in copyright paragraph", () => {
        const src = read("Footer.vue");
        expect(src).toContain("{{ year }}");
    });

    it("preserves aria-label on footer nav", () => {
        const src = read("Footer.vue");
        expect(src).toContain('aria-label="Footer navigation"');
    });
});

// ---------------------------------------------------------------------------
// ProductCard.vue
// ---------------------------------------------------------------------------
describe("ProductCard.vue — inline class removal", () => {
    it("has 0 static class attributes", () => {
        const src = read("ProductCard.vue");
        expect(countStaticClassAttrs(src)).toBe(0);
    });

    it("preserves :to binding on NuxtLink", () => {
        const src = read("ProductCard.vue");
        expect(src).toContain(":to=\"productUrl\"");
    });

    it("preserves :src and :alt bindings on img", () => {
        const src = read("ProductCard.vue");
        expect(src).toContain(":src=\"imageSrc\"");
        expect(src).toContain(":alt=\"product.name\"");
    });

    it("Card component has no class prop", () => {
        const src = read("ProductCard.vue");
        const cardLine = src.split("\n").find((l) => /^\s*<Card\b/.test(l));
        expect(cardLine).toBeDefined();
        expect(cardLine).not.toMatch(/(?<!:)class="/);
    });

    it("CardContent has no class prop", () => {
        const src = read("ProductCard.vue");
        const line = src.split("\n").find((l) => l.includes("CardContent") && !l.includes("</"));
        expect(line).toBeDefined();
        expect(line).not.toMatch(/(?<!:)class="/);
    });
});

// ---------------------------------------------------------------------------
// CartItem.vue
// ---------------------------------------------------------------------------
describe("CartItem.vue — inline class removal", () => {
    it("has 0 static class attributes", () => {
        const src = read("CartItem.vue");
        expect(countStaticClassAttrs(src)).toBe(0);
    });

    it("Remove button retains variant='ghost', size='sm', and data-testid", () => {
        const src = read("CartItem.vue");
        expect(src).toContain('variant="ghost"');
        expect(src).toContain('data-testid="remove"');
    });

    it("Remove button has no class prop", () => {
        const src = read("CartItem.vue");
        // Find remove button block
        const idx = src.indexOf('data-testid="remove"');
        const surroundingBlock = src.slice(Math.max(0, idx - 200), idx + 50);
        expect(surroundingBlock).not.toMatch(/(?<!:)class="/);
    });

    it("preserves @click handlers and :disabled binding", () => {
        const src = read("CartItem.vue");
        expect(src).toContain("@click=\"decrement\"");
        expect(src).toContain("@click=\"increment\"");
        expect(src).toContain("@click=\"remove\"");
        expect(src).toContain(":disabled=\"item.quantity <= 1\"");
    });

    it("preserves data-testid on increment and decrement buttons", () => {
        const src = read("CartItem.vue");
        expect(src).toContain('data-testid="decrement"');
        expect(src).toContain('data-testid="increment"');
    });
});

// ---------------------------------------------------------------------------
// OrderConfirmation.vue
// ---------------------------------------------------------------------------
describe("OrderConfirmation.vue — inline class removal", () => {
    it("has 0 static class attributes", () => {
        const src = read("OrderConfirmation.vue");
        expect(countStaticClassAttrs(src)).toBe(0);
    });

    it("preserves Button as-child and variant props", () => {
        const src = read("OrderConfirmation.vue");
        expect(src).toContain("as-child");
        expect(src).toContain('variant="default"');
        expect(src).toContain('variant="secondary"');
    });

    it("preserves NuxtLink :to binding for order details", () => {
        const src = read("OrderConfirmation.vue");
        expect(src).toContain(":to=\"`/account/orders/${orderId}`\"");
    });

    it("preserves orderId and totalAmount interpolations", () => {
        const src = read("OrderConfirmation.vue");
        expect(src).toContain("{{ orderId }}");
        expect(src).toContain("{{ totalAmount }}");
    });

    it("Alert component has no class prop", () => {
        const src = read("OrderConfirmation.vue");
        const line = src.split("\n").find((l) => /\s*<Alert\b/.test(l));
        expect(line).toBeDefined();
        expect(line).not.toMatch(/(?<!:)class="/);
    });
});

// ---------------------------------------------------------------------------
// ReviewForm.vue
// ---------------------------------------------------------------------------
describe("ReviewForm.vue — inline class removal (preserve :class bindings)", () => {
    it("has 0 static class attributes", () => {
        const src = read("ReviewForm.vue");
        expect(countStaticClassAttrs(src)).toBe(0);
    });

    it("preserves :class binding on star Button (text-yellow-400)", () => {
        const src = read("ReviewForm.vue");
        expect(src).toContain(":class=\"{ 'text-yellow-400': rating >= star }\"");
    });

    it("preserves :class binding on Textarea (border-red-500)", () => {
        const src = read("ReviewForm.vue");
        expect(src).toContain(":class=\"{ 'border-red-500': commentTooLong }\"");
    });

    it("preserves :class binding on character count paragraph", () => {
        const src = read("ReviewForm.vue");
        expect(src).toContain(":class=\"{ 'text-red-500': commentTooLong, 'text-gray-500': !commentTooLong }\"");
    });

    it("preserves v-if, v-else-if conditionals for already-reviewed and success states", () => {
        const src = read("ReviewForm.vue");
        expect(src).toContain("v-if=\"isAuthenticated && alreadyReviewed\"");
        expect(src).toContain("v-else-if=\"submitted\"");
        expect(src).toContain("v-else-if=\"isAuthenticated\"");
    });

    it("preserves data-testid attributes", () => {
        const src = read("ReviewForm.vue");
        expect(src).toContain('data-testid="already-reviewed"');
        expect(src).toContain('data-testid="review-success"');
        expect(src).toContain('data-testid="review-form"');
        expect(src).toContain('data-testid="submit-review"');
    });

    it("preserves v-for on star buttons", () => {
        const src = read("ReviewForm.vue");
        expect(src).toMatch(/v-for="star in 5"/);
    });
});

// ---------------------------------------------------------------------------
// CategoryChip.vue and Breadcrumb.vue — already clean (verify only)
// ---------------------------------------------------------------------------
describe("CategoryChip.vue — already has 0 class attributes", () => {
    it("has 0 static class attributes", () => {
        const src = read("CategoryChip.vue");
        expect(countStaticClassAttrs(src)).toBe(0);
    });
});

describe("Breadcrumb.vue — already has 0 class attributes", () => {
    it("has 0 static class attributes", () => {
        const src = read("Breadcrumb.vue");
        expect(countStaticClassAttrs(src)).toBe(0);
    });
});
