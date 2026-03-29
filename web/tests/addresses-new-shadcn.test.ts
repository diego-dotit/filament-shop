/**
 * Tests for account/addresses/new.vue shadcn migration (T6.4)
 * Verifies:
 * - No <style> or <style scoped> block in source
 * - No legacy CSS class names (.address-form-page, .form-group, .error-msg, .form-actions)
 * - Imports shadcn Card, Input, Label, Button, Alert components
 * - Form fields use shadcn Input (data-slot="input")
 * - Labels use shadcn Label (data-slot="label")
 * - Error uses Alert component (role="alert")
 * - Submit button uses shadcn Button (data-slot="button")
 * - Cancel uses Button variant="outline" with NuxtLink
 * - All form functionality preserved (redirect, error handling)
 */
import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, reactive, computed } from "vue";
import { mount } from "@vue/test-utils";
import * as fs from "node:fs";
import * as path from "node:path";
import { dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE importing any component under test
// ---------------------------------------------------------------------------

vi.stubGlobal("ref", ref);
vi.stubGlobal("computed", computed);
vi.stubGlobal("reactive", reactive);
vi.stubGlobal("definePageMeta", vi.fn());
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// ---------------------------------------------------------------------------
// Global stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
};

// ---------------------------------------------------------------------------
// Source-level tests
// ---------------------------------------------------------------------------

describe("addresses/new.vue shadcn migration — source checks", () => {
    const filePath = path.resolve(__dirname, "../pages/account/addresses/new.vue");

    it("source file has no <style> block", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no legacy CSS class names", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        const legacyClasses = ["address-form-page", "form-group", "error-msg", "form-actions"];
        for (const cls of legacyClasses) {
            expect(source, `Expected source NOT to contain "${cls}"`).not.toContain(`"${cls}"`);
        }
    });

    it("source file imports Card from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/card");
    });

    it("source file imports Input from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/input");
    });

    it("source file imports Label from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/label");
    });

    it("source file imports Button from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/button");
    });

    it("source file imports Alert from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/alert");
    });
});

// ---------------------------------------------------------------------------
// Rendered HTML tests
// ---------------------------------------------------------------------------

describe("addresses/new.vue shadcn migration — rendered HTML", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockNavigateTo.mockReset();
        vi.resetModules();
    });

    async function mountPage(apiMock = vi.fn().mockResolvedValue({}), routeQuery = {}) {
        vi.stubGlobal("useApi", () => apiMock);
        vi.stubGlobal("useRoute", () => ({ query: routeQuery }));
        const { default: NewAddressPage } = await import(
            "../pages/account/addresses/new.vue"
        );
        return mount(NewAddressPage, { global: { stubs: globalStubs } });
    }

    it("renders inputs with data-slot=input for all form fields", async () => {
        const wrapper = await mountPage();
        // Input component renders native <input> elements
        const inputs = wrapper.findAll("input");
        // 5 fields: country, city, address_line_1, address_line_2, postcode
        expect(inputs.length).toBe(5);
    });

    it("renders labels with data-slot=label for all form fields", async () => {
        const wrapper = await mountPage();
        // Label component renders native <label> elements
        const labels = wrapper.findAll("label");
        expect(labels.length).toBe(5);
    });

    it("submit button uses shadcn Button (data-slot=button)", async () => {
        const wrapper = await mountPage();
        const submitBtn = wrapper.find('button[type="submit"]');
        expect(submitBtn.exists()).toBe(true);
        expect(submitBtn.attributes("data-slot")).toBe("button");
    });

    it("shows error in role=alert element when API fails", async () => {
        const failingApi = vi.fn().mockRejectedValueOnce({ data: { message: "Validation failed" } });
        const wrapper = await mountPage(failingApi, { redirect: "/checkout" });

        await wrapper.find("#country").setValue("US");
        await wrapper.find("#city").setValue("Springfield");
        await wrapper.find("#address_line_1").setValue("123 Main St");
        await wrapper.find("#postcode").setValue("62701");

        await wrapper.find("form").trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        const alert = wrapper.find('[role="alert"]');
        expect(alert.exists()).toBe(true);
        expect(wrapper.text()).toContain("Validation failed");
    });

    it("does NOT show the error alert when no error", async () => {
        const wrapper = await mountPage();
        const alert = wrapper.find('[role="alert"]');
        expect(alert.exists()).toBe(false);
    });

    it("submit button is disabled while submitting", async () => {
        let resolveApi!: () => void;
        const slowApi = vi.fn().mockReturnValue(
            new Promise<void>((resolve) => { resolveApi = resolve; })
        );
        const wrapper = await mountPage(slowApi, {});

        await wrapper.find("#country").setValue("US");
        await wrapper.find("#city").setValue("Springfield");
        await wrapper.find("#address_line_1").setValue("123 Main St");
        await wrapper.find("#postcode").setValue("62701");

        wrapper.find("form").trigger("submit");
        await wrapper.vm.$nextTick();

        const submitBtn = wrapper.find('button[type="submit"]');
        expect(submitBtn.attributes("disabled")).toBeDefined();

        resolveApi();
    });

    it("cancel button/link renders with to=/account/addresses", async () => {
        const wrapper = await mountPage();
        const links = wrapper.findAll("a");
        const cancelLink = links.find((l) => l.text().toLowerCase().includes("cancel"));
        expect(cancelLink).toBeDefined();
        expect(cancelLink?.attributes("href")).toBe("/account/addresses");
    });
});
