import { describe, it, expect } from "vitest";
import { readFileSync, existsSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const numberFieldDir = resolve(__dirname, "../components/ui/number-field");

describe("NumberField shadcn-vue component installation", () => {
    it("number-field directory exists", () => {
        expect(existsSync(numberFieldDir)).toBe(true);
    });

    it("NumberField.vue exists", () => {
        expect(existsSync(resolve(numberFieldDir, "NumberField.vue"))).toBe(true);
    });

    it("NumberFieldContent.vue exists", () => {
        expect(existsSync(resolve(numberFieldDir, "NumberFieldContent.vue"))).toBe(true);
    });

    it("NumberFieldDecrement.vue exists", () => {
        expect(existsSync(resolve(numberFieldDir, "NumberFieldDecrement.vue"))).toBe(true);
    });

    it("NumberFieldIncrement.vue exists", () => {
        expect(existsSync(resolve(numberFieldDir, "NumberFieldIncrement.vue"))).toBe(true);
    });

    it("NumberFieldInput.vue exists", () => {
        expect(existsSync(resolve(numberFieldDir, "NumberFieldInput.vue"))).toBe(true);
    });

    it("index.ts barrel export exists", () => {
        expect(existsSync(resolve(numberFieldDir, "index.ts"))).toBe(true);
    });

    it("index.ts exports all five components", () => {
        const raw = readFileSync(resolve(numberFieldDir, "index.ts"), "utf-8");
        expect(raw).toMatch(/export.*NumberField/);
        expect(raw).toMatch(/export.*NumberFieldContent/);
        expect(raw).toMatch(/export.*NumberFieldDecrement/);
        expect(raw).toMatch(/export.*NumberFieldIncrement/);
        expect(raw).toMatch(/export.*NumberFieldInput/);
    });

    it("NumberField.vue uses reka-ui NumberFieldRoot", () => {
        const raw = readFileSync(resolve(numberFieldDir, "NumberField.vue"), "utf-8");
        expect(raw).toMatch(/reka-ui/);
        expect(raw).toMatch(/NumberFieldRoot/);
    });

    it("NumberFieldDecrement.vue renders a Minus icon by default", () => {
        const raw = readFileSync(resolve(numberFieldDir, "NumberFieldDecrement.vue"), "utf-8");
        expect(raw).toMatch(/Minus/);
    });

    it("NumberFieldIncrement.vue renders a Plus icon by default", () => {
        const raw = readFileSync(resolve(numberFieldDir, "NumberFieldIncrement.vue"), "utf-8");
        expect(raw).toMatch(/Plus/);
    });

    it("NumberFieldInput.vue uses reka-ui NumberFieldInput", () => {
        const raw = readFileSync(resolve(numberFieldDir, "NumberFieldInput.vue"), "utf-8");
        expect(raw).toMatch(/reka-ui/);
        expect(raw).toMatch(/NumberFieldInput/);
    });

    it("components use cn utility for class merging", () => {
        const numberFieldVue = readFileSync(resolve(numberFieldDir, "NumberField.vue"), "utf-8");
        expect(numberFieldVue).toMatch(/cn\(/);
    });
});
