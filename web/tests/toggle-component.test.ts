import { describe, it, expect } from "vitest";
import { existsSync, readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const toggleDir = resolve(__dirname, "../components/ui/toggle");
const toggleVuePath = resolve(toggleDir, "Toggle.vue");
const indexTsPath = resolve(toggleDir, "index.ts");

describe("Toggle component installation", () => {
    it("components/ui/toggle/ directory exists", () => {
        expect(existsSync(toggleDir)).toBe(true);
    });

    it("Toggle.vue file exists", () => {
        expect(existsSync(toggleVuePath)).toBe(true);
    });

    it("index.ts barrel file exists", () => {
        expect(existsSync(indexTsPath)).toBe(true);
    });

    it("index.ts exports Toggle component", () => {
        const raw = readFileSync(indexTsPath, "utf-8");
        expect(raw).toMatch(/export.*Toggle/);
        expect(raw).toMatch(/Toggle\.vue/);
    });

    it("index.ts exports toggleVariants with CVA", () => {
        const raw = readFileSync(indexTsPath, "utf-8");
        expect(raw).toMatch(/export const toggleVariants/);
        expect(raw).toMatch(/cva/);
    });

    it("toggleVariants includes pressed state styling (data-[state=on])", () => {
        const raw = readFileSync(indexTsPath, "utf-8");
        expect(raw).toMatch(/data-\[state=on\]/);
    });

    it("toggleVariants includes default and outline variants", () => {
        const raw = readFileSync(indexTsPath, "utf-8");
        expect(raw).toMatch(/variant/);
        expect(raw).toMatch(/default/);
        expect(raw).toMatch(/outline/);
    });

    it("toggleVariants includes size variants (default, sm, lg)", () => {
        const raw = readFileSync(indexTsPath, "utf-8");
        expect(raw).toMatch(/size/);
        // shadcn-vue generates unquoted keys: sm: "h-9..."
        expect(raw).toMatch(/\bsm\b/);
        expect(raw).toMatch(/\blg\b/);
    });

    it("Toggle.vue uses reka-ui Toggle primitive", () => {
        const raw = readFileSync(toggleVuePath, "utf-8");
        expect(raw).toMatch(/from ['"]reka-ui['"]/);
        expect(raw).toMatch(/Toggle/);
    });

    it("Toggle.vue accepts variant and size props", () => {
        const raw = readFileSync(toggleVuePath, "utf-8");
        expect(raw).toMatch(/variant/);
        expect(raw).toMatch(/size/);
    });

    it("Toggle.vue applies toggleVariants for class binding", () => {
        const raw = readFileSync(toggleVuePath, "utf-8");
        expect(raw).toMatch(/toggleVariants/);
        expect(raw).toMatch(/cn\(/);
    });

    it("index.ts exports ToggleVariants type", () => {
        const raw = readFileSync(indexTsPath, "utf-8");
        expect(raw).toMatch(/ToggleVariants/);
    });
});
