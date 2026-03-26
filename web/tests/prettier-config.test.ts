import { describe, it, expect } from "vitest";
import { readFileSync, existsSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const configPath = resolve(__dirname, "../.prettierrc.json");

describe("Prettier configuration", () => {
    it("config file exists at web/.prettierrc.json", () => {
        expect(existsSync(configPath)).toBe(true);
    });

    it("config file is valid JSON", () => {
        const raw = readFileSync(configPath, "utf-8");
        expect(() => JSON.parse(raw)).not.toThrow();
    });

    it("tabWidth is set to 4", () => {
        const config = JSON.parse(readFileSync(configPath, "utf-8"));
        expect(config.tabWidth).toBe(4);
    });

    it("useTabs is explicitly set to false", () => {
        const config = JSON.parse(readFileSync(configPath, "utf-8"));
        expect(config.useTabs).toBe(false);
    });

    it("semi is set to a boolean value", () => {
        const config = JSON.parse(readFileSync(configPath, "utf-8"));
        expect(typeof config.semi).toBe("boolean");
    });

    it("singleQuote is explicitly configured", () => {
        const config = JSON.parse(readFileSync(configPath, "utf-8"));
        expect(typeof config.singleQuote).toBe("boolean");
    });

    it("trailingComma is set to a valid option", () => {
        const config = JSON.parse(readFileSync(configPath, "utf-8"));
        expect(["none", "es5", "all"]).toContain(config.trailingComma);
    });
});
