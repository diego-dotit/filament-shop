/**
 * T2.4 — Remove inline Tailwind class attributes from layout and root files
 *
 * Verifies:
 * - /web/layouts/default.vue has no static class="" attributes
 * - /web/app.vue has no static class="" attributes
 * - /web/error.vue has no static class="" attributes
 * - Component hierarchy and slot structure remain intact
 */
import { describe, it, expect } from "vitest";
import * as fs from "node:fs";
import * as path from "node:path";
import { dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, "..");

// ---------------------------------------------------------------------------
// Helper: check that a file has no static class="..." attributes
// Dynamic :class bindings are allowed.
// ---------------------------------------------------------------------------
function getStaticClassMatches(source: string): RegExpMatchArray | null {
    // Match class="..." but NOT :class="..."
    return source.match(/(?<!:)class="[^"]+"/g);
}

// ---------------------------------------------------------------------------
// /web/layouts/default.vue
// ---------------------------------------------------------------------------

describe("layouts/default.vue — no inline class attributes", () => {
    const filePath = path.resolve(root, "layouts/default.vue");
    const source = fs.readFileSync(filePath, "utf-8");

    it("has no static class= attribute on the root div", () => {
        const matches = getStaticClassMatches(source);
        expect(matches).toBeNull();
    });

    it("does not contain flex flex-col min-h-screen font-sans inline", () => {
        expect(source).not.toContain('class="flex flex-col min-h-screen font-sans"');
    });

    it("does not contain flex-1 max-w-7xl w-full mx-auto inline", () => {
        expect(source).not.toContain('class="flex-1 max-w-7xl w-full mx-auto px-6 py-8"');
    });

    it("still contains <Header /> component", () => {
        expect(source).toContain("<Header");
    });

    it("still contains <Footer /> component", () => {
        expect(source).toContain("<Footer");
    });

    it("still contains <slot /> for page content", () => {
        expect(source).toContain("<slot");
    });

    it("still contains <main> element wrapping the slot", () => {
        expect(source).toContain("<main");
    });
});

// ---------------------------------------------------------------------------
// /web/app.vue
// ---------------------------------------------------------------------------

describe("app.vue — no inline class attributes", () => {
    const filePath = path.resolve(root, "app.vue");
    const source = fs.readFileSync(filePath, "utf-8");

    it("has no static class= attribute", () => {
        const matches = getStaticClassMatches(source);
        expect(matches).toBeNull();
    });

    it("does not contain min-h-screen inline class", () => {
        expect(source).not.toContain('class="min-h-screen"');
    });

    it("still contains <NuxtLayout>", () => {
        expect(source).toContain("NuxtLayout");
    });

    it("still contains <NuxtPage />", () => {
        expect(source).toContain("NuxtPage");
    });
});

// ---------------------------------------------------------------------------
// /web/error.vue
// ---------------------------------------------------------------------------

describe("error.vue — no inline class attributes", () => {
    const filePath = path.resolve(root, "error.vue");
    const source = fs.readFileSync(filePath, "utf-8");

    it("has no static class= attribute", () => {
        const matches = getStaticClassMatches(source);
        expect(matches).toBeNull();
    });

    it("still contains handleBack function", () => {
        expect(source).toContain("handleBack");
    });

    it("still contains is404 computed property", () => {
        expect(source).toContain("is404");
    });

    it("still imports Card from shadcn-vue", () => {
        expect(source).toContain("@/components/ui/card");
    });

    it("still imports Button from shadcn-vue", () => {
        expect(source).toContain("@/components/ui/button");
    });

    it("still uses Button variant outline for Go Back", () => {
        expect(source).toContain('variant="outline"');
    });

    it("still contains NuxtLayout wrapper", () => {
        expect(source).toContain("NuxtLayout");
    });
});
