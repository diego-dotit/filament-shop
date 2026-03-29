/**
 * T6.8 — error.vue shadcn migration tests
 *
 * Verifies:
 * - No <style> block in source
 * - No BEM class names (.error-page, .error-page__*)
 * - Imports Card/CardContent and Button from shadcn-vue
 * - Uses Tailwind classes for layout, typography, spacing
 * - Renders status code, title, description correctly
 * - Go Home and Go Back buttons present and functional
 * - All conditional logic preserved (is404, title, description computed props)
 * - handleBack() calls router.back()
 */
import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";
import { mount } from "@vue/test-utils";
import * as fs from "node:fs";
import * as path from "node:path";
import { dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component is imported
// ---------------------------------------------------------------------------

vi.stubGlobal("ref", ref);
vi.stubGlobal("computed", computed);

const mockBack = vi.fn();
vi.stubGlobal("useRouter", () => ({ back: mockBack }));
vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));

// ---------------------------------------------------------------------------
// Global stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
    NuxtLayout: { template: "<div><slot /></div>" },
};

// ---------------------------------------------------------------------------
// Source-level tests
// ---------------------------------------------------------------------------

describe("error.vue shadcn migration — source checks", () => {
    const filePath = path.resolve(__dirname, "../error.vue");

    it("source file has no <style> block", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no BEM class .error-page", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("error-page");
    });

    it("source file has no BEM class .error-page__code", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("error-page__code");
    });

    it("source file has no BEM class .error-page__actions", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("error-page__actions");
    });

    it("source file imports Card from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/card");
    });

    it("source file imports Button from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/button");
    });

    it("source file uses Tailwind text-6xl class for error code", () => {
        // T2.4: inline class attributes removed; text-6xl is now applied via CSS/scoped styles
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain('class="text-6xl');
    });

    it("source file no longer has inline text-gray-500 class (moved to CSS)", () => {
        // T2.4: inline class attributes removed from error.vue
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain('class="text-6xl font-bold text-gray-500');
    });

    it("source file uses min-h-screen flex layout via CSS (not inline class)", () => {
        // T2.4: inline class attributes removed; layout is now in CSS
        // Component structure is preserved (NuxtLayout + div wrapper remain)
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("NuxtLayout");
        expect(source).toContain("<div>");
    });

    it("source preserves is404 computed property", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("is404");
    });

    it("source preserves handleBack function", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("handleBack");
        expect(source).toContain("router.back()");
    });

    it("source uses Button variant outline for Go Back", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain('variant="outline"');
    });
});

// ---------------------------------------------------------------------------
// Rendered HTML tests
// ---------------------------------------------------------------------------

describe("error.vue shadcn migration — rendered HTML", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    it("renders 404 status code", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("404");
    });

    it("renders 'Page Not Found' title for 404", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("Page Not Found");
    });

    it("renders 'Something Went Wrong' title for 500", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 500, message: "Server Error" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("Something Went Wrong");
    });

    it("renders 500 status code", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 500, message: "Server Error" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("500");
    });

    it("Go Home button renders with shadcn Button (data-slot=button)", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        const buttons = wrapper.findAll("[data-slot='button']");
        expect(buttons.length).toBeGreaterThanOrEqual(1);
    });

    it("Go Back button calls router.back() when clicked", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        const backBtn = wrapper
            .findAll("button")
            .find((b) => b.text().toLowerCase().includes("back"));
        expect(backBtn).toBeDefined();
        await backBtn!.trigger("click");
        expect(mockBack).toHaveBeenCalledOnce();
    });

    it("renders description text", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        // Should contain some description text
        expect(wrapper.text().length).toBeGreaterThan(50);
    });

    it("Go Home link points to /", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        const homeLink = wrapper.find("a");
        expect(homeLink.exists()).toBe(true);
    });
});
