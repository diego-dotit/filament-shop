import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";
import { mount } from "@vue/test-utils";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// Nuxt auto-imports (ref, computed, useState, useAuth, useRoute, useRouter)
// are not available in the Vitest environment, so we expose them on globalThis.
// ---------------------------------------------------------------------------

vi.stubGlobal("ref", ref);
vi.stubGlobal("computed", computed);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());

// ---------------------------------------------------------------------------
// Mock useAuth — default: not authenticated
// ---------------------------------------------------------------------------

const mockLogin = vi.fn();
const mockIsAuthenticated = ref(false);

vi.stubGlobal("useAuth", () => ({
    isAuthenticated: mockIsAuthenticated,
    login: mockLogin,
}));

// ---------------------------------------------------------------------------
// Mock useRoute / useRouter — Nuxt composables
// ---------------------------------------------------------------------------

const mockPush = vi.fn();
const mockRoute = ref({ query: {} as Record<string, string> });

vi.stubGlobal("useRoute", () => mockRoute.value);
vi.stubGlobal("useRouter", () => ({ push: mockPush }));

// ---------------------------------------------------------------------------
// Stub NuxtLink global component
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
};

// ---------------------------------------------------------------------------
// Helper: mount the login page
// ---------------------------------------------------------------------------

async function mountLoginPage() {
    const { default: LoginPage } = await import("../pages/login.vue");
    return mount(LoginPage, { global: { stubs: globalStubs } });
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("Login page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockIsAuthenticated.value = false;
        mockRoute.value = { query: {} };
        vi.resetModules();
    });

    // ── Rendering ──────────────────────────────────────────────────────────────

    it("renders email and password input fields", async () => {
        const wrapper = await mountLoginPage();
        expect(wrapper.find('input[type="email"]').exists()).toBe(true);
        expect(wrapper.find('input[type="password"]').exists()).toBe(true);
    });

    it("renders a submit button", async () => {
        const wrapper = await mountLoginPage();
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });

    it('renders a "Register" link pointing to /register', async () => {
        const wrapper = await mountLoginPage();
        const links = wrapper.findAll("a");
        const registerLink = links.find((l) => l.text().toLowerCase().includes("register"));
        expect(registerLink).toBeDefined();
        expect(registerLink?.attributes("href")).toBe("/register");
    });

    // ── Client-side validation ─────────────────────────────────────────────────

    it("shows validation error when email is empty on submit", async () => {
        const wrapper = await mountLoginPage();
        await wrapper.find("form").trigger("submit.prevent");
        expect(wrapper.text()).toMatch(/email/i);
    });

    it("shows validation error when password is fewer than 8 characters", async () => {
        const wrapper = await mountLoginPage();
        await wrapper.find('input[type="email"]').setValue("user@example.com");
        await wrapper.find('input[type="password"]').setValue("short");
        await wrapper.find("form").trigger("submit.prevent");
        expect(wrapper.text()).toMatch(/password/i);
    });

    // ── Submission ─────────────────────────────────────────────────────────────

    it("calls useAuth().login with email and password on valid submit", async () => {
        mockLogin.mockResolvedValueOnce([{ customer: {}, token: "tok" }, null]);
        const wrapper = await mountLoginPage();
        await wrapper.find('input[type="email"]').setValue("user@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");
        await wrapper.find("form").trigger("submit.prevent");
        await wrapper.vm.$nextTick();
        expect(mockLogin).toHaveBeenCalledWith("user@example.com", "password123");
    });

    it('redirects to "/" on successful login with no redirect query param', async () => {
        mockLogin.mockResolvedValueOnce([{ customer: {}, token: "tok" }, null]);
        const wrapper = await mountLoginPage();
        await wrapper.find('input[type="email"]').setValue("user@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");
        await wrapper.find("form").trigger("submit.prevent");
        await wrapper.vm.$nextTick();
        expect(mockPush).toHaveBeenCalledWith("/");
    });

    it("redirects to the route query redirect param on successful login", async () => {
        mockLogin.mockResolvedValueOnce([{ customer: {}, token: "tok" }, null]);
        mockRoute.value = { query: { redirect: "/checkout" } };
        vi.resetModules();
        const wrapper = await mountLoginPage();
        await wrapper.find('input[type="email"]').setValue("user@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");
        await wrapper.find("form").trigger("submit.prevent");
        await wrapper.vm.$nextTick();
        expect(mockPush).toHaveBeenCalledWith("/checkout");
    });

    it("displays an error message on login failure", async () => {
        mockLogin.mockResolvedValueOnce([null, new Error("Invalid credentials")]);
        const wrapper = await mountLoginPage();
        await wrapper.find('input[type="email"]').setValue("user@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");
        await wrapper.find("form").trigger("submit.prevent");
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toMatch(/invalid credentials/i);
    });

    it("resets the form fields after successful login", async () => {
        mockLogin.mockResolvedValueOnce([{ customer: {}, token: "tok" }, null]);
        const wrapper = await mountLoginPage();
        const emailInput = wrapper.find('input[type="email"]');
        const passwordInput = wrapper.find('input[type="password"]');
        await emailInput.setValue("user@example.com");
        await passwordInput.setValue("password123");
        await wrapper.find("form").trigger("submit.prevent");
        await wrapper.vm.$nextTick();
        expect((emailInput.element as HTMLInputElement).value).toBe("");
        expect((passwordInput.element as HTMLInputElement).value).toBe("");
    });

    // ── Loading state ──────────────────────────────────────────────────────────

    it("disables the submit button while login is in progress", async () => {
        // login never resolves during the test — simulates in-flight request
        let resolveLogin: (val: unknown) => void;
        mockLogin.mockReturnValueOnce(
            new Promise((res) => {
                resolveLogin = res;
            })
        );
        const wrapper = await mountLoginPage();
        await wrapper.find('input[type="email"]').setValue("user@example.com");
        await wrapper.find('input[type="password"]').setValue("password123");
        wrapper.find("form").trigger("submit.prevent");
        await wrapper.vm.$nextTick();
        expect(wrapper.find('button[type="submit"]').attributes("disabled")).toBeDefined();
        // Resolve so no unhandled promise warnings
        resolveLogin!([{ customer: {}, token: "tok" }, null]);
    });

    // ── Already authenticated ──────────────────────────────────────────────────

    it('redirects to "/" immediately when user is already authenticated', async () => {
        mockIsAuthenticated.value = true;
        await mountLoginPage();
        expect(mockPush).toHaveBeenCalledWith("/");
    });
});
