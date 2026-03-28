/**
 * T1.2: Verify orders list route is served by orders/index.vue (sibling pattern)
 *
 * After restructuring:
 *   - web/pages/account/orders/index.vue  ← orders listing (NEW location)
 *   - web/pages/account/orders/[id].vue   ← order detail (unchanged)
 *   - web/pages/account/orders.vue        ← must NOT exist
 */

import { describe, it, expect } from "vitest";
import { existsSync } from "node:fs";
import { resolve } from "node:path";

const pagesRoot = resolve(__dirname, "../pages/account/orders");

describe("Order route file structure", () => {
    it("orders/index.vue exists at the new sibling location", () => {
        const newFile = resolve(pagesRoot, "index.vue");
        expect(existsSync(newFile)).toBe(true);
    });

    it("original orders.vue no longer exists (no parent-layout conflict)", () => {
        const oldFile = resolve(__dirname, "../pages/account/orders.vue");
        expect(existsSync(oldFile)).toBe(false);
    });

    it("[id].vue still exists alongside index.vue", () => {
        const detailFile = resolve(pagesRoot, "[id].vue");
        expect(existsSync(detailFile)).toBe(true);
    });
});

