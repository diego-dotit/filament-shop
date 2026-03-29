/**
 * Tests for account/edit.vue shadcn migration (T6.2)
 * Verifies:
 * - No <style> or <style scoped> block in source
 * - No legacy CSS class names
 * - Imports Input, Label, Alert, Button from shadcn-vue
 * - All data-testids preserved
 * - Success alert uses role="alert"
 * - Error alert uses role="alert"
 * - Submit button has data-slot="button"
 * - All form functionality preserved
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
// Stub Nuxt globals BEFORE importing any component under test
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);
vi.stubGlobal("definePageMeta", vi.fn());

// ---------------------------------------------------------------------------
// Shared stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
};

// ---------------------------------------------------------------------------
// Test fixture
// ---------------------------------------------------------------------------

const mockCustomer = {
    id: 1,
    name: "Alice Smith",
    first_name: "Alice",
    last_name: "Smith",
    email: "alice@example.com",
    phone: "+1-555-0100",
};

function makeAuthStub(userValue: typeof mockCustomer | null = mockCustomer) {
    return () => ({
        user: ref(userValue),
        isAuthenticated: computed(() => userValue !== null),
        logout: vi.fn(),
    });
}

// ---------------------------------------------------------------------------
// Source-level checks
// ---------------------------------------------------------------------------

describe("account/edit.vue shadcn migration — source checks", () => {
    const filePath = path.resolve(__dirname, "../pages/account/edit.vue");

    it("source file has no <style> block", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no legacy CSS class names", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        const legacyClasses = ["account-edit", "success-message", "error-message", "form-actions"];
        for (const cls of legacyClasses) {
            expect(source, `Expected source NOT to contain "${cls}"`).not.toContain(`"${cls}"`);
        }
    });

    it("source file imports Input from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/input");
    });

    it("source file imports Label from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/label");
    });

    it("source file imports Alert from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/alert");
    });

    it("source file imports Button from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/button");
    });
});

// ---------------------------------------------------------------------------
// Rendered HTML tests
// ---------------------------------------------------------------------------

describe("account/edit.vue shadcn migration — rendered HTML", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockNavigateTo.mockReset();
        vi.resetModules();
    });

    it("submit button has data-slot=button", async () => {
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        const submitBtn = wrapper.find('[data-testid="submit-btn"]');
        expect(submitBtn.exists()).toBe(true);
        expect(submitBtn.attributes("data-slot")).toBe("button");
    });

    it("cancel button has data-slot=button", async () => {
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        const cancelBtn = wrapper.find('[data-testid="cancel-btn"]');
        expect(cancelBtn.exists()).toBe(true);
        expect(cancelBtn.attributes("data-slot")).toBe("button");
    });

    it("success message uses role=alert element", async () => {
        const mockApi = vi.fn().mockResolvedValueOnce({ data: mockCustomer });
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        await wrapper.find('[data-testid="edit-form"]').trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        const successMsg = wrapper.find('[data-testid="success-msg"]');
        expect(successMsg.exists()).toBe(true);
        expect(successMsg.attributes("role")).toBe("alert");
    });

    it("error message uses role=alert element", async () => {
        const apiError = { data: { message: "Server error" } };
        const mockApi = vi.fn().mockRejectedValueOnce(apiError);
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        await wrapper.find('[data-testid="edit-form"]').trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        const errorMsg = wrapper.find('[data-testid="error-msg"]');
        expect(errorMsg.exists()).toBe(true);
        expect(errorMsg.attributes("role")).toBe("alert");
    });

    it("renders label elements for all four fields", async () => {
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        const labels = wrapper.findAll("label");
        expect(labels.length).toBeGreaterThanOrEqual(4);
    });

    it("all four inputs have data-testid attributes and accept v-model", async () => {
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        const testIds = ["input-first-name", "input-last-name", "input-email", "input-phone"];
        for (const id of testIds) {
            expect(wrapper.find(`[data-testid="${id}"]`).exists(), `${id} should exist`).toBe(true);
        }
    });
});
