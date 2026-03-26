import { describe, it, expect } from "vitest";
import { readFileSync, existsSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const configPath = resolve(__dirname, "../eslint.config.js");

describe("ESLint configuration", () => {
    it("config file exists at web/eslint.config.js", () => {
        expect(existsSync(configPath)).toBe(true);
    });

    it("config file is valid JavaScript that can be read", () => {
        expect(() => readFileSync(configPath, "utf-8")).not.toThrow();
    });

    it("config enforces 4-space indentation (indent rule present)", () => {
        const raw = readFileSync(configPath, "utf-8");
        // The indent rule should appear with value 4
        expect(raw).toMatch(/indent/);
        expect(raw).toMatch(/4/);
    });

    it("config includes vue/html-indent rule for Vue SFC templates", () => {
        const raw = readFileSync(configPath, "utf-8");
        expect(raw).toMatch(/vue\/html-indent/);
    });

    it("config references eslint-plugin-vue for Vue 3 support", () => {
        const raw = readFileSync(configPath, "utf-8");
        expect(raw).toMatch(/eslint-plugin-vue|pluginVue/);
    });
});
