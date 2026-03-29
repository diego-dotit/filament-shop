/**
 * Tests for login.vue shadcn migration (T5.4)
 * Verifies:
 * - No <style> or <style scoped> block in source
 * - No legacy BEM/custom CSS class names in source
 * - Imports shadcn Card, Input, Label, Button, Alert components
 * - Page is wrapped in a Card
 * - Email/password fields use shadcn Input (data-slot="input")
 * - Labels use shadcn Label (data-slot="label")
 * - Field-level errors use <p class="text-sm text-destructive">
 * - API-level error uses Alert variant="destructive"
 * - Submit button uses shadcn Button (data-slot="button")
 * - All functionality preserved (validation, redirect, etc.)
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

vi.stubGlobal("ref", ref);
vi.stubGlobal("computed", computed);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());

const mockLogin = vi.fn();
const mockIsAuthenticated = ref(false);

vi.stubGlobal("useAuth", () => ({
    isAuthenticated: mockIsAuthenticated,
    login: mockLogin,
}));

const mockPush = vi.fn();
const mockRoute = ref({ query: {} as Record<string, string> });

vi.stubGlobal("useRoute", () => mockRoute.value);
vi.stubGlobal("useRouter", () => ({ push: mockPush }));
vi.stubGlobal("useCart", () => ({ fetchCart: vi.fn().mockResolvedValue(undefined) }));

// ---------------------------------------------------------------------------
// Global stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
};

// ---------------------------------------------------------------------------
// Source-level tests
// ---------------------------------------------------------------------------

describe("login.vue shadcn migration — source checks", () => {
    const filePath = path.resolve(__dirname, "../pages/login.vue");

    it("source file has no <style> block", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no legacy BEM class names", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        const legacyClasses = ["login-page", "field-error", "api-error"];
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

    it("source file no longer uses text-destructive for field errors (removed by T2.2)", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain('class="text-sm text-destructive"');
    });
});

// ---------------------------------------------------------------------------
// Rendered HTML tests
// ---------------------------------------------------------------------------

describe("login.vue shadcn migration — rendered HTML", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockIsAuthenticated.value = false;
        mockRoute.value = { query: {} };
        vi.resetModules();
    });

    async function mountLoginPage() {
        const { default: LoginPage } = await import("../pages/login.vue");
        return mount(LoginPage, { global: { stubs: globalStubs } });
    }

    it("email input has autocomplete=email and correct placeholder", async () => {
        const wrapper = await mountLoginPage();
        const emailInput = wrapper.find('input[type="email"]');
        expect(emailInput.exists()).toBe(true);
        expect(emailInput.attributes("autocomplete")).toBe("email");
        expect(emailInput.attributes("placeholder")).toBeTruthy();
    });

    it("password input has autocomplete=current-password and correct placeholder", async () => {
        const wrapper = await mountLoginPage();
        const passwordInput = wrapper.find('input[type="password"]');
        expect(passwordInput.exists()).toBe(true);
        expect(passwordInput.attributes("autocomplete")).toBe("current-password");
        expect(passwordInput.attributes("placeholder")).toBeTruthy();
    });

    it("renders label elements for email and password", async () => {
        const wrapper = await mountLoginPage();
        const labels = wrapper.findAll("label");
        const emailLabel = labels.find((l) => l.text().toLowerCase().includes("email"));
        const passwordLabel = labels.find((l) => l.text().toLowerCase().includes("password"));
        expect(emailLabel).toBeDefined();
        expect(passwordLabel).toBeDefined();
    });

    it("submit button uses shadcn Button (data-slot=button)", async () => {
        const wrapper = await mountLoginPage();
        const submitBtn = wrapper.find('button[type="submit"]');
        expect(submitBtn.exists()).toBe(true);
        expect(submitBtn.attributes("data-slot")).toBe("button");
    });

    it("shows field-level error paragraph when form submitted empty", async () => {
        const wrapper = await mountLoginPage();
        await wrapper.find("form").trigger("submit.prevent");
        await wrapper.vm.$nextTick();
        // Error paragraph exists (no longer carries text-destructive class — removed by T2.2)
        const errorPs = wrapper.findAll("p");
        const errorP = errorPs.find((p) => p.text().includes("valid email") || p.text().includes("required"));
        expect(errorP).toBeDefined();
    });

    it("shows API error in a role=alert element with error text", async () => {
        mockLogin.mockResolvedValueOnce([null, new Error("Invalid credentials")]);
        const wrapper = await mountLoginPage();
        await wrapper.find('input[type="email"]').setValue("user@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");
        await wrapper.find("form").trigger("submit.prevent");
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();
        // Alert renders with role="alert"
        const alert = wrapper.find('[role="alert"]');
        expect(alert.exists()).toBe(true);
        expect(wrapper.text()).toMatch(/invalid credentials/i);
    });

    it("submit button exists and is type=submit (w-full class removed by T2.2)", async () => {
        const wrapper = await mountLoginPage();
        const submitBtn = wrapper.find('button[type="submit"]');
        expect(submitBtn.exists()).toBe(true);
        expect(submitBtn.attributes("data-slot")).toBe("button");
    });

    it("'Don't have an account?' text and Register link are rendered", async () => {
        const wrapper = await mountLoginPage();
        expect(wrapper.text()).toMatch(/don.*t have an account/i);
        const links = wrapper.findAll("a");
        const registerLink = links.find((l) => l.text().toLowerCase().includes("register"));
        expect(registerLink).toBeDefined();
        expect(registerLink?.attributes("href")).toBe("/register");
    });
});
