import { describe, it, expect } from "vitest";
import { readFileSync } from "fs";
import { resolve } from "path";

// ---------------------------------------------------------------------------
// Tests: account pages must have no static Tailwind class attributes
// Template-only change — verifies removal of inline class="..." attributes
// ---------------------------------------------------------------------------

const pagesDir = resolve(__dirname, "../pages/account");

function readPage(relativePath: string): string {
    return readFileSync(resolve(pagesDir, relativePath), "utf-8");
}

/**
 * Returns all static class="..." occurrences found in the <template> block.
 * Dynamic :class="..." bindings are intentionally excluded.
 */
function findStaticClassAttrs(source: string): string[] {
    // Extract template block only (between <template> and </template>)
    const templateMatch = source.match(/<template>([\s\S]*?)<\/template>/);
    if (!templateMatch) return [];
    const template = templateMatch[1];

    // Match class="..." but not :class="..."
    const matches = template.match(/(?<!\:)class="[^"]+"/g);
    return matches ?? [];
}

describe("Account pages — no static Tailwind class attributes", () => {
    it("index.vue has no static class attributes", () => {
        const source = readPage("index.vue");
        const found = findStaticClassAttrs(source);
        expect(found).toHaveLength(0);
    });

    it("edit.vue has no static class attributes", () => {
        const source = readPage("edit.vue");
        const found = findStaticClassAttrs(source);
        expect(found).toHaveLength(0);
    });

    it("dashboard.vue has no static class attributes", () => {
        const source = readPage("dashboard.vue");
        const found = findStaticClassAttrs(source);
        expect(found).toHaveLength(0);
    });

    it("addresses/new.vue has no static class attributes", () => {
        const source = readPage("addresses/new.vue");
        const found = findStaticClassAttrs(source);
        expect(found).toHaveLength(0);
    });
});
