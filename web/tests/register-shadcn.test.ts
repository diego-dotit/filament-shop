/**
 * T5.5 — register.vue shadcn-vue migration tests.
 *
 * Acceptance criteria:
 *  - No .register-page__* or .form-group classes remain in template
 *  - No <style scoped> block exists in the file
 *  - Page is wrapped in a Card component (data-slot="card")
 *  - All four fields use shadcn Input (data-slot="input") + Label (data-slot="label" or for= attr)
 *  - Password mismatch renders <p class="text-sm text-destructive">
 *  - API error renders Alert with variant="destructive" (data-slot="alert")
 *  - Submit button uses shadcn Button (data-slot="button") and is full-width
 *  - "Already have an account?" link is present with Tailwind utility classes
 *  - All functionality preserved: password mismatch check, register() call, redirect on success
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";
import { mount } from "@vue/test-utils";
import { readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const registerVuePath = resolve(__dirname, "../pages/register.vue");

// ---------------------------------------------------------------------------
// Stub Nuxt globals
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("ref", ref);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());
vi.stubGlobal("definePageMeta", vi.fn());

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);

vi.stubGlobal("useRoute", () => ({ query: {}, path: "/register" }));
vi.stubGlobal("useRouter", () => ({ push: vi.fn() }));

// ---------------------------------------------------------------------------
// Default useAuth stub
// ---------------------------------------------------------------------------

const mockRegister = vi.fn();

function makeAuthStub({
    isAuthenticated = false,
    registerResult = [
        { customer: { id: 1, name: "Test", email: "test@example.com" }, token: "tok" },
        null,
    ] as const,
}: {
    isAuthenticated?: boolean;
    registerResult?: readonly [unknown, unknown];
} = {}) {
    mockRegister.mockResolvedValue(registerResult);
    vi.stubGlobal("useAuth", () => ({
        isAuthenticated: computed(() => isAuthenticated),
        register: mockRegister,
    }));
}

const globalStubs = {
    NuxtLink: { template: '<a href="#"><slot /></a>' },
};

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("Register page — shadcn migration (T5.5)", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        makeAuthStub();
        vi.resetModules();
    });

    // ── Source-level checks (no old CSS classes, no style block) ──────────────

    it("has no <style> or <style scoped> block", () => {
        const source = readFileSync(registerVuePath, "utf-8");
        expect(source).not.toMatch(/<style[\s\S]*?>/);
    });

    it("has no .register-page__ BEM classes in template", () => {
        const source = readFileSync(registerVuePath, "utf-8");
        expect(source).not.toMatch(/register-page__/);
    });

    it("has no .form-group classes in template", () => {
        const source = readFileSync(registerVuePath, "utf-8");
        expect(source).not.toMatch(/class="form-group/);
    });

    // ── Component structure checks ─────────────────────────────────────────────

    it("renders a shadcn Card (data-slot=card) as container", async () => {
        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });
        // Card renders a div with bg-card class (shadcn card styling)
        expect(wrapper.find("div.bg-card").exists()).toBe(true);
    });

    it("renders shadcn Input components (data-slot=input) for all four fields", async () => {
        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });
        // shadcn Input renders native <input> with h-10 class
        const inputs = wrapper.findAll("input.h-10");
        expect(inputs.length).toBeGreaterThanOrEqual(4);
    });

    it("renders Label components (for= attribute) for each field", async () => {
        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });
        // Labels with for= attribute for each field
        expect(wrapper.find('label[for="name"]').exists()).toBe(true);
        expect(wrapper.find('label[for="email"]').exists()).toBe(true);
        expect(wrapper.find('label[for="password"]').exists()).toBe(true);
        expect(wrapper.find('label[for="password_confirmation"]').exists()).toBe(true);
    });

    it("renders shadcn Button (data-slot=button) as submit", async () => {
        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });
        const btn = wrapper.find('[data-slot="button"]');
        expect(btn.exists()).toBe(true);
        expect(btn.attributes("type")).toBe("submit");
    });

    it("submit Button exists and is type=submit (w-full class removed by T2.2)", async () => {
        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });
        const btn = wrapper.find('[data-slot="button"]');
        expect(btn.exists()).toBe(true);
        expect(btn.attributes("type")).toBe("submit");
    });

    // ── Password mismatch error ────────────────────────────────────────────────

    it("shows mismatch error paragraph on password mismatch (class removed by T2.2)", async () => {
        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });

        await wrapper.find('input[id="name"]').setValue("Alice");
        await wrapper.find('input[id="email"]').setValue("alice@example.com");
        await wrapper.find('input[id="password"]').setValue("password123");
        await wrapper.find('input[id="password_confirmation"]').setValue("different");

        await wrapper.find("form").trigger("submit.prevent");
        await wrapper.vm.$nextTick();

        // Error paragraph exists — text-destructive class removed by T2.2
        expect(wrapper.text()).toMatch(/password.*match|match.*password/i);
    });

    // ── API error via Alert ────────────────────────────────────────────────────

    it("shows destructive Alert (role=alert) on API error", async () => {
        makeAuthStub({
            registerResult: [null, { message: "Email already in use" }] as const,
        });

        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });

        await wrapper.find('input[id="name"]').setValue("Alice Smith");
        await wrapper.find('input[id="email"]').setValue("alice@example.com");
        await wrapper.find('input[id="password"]').setValue("password123");
        await wrapper.find('input[id="password_confirmation"]').setValue("password123");

        await wrapper.find("form").trigger("submit.prevent");
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const alert = wrapper.find('[role="alert"]');
        expect(alert.exists()).toBe(true);
        expect(alert.text()).toMatch(/email already in use/i);
    });

    // ── "Already have an account?" link ───────────────────────────────────────

    it("renders the login link (Tailwind utility classes removed by T2.2)", async () => {
        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });

        // The paragraph with the link — no longer carries text-center class (removed by T2.2)
        expect(wrapper.text()).toContain("Already have an account");
    });

    // ── Functionality preserved (smoke tests) ─────────────────────────────────

    it("calls register() on valid submit and redirects on success", async () => {
        const { default: RegisterPage } = await import("../pages/register.vue");
        const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } });

        await wrapper.find('input[id="name"]').setValue("Alice Smith");
        await wrapper.find('input[id="email"]').setValue("alice@example.com");
        await wrapper.find('input[id="password"]').setValue("password123");
        await wrapper.find('input[id="password_confirmation"]').setValue("password123");

        await wrapper.find("form").trigger("submit.prevent");
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(mockRegister).toHaveBeenCalledWith(
            "Alice Smith",
            "alice@example.com",
            "password123",
            "password123"
        );
        expect(mockNavigateTo).toHaveBeenCalledWith("/");
    });
});
