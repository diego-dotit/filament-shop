import { describe, it, expect } from "vitest";
import { readFileSync, existsSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const alertDir = resolve(__dirname, "../components/ui/alert");

describe("Alert component installation", () => {
    it("Alert.vue exists in components/ui/alert/", () => {
        expect(existsSync(resolve(alertDir, "Alert.vue"))).toBe(true);
    });

    it("AlertDescription.vue exists in components/ui/alert/", () => {
        expect(existsSync(resolve(alertDir, "AlertDescription.vue"))).toBe(true);
    });

    it("AlertTitle.vue exists in components/ui/alert/", () => {
        expect(existsSync(resolve(alertDir, "AlertTitle.vue"))).toBe(true);
    });

    it("index.ts exists in components/ui/alert/", () => {
        expect(existsSync(resolve(alertDir, "index.ts"))).toBe(true);
    });
});

describe("Alert component barrel export (index.ts)", () => {
    it("exports Alert component", () => {
        const raw = readFileSync(resolve(alertDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/export.*Alert.*from.*Alert\.vue/);
    });

    it("exports AlertDescription component", () => {
        const raw = readFileSync(resolve(alertDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/export.*AlertDescription.*from.*AlertDescription\.vue/);
    });

    it("exports AlertTitle component", () => {
        const raw = readFileSync(resolve(alertDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/export.*AlertTitle.*from.*AlertTitle\.vue/);
    });

    it("exports alertVariants function using cva", () => {
        const raw = readFileSync(resolve(alertDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/export const alertVariants/);
        expect(raw).toMatch(/cva\(/);
    });

    it("has 'default' variant", () => {
        const raw = readFileSync(resolve(alertDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/default:/);
    });

    it("has 'destructive' variant", () => {
        const raw = readFileSync(resolve(alertDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/destructive:/);
    });

    it("exports AlertVariants type", () => {
        const raw = readFileSync(resolve(alertDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/export type AlertVariants/);
    });
});

describe("Alert.vue component structure", () => {
    it("renders with role='alert' for accessibility", () => {
        const raw = readFileSync(resolve(alertDir, "Alert.vue"), "utf-8");
        expect(raw).toMatch(/role="alert"/);
    });

    it("applies alertVariants via cn helper", () => {
        const raw = readFileSync(resolve(alertDir, "Alert.vue"), "utf-8");
        expect(raw).toMatch(/alertVariants/);
        expect(raw).toMatch(/cn\(/);
    });

    it("accepts variant prop", () => {
        const raw = readFileSync(resolve(alertDir, "Alert.vue"), "utf-8");
        expect(raw).toMatch(/variant\?/);
    });

    it("uses slot for content projection", () => {
        const raw = readFileSync(resolve(alertDir, "Alert.vue"), "utf-8");
        expect(raw).toMatch(/<slot/);
    });
});

describe("AlertTitle.vue component structure", () => {
    it("renders as h5 heading element", () => {
        const raw = readFileSync(resolve(alertDir, "AlertTitle.vue"), "utf-8");
        expect(raw).toMatch(/<h5/);
    });

    it("uses slot for content projection", () => {
        const raw = readFileSync(resolve(alertDir, "AlertTitle.vue"), "utf-8");
        expect(raw).toMatch(/<slot/);
    });
});

describe("AlertDescription.vue component structure", () => {
    it("renders as div element", () => {
        const raw = readFileSync(resolve(alertDir, "AlertDescription.vue"), "utf-8");
        expect(raw).toMatch(/<div/);
    });

    it("uses slot for content projection", () => {
        const raw = readFileSync(resolve(alertDir, "AlertDescription.vue"), "utf-8");
        expect(raw).toMatch(/<slot/);
    });
});
