import { describe, it, expect } from "vitest";
import { existsSync, readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const uiDir = resolve(__dirname, "../components/ui");

// ── Pagination ──────────────────────────────────────────────────────────────
describe("shadcn-vue pagination component", () => {
    const paginationDir = resolve(uiDir, "pagination");

    it("pagination/ directory exists", () => {
        expect(existsSync(paginationDir)).toBe(true);
    });

    const requiredFiles = [
        "Pagination.vue",
        "PaginationContent.vue",
        "PaginationEllipsis.vue",
        "PaginationFirst.vue",
        "PaginationLast.vue",
        "PaginationLink.vue",
        "PaginationNext.vue",
        "PaginationPrevious.vue",
        "index.ts",
    ];

    for (const file of requiredFiles) {
        it(`pagination/${file} exists`, () => {
            expect(existsSync(resolve(paginationDir, file))).toBe(true);
        });
    }

    it("index.ts exports Pagination", () => {
        const raw = readFileSync(resolve(paginationDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/export.*Pagination/);
    });

    it("index.ts exports PaginationNext and PaginationPrevious", () => {
        const raw = readFileSync(resolve(paginationDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/PaginationNext/);
        expect(raw).toMatch(/PaginationPrevious/);
    });

    it("Pagination.vue uses reka-ui PaginationRoot", () => {
        const raw = readFileSync(
            resolve(paginationDir, "Pagination.vue"),
            "utf-8",
        );
        expect(raw).toMatch(/reka-ui/);
        expect(raw).toMatch(/PaginationRoot/);
    });
});

// ── Select ───────────────────────────────────────────────────────────────────
describe("shadcn-vue select component", () => {
    const selectDir = resolve(uiDir, "select");

    it("select/ directory exists", () => {
        expect(existsSync(selectDir)).toBe(true);
    });

    const requiredFiles = [
        "Select.vue",
        "SelectContent.vue",
        "SelectItem.vue",
        "SelectTrigger.vue",
        "SelectValue.vue",
        "index.ts",
    ];

    for (const file of requiredFiles) {
        it(`select/${file} exists`, () => {
            expect(existsSync(resolve(selectDir, file))).toBe(true);
        });
    }

    it("index.ts exports Select", () => {
        const raw = readFileSync(resolve(selectDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/export.*Select/);
    });

    it("index.ts exports SelectTrigger and SelectValue", () => {
        const raw = readFileSync(resolve(selectDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/SelectTrigger/);
        expect(raw).toMatch(/SelectValue/);
    });

    it("Select.vue uses reka-ui SelectRoot", () => {
        const raw = readFileSync(resolve(selectDir, "Select.vue"), "utf-8");
        expect(raw).toMatch(/reka-ui/);
        expect(raw).toMatch(/SelectRoot/);
    });
});
