import { describe, it, expect } from "vitest";
import { readFileSync, existsSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const componentsJsonPath = resolve(__dirname, "../components.json");
const libUtilsPath = resolve(__dirname, "../lib/utils.ts");

describe("shadcn-vue configuration (components.json)", () => {
    it("components.json exists in web/ root", () => {
        expect(existsSync(componentsJsonPath)).toBe(true);
    });

    it("components.json is valid JSON", () => {
        const raw = readFileSync(componentsJsonPath, "utf-8");
        expect(() => JSON.parse(raw)).not.toThrow();
    });

    it("uses the shadcn-vue schema URL", () => {
        const json = JSON.parse(readFileSync(componentsJsonPath, "utf-8"));
        expect(json["$schema"]).toBe("https://shadcn-vue.com/schema.json");
    });

    it("has style set to default", () => {
        const json = JSON.parse(readFileSync(componentsJsonPath, "utf-8"));
        expect(json.style).toBe("default");
    });

    it("has typescript enabled", () => {
        const json = JSON.parse(readFileSync(componentsJsonPath, "utf-8"));
        expect(json.typescript).toBe(true);
    });

    it("has aliases configured with @ prefix", () => {
        const json = JSON.parse(readFileSync(componentsJsonPath, "utf-8"));
        expect(json.aliases).toBeDefined();
        expect(json.aliases.utils).toBe("@/lib/utils");
        expect(json.aliases.components).toBe("@/components");
        expect(json.aliases.ui).toBe("@/components/ui");
    });

    it("has tailwind config pointing to correct CSS file", () => {
        const json = JSON.parse(readFileSync(componentsJsonPath, "utf-8"));
        expect(json.tailwind).toBeDefined();
        expect(json.tailwind.css).toBe("assets/css/globals.css");
        expect(json.tailwind.cssVariables).toBe(true);
    });
});

describe("lib/utils.ts (cn helper)", () => {
    it("lib/utils.ts exists", () => {
        expect(existsSync(libUtilsPath)).toBe(true);
    });

    it("lib/utils.ts exports cn function", () => {
        const raw = readFileSync(libUtilsPath, "utf-8");
        expect(raw).toMatch(/export function cn/);
    });

    it("lib/utils.ts imports from clsx and tailwind-merge", () => {
        const raw = readFileSync(libUtilsPath, "utf-8");
        expect(raw).toMatch(/from 'clsx'/);
        expect(raw).toMatch(/from 'tailwind-merge'/);
    });

    it("lib/utils.ts uses twMerge and clsx in cn function body", () => {
        const raw = readFileSync(libUtilsPath, "utf-8");
        expect(raw).toMatch(/twMerge/);
        expect(raw).toMatch(/clsx/);
    });
});
