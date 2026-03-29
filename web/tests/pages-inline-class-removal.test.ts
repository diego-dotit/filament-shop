/**
 * T2.2 — Remove inline Tailwind class attributes from remaining page files
 *
 * Verifies that static `class="..."` Tailwind utility attributes have been
 * removed from:
 *   - /web/pages/login.vue
 *   - /web/pages/register.vue
 *   - /web/pages/cart.vue
 *   - /web/pages/checkout.vue
 *   - /web/pages/[...slug].vue
 *   - /web/error.vue
 *
 * Acceptance criteria:
 * - No static `class="..."` attributes containing Tailwind layout/typography utilities
 * - All `:class` dynamic bindings preserved
 * - All functional logic, routing, event handlers, and data binding intact
 */

import { describe, it, expect } from "vitest";
import { readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));

function read(relPath: string): string {
    return readFileSync(resolve(__dirname, relPath), "utf-8");
}

// ---------------------------------------------------------------------------
// login.vue
// ---------------------------------------------------------------------------

describe("login.vue — inline class removal (T2.2)", () => {
    const src = () => read("../pages/login.vue");

    it("has no class= layout wrapper (min-h-[60vh] flex ...)", () => {
        expect(src()).not.toContain('class="min-h-[60vh]');
    });

    it("has no class= on Card (w-full max-w-sm)", () => {
        expect(src()).not.toContain('class="w-full max-w-sm"');
    });

    it("has no class= on CardTitle (text-2xl text-center)", () => {
        expect(src()).not.toContain('class="text-2xl text-center"');
    });

    it("has no class= on form (flex flex-col gap-4)", () => {
        expect(src()).not.toContain('class="flex flex-col gap-4"');
    });

    it("has no class= on field divs (flex flex-col gap-1.5)", () => {
        expect(src()).not.toContain('class="flex flex-col gap-1.5"');
    });

    it("has no class= on error paragraphs (text-sm text-destructive)", () => {
        expect(src()).not.toContain('class="text-sm text-destructive"');
    });

    it("has no class= on submit Button (w-full)", () => {
        expect(src()).not.toContain('class="w-full"');
    });

    it("has no class= on bottom paragraph (text-center text-sm ...)", () => {
        expect(src()).not.toContain('class="text-center text-sm');
    });

    it("has no class= on NuxtLink (underline hover:text-foreground)", () => {
        expect(src()).not.toContain('class="underline hover:text-foreground"');
    });

    // Functional logic preserved
    it("still has handleSubmit event handler", () => {
        expect(src()).toContain("handleSubmit");
    });

    it("still uses useAuth composable", () => {
        expect(src()).toContain("useAuth");
    });

    it("still has navigateTo call", () => {
        expect(src()).toContain("navigateTo");
    });
});

// ---------------------------------------------------------------------------
// register.vue
// ---------------------------------------------------------------------------

describe("register.vue — inline class removal (T2.2)", () => {
    const src = () => read("../pages/register.vue");

    it("has no class= layout wrapper (min-h-[60vh] flex ...)", () => {
        expect(src()).not.toContain('class="min-h-[60vh]');
    });

    it("has no class= on Card (w-full max-w-sm)", () => {
        expect(src()).not.toContain('class="w-full max-w-sm"');
    });

    it("has no class= on CardTitle (text-2xl text-center)", () => {
        expect(src()).not.toContain('class="text-2xl text-center"');
    });

    it("has no class= on form (flex flex-col gap-4)", () => {
        expect(src()).not.toContain('class="flex flex-col gap-4"');
    });

    it("has no class= on field divs (flex flex-col gap-1.5)", () => {
        expect(src()).not.toContain('class="flex flex-col gap-1.5"');
    });

    it("has no class= on mismatch error paragraph (text-sm text-destructive)", () => {
        expect(src()).not.toContain('class="text-sm text-destructive"');
    });

    it("has no class= on submit Button (w-full)", () => {
        expect(src()).not.toContain('class="w-full"');
    });

    it("has no class= on bottom paragraph (text-center ...)", () => {
        expect(src()).not.toContain('class="text-center text-sm');
    });

    it("has no class= on NuxtLink (underline hover:text-foreground)", () => {
        expect(src()).not.toContain('class="underline hover:text-foreground"');
    });

    // Functional logic preserved
    it("still has handleSubmit event handler", () => {
        expect(src()).toContain("handleSubmit");
    });

    it("still uses useAuth composable", () => {
        expect(src()).toContain("useAuth");
    });

    it("password confirmation field still has v-model", () => {
        expect(src()).toContain("v-model=\"passwordConfirmation\"");
    });
});

// ---------------------------------------------------------------------------
// cart.vue
// ---------------------------------------------------------------------------

describe("cart.vue — inline class removal (T2.2)", () => {
    const src = () => read("../pages/cart.vue");

    it("has no class= on root wrapper (max-w-5xl mx-auto ...)", () => {
        expect(src()).not.toContain('class="max-w-5xl');
    });

    it("has no class= on h1 (text-3xl font-bold mb-6)", () => {
        expect(src()).not.toContain('class="text-3xl font-bold mb-6"');
    });

    it("has no class= on empty state div (text-center py-12)", () => {
        expect(src()).not.toContain('class="text-center py-12"');
    });

    it("has no class= on empty state paragraph (text-muted-foreground text-lg mb-4)", () => {
        expect(src()).not.toContain('class="text-muted-foreground text-lg mb-4"');
    });

    it("has no class= on items container (flex gap-8 items-start)", () => {
        expect(src()).not.toContain('class="flex gap-8 items-start"');
    });

    it("has no class= on inner container (flex-1)", () => {
        expect(src()).not.toContain('class="flex-1"');
    });

    it("has no class= on summary Card (w-72 shrink-0)", () => {
        expect(src()).not.toContain('class="w-72 shrink-0"');
    });

    it("has no class= on CardContent (flex flex-col gap-2)", () => {
        expect(src()).not.toContain('class="flex flex-col gap-2"');
    });

    it("has no class= on summary rows (flex justify-between ...)", () => {
        expect(src()).not.toContain('class="flex justify-between');
    });

    it("has no class= on Buttons (w-full ...)", () => {
        expect(src()).not.toContain('class="w-full mt-4"');
        expect(src()).not.toContain('class="w-full"');
    });

    // Functional logic preserved
    it("still uses useCart composable", () => {
        expect(src()).toContain("useCart");
    });

    it("still has v-for on CartItem", () => {
        expect(src()).toContain("v-for");
    });

    it("still has isAuthenticated check for checkout link", () => {
        expect(src()).toContain("isAuthenticated");
    });

    it("still has subtotal computed", () => {
        expect(src()).toContain("subtotal");
    });
});

// ---------------------------------------------------------------------------
// checkout.vue
// ---------------------------------------------------------------------------

describe("checkout.vue — inline class removal (T2.2)", () => {
    const src = () => read("../pages/checkout.vue");

    it("has no class= on h1 (text-2xl font-bold mb-6)", () => {
        expect(src()).not.toContain('class="text-2xl font-bold mb-6"');
    });

    it("has no class= on loading paragraph (text-muted-foreground italic)", () => {
        expect(src()).not.toContain('class="text-muted-foreground italic"');
    });

    it("has no class= on address section wrapper (flex flex-col gap-4 / gap-8)", () => {
        expect(src()).not.toContain('class="flex flex-col gap-4"');
        expect(src()).not.toContain('class="flex flex-col gap-8"');
    });

    it("has no class= on address section headers (mb-6)", () => {
        // The standalone mb-6 class
        expect(src()).not.toMatch(/class="mb-6"/);
    });

    it("has no class= on address headings (font-semibold mb-3)", () => {
        expect(src()).not.toContain('class="font-semibold mb-3"');
    });

    it("has no class= on address rows (flex items-start gap-3 py-2)", () => {
        expect(src()).not.toContain('class="flex items-start gap-3 py-2"');
    });

    it("has no class= on Labels (cursor-pointer leading-snug)", () => {
        expect(src()).not.toContain('class="cursor-pointer leading-snug"');
    });

    it("has no class= on DialogContent (max-w-lg)", () => {
        expect(src()).not.toContain('class="max-w-lg"');
    });

    it("has no class= on modal form (flex flex-col gap-4)", () => {
        // Note: no plain class="flex flex-col gap-4" at all
        expect(src()).not.toContain('class="flex flex-col gap-4"');
    });

    it("has no class= on modal field divs (flex flex-col gap-1.5)", () => {
        expect(src()).not.toContain('class="flex flex-col gap-1.5"');
    });

    it("has no class= on add-address Button (self-start)", () => {
        expect(src()).not.toContain('class="self-start"');
    });

    // Functional logic preserved
    it("still has handleSubmitOrder function", () => {
        expect(src()).toContain("handleSubmitOrder");
    });

    it("still has data-testid attributes for testing", () => {
        expect(src()).toContain('data-testid="submit-order-btn"');
        expect(src()).toContain('data-testid="add-address-btn"');
    });

    it("still has RadioGroup for address selection", () => {
        expect(src()).toContain("<RadioGroup");
    });

    it("still has Dialog for address modal", () => {
        expect(src()).toContain("<Dialog");
    });

    it(":class bindings are NOT removed (dynamic bindings preserved)", () => {
        // checkout.vue has no :class bindings currently, this is a placeholder
        expect(src()).toContain("useCheckout");
    });
});

// ---------------------------------------------------------------------------
// [...slug].vue
// ---------------------------------------------------------------------------

describe("[...slug].vue — inline class removal (T2.2)", () => {
    const src = () => read("../pages/[...slug].vue");

    it("has no class= on loading div (py-12 px-6 text-center text-gray-500)", () => {
        expect(src()).not.toContain('class="py-12 px-6 text-center text-gray-500"');
    });

    it("has no class= on product div (p-6)", () => {
        expect(src()).not.toContain('class="p-6"');
    });

    it("has no class= on responsive product grid (grid grid-cols-1 md:grid-cols-2 ...)", () => {
        expect(src()).not.toContain('class="grid grid-cols-1 md:grid-cols-2');
    });

    it("has no class= on primary image (w-full max-h-96 object-cover rounded-lg)", () => {
        expect(src()).not.toContain('class="w-full max-h-96 object-cover rounded-lg"');
    });

    it("has no class= on thumbnails container (flex gap-2 mt-2)", () => {
        expect(src()).not.toContain('class="flex gap-2 mt-2"');
    });

    it("has no class= on thumbnail images (w-16 h-16 object-cover ...)", () => {
        expect(src()).not.toContain('class="w-16 h-16 object-cover rounded cursor-pointer border-2"');
    });

    it("has no class= on product h1 (text-3xl font-bold text-gray-900 mb-3)", () => {
        expect(src()).not.toContain('class="text-3xl font-bold text-gray-900 mb-3"');
    });

    it("has no class= on quantity native input (w-full px-3 py-2 ...)", () => {
        expect(src()).not.toContain('class="w-full px-3 py-2 border');
    });

    it("has no class= on specifications section (mt-6)", () => {
        expect(src()).not.toContain('class="mt-6"');
    });

    it("has no class= on specs dl (grid grid-cols-[max-content ...])", () => {
        expect(src()).not.toContain('class="grid grid-cols-[max-content');
    });

    it("has no class= on review section (mt-8 pt-8 border-t border-gray-200)", () => {
        expect(src()).not.toContain('class="mt-8 pt-8 border-t border-gray-200"');
    });

    it("has no class= on review rows (py-4 border-b ...)", () => {
        expect(src()).not.toContain('class="py-4 border-b border-gray-100 last:border-b-0"');
    });

    it("has no class= on product grid (grid grid-cols-[repeat ...])", () => {
        expect(src()).not.toContain('class="grid grid-cols-[repeat(auto-fill');
    });

    it("has no class= on pagination nav (flex items-center justify-center gap-4 mt-8)", () => {
        expect(src()).not.toContain('class="flex items-center justify-center gap-4 mt-8"');
    });

    it("has no class= on 404 div (py-12 px-6 text-center text-gray-500)", () => {
        // Matches end of file 404 fallback div
        expect(src()).not.toContain('class="py-12 px-6 text-center text-gray-500"');
    });

    // Dynamic :class bindings MUST be preserved
    it("preserves :class for thumbnail active border (border-blue-500 / border-transparent)", () => {
        expect(src()).toContain("border-blue-500");
        expect(src()).toContain("border-transparent");
    });

    it("preserves :class for stock status (text-green-600 / text-red-600)", () => {
        expect(src()).toContain("text-green-600");
        expect(src()).toContain("text-red-600");
    });

    it("preserves isOutOfStock :class binding", () => {
        expect(src()).toContain(":class=\"isOutOfStock");
    });

    // Functional logic preserved
    it("still has handleAddToCart event handler", () => {
        expect(src()).toContain("handleAddToCart");
    });

    it("still has data-testid='add-to-cart' on Button", () => {
        expect(src()).toContain('data-testid="add-to-cart"');
    });

    it("still has :disabled binding on Add to Cart Button", () => {
        expect(src()).toContain(":disabled");
    });

    it("still has v-model for variant select", () => {
        expect(src()).toContain("v-model");
    });

    it("still has pagination v-if and page buttons", () => {
        expect(src()).toContain("totalPages > 1");
    });
});

// ---------------------------------------------------------------------------
// error.vue
// ---------------------------------------------------------------------------

describe("error.vue — inline class removal (T2.2)", () => {
    const src = () => read("../error.vue");

    it("has no class= on layout div (min-h-screen flex items-center ...)", () => {
        expect(src()).not.toContain('class="min-h-screen');
    });

    it("has no class= on Card (w-full max-w-md)", () => {
        expect(src()).not.toContain('class="w-full max-w-md"');
    });

    it("has no class= on error code h1 (text-6xl font-bold ...)", () => {
        expect(src()).not.toContain('class="text-6xl');
    });

    it("has no class= on title h2 (text-2xl font-semibold mb-4)", () => {
        expect(src()).not.toContain('class="text-2xl font-semibold mb-4"');
    });

    it("has no class= on description paragraph (text-gray-600 mb-6)", () => {
        expect(src()).not.toContain('class="text-gray-600 mb-6"');
    });

    it("has no class= on buttons container (flex flex-col gap-3 sm:flex-row)", () => {
        expect(src()).not.toContain('class="flex flex-col gap-3 sm:flex-row"');
    });

    it("has no class= on Go Home Button (flex-1)", () => {
        // flex-1 appears as class on Button components
        expect(src()).not.toContain('class="flex-1"');
    });

    // Functional logic preserved
    it("still has handleBack function", () => {
        expect(src()).toContain("handleBack");
    });

    it("still has router.back() call", () => {
        expect(src()).toContain("router.back()");
    });

    it("still has is404 computed property", () => {
        expect(src()).toContain("is404");
    });

    it("still uses NuxtLayout wrapper", () => {
        expect(src()).toContain("<NuxtLayout>");
    });

    it("still has variant=outline on Go Back button", () => {
        expect(src()).toContain('variant="outline"');
    });
});
