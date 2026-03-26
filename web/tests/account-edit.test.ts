import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context");
});

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

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
// Test fixture data
// ---------------------------------------------------------------------------

const mockCustomer = {
    id: 1,
    name: "Alice Smith",
    first_name: "Alice",
    last_name: "Smith",
    email: "alice@example.com",
    phone: "+1-555-0100",
};

// ---------------------------------------------------------------------------
// Helper: build a useAuth stub
// ---------------------------------------------------------------------------

function makeAuthStub(userValue: typeof mockCustomer | null = mockCustomer, _apiMock = vi.fn()) {
    return () => ({
        user: ref(userValue),
        isAuthenticated: computed(() => userValue !== null),
        logout: vi.fn(),
    });
}

// ---------------------------------------------------------------------------
// Tests: Account Edit page
// ---------------------------------------------------------------------------

describe("Account Edit page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockNavigateTo.mockReset();
        vi.resetModules();
    });

    // ── Authentication guard ───────────────────────────────────────────────────

    it("redirects to /login when user is not authenticated", async () => {
        vi.stubGlobal("useAuth", makeAuthStub(null));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        mount(EditPage, { global: { stubs: globalStubs } });

        expect(mockNavigateTo).toHaveBeenCalledWith("/login");
    });

    // ── Form rendering ─────────────────────────────────────────────────────────

    it("renders the edit form on mount", async () => {
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        expect(wrapper.find('[data-testid="edit-form"]').exists()).toBe(true);
    });

    it("pre-fills form fields with current user data on mount", async () => {
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        expect(
            (wrapper.find('[data-testid="input-first-name"]').element as HTMLInputElement).value
        ).toBe("Alice");
        expect(
            (wrapper.find('[data-testid="input-last-name"]').element as HTMLInputElement).value
        ).toBe("Smith");
        expect(
            (wrapper.find('[data-testid="input-email"]').element as HTMLInputElement).value
        ).toBe("alice@example.com");
        expect(
            (wrapper.find('[data-testid="input-phone"]').element as HTMLInputElement).value
        ).toBe("+1-555-0100");
    });

    it("shows empty inputs when user fields are null/missing", async () => {
        const partialUser = { id: 2, name: "Bob", email: "bob@example.com" };
        vi.stubGlobal("useAuth", makeAuthStub(partialUser as typeof mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        expect(
            (wrapper.find('[data-testid="input-first-name"]').element as HTMLInputElement).value
        ).toBe("");
        expect(
            (wrapper.find('[data-testid="input-last-name"]').element as HTMLInputElement).value
        ).toBe("");
        expect(
            (wrapper.find('[data-testid="input-email"]').element as HTMLInputElement).value
        ).toBe("bob@example.com");
        expect(
            (wrapper.find('[data-testid="input-phone"]').element as HTMLInputElement).value
        ).toBe("");
    });

    // ── Cancel button ──────────────────────────────────────────────────────────

    it("cancel button navigates to /account/dashboard", async () => {
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        await wrapper.find('[data-testid="cancel-btn"]').trigger("click");

        expect(mockNavigateTo).toHaveBeenCalledWith("/account/dashboard");
    });

    // ── Submit / API call ──────────────────────────────────────────────────────

    it("submit calls PUT /customers/me with updated form values", async () => {
        const mockApi = vi.fn().mockResolvedValueOnce({
            data: { ...mockCustomer, first_name: "Alicia" },
        });
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        const firstNameInput = wrapper.find('[data-testid="input-first-name"]');
        await firstNameInput.setValue("Alicia");

        await wrapper.find('[data-testid="edit-form"]').trigger("submit");
        await wrapper.vm.$nextTick();

        expect(mockApi).toHaveBeenCalledWith(
            "/customers/me",
            expect.objectContaining({
                method: "PUT",
                body: expect.objectContaining({
                    first_name: "Alicia",
                    last_name: "Smith",
                    email: "alice@example.com",
                    phone: "+1-555-0100",
                }),
            })
        );
    });

    it("shows success message after successful profile update", async () => {
        const updatedCustomer = { ...mockCustomer, first_name: "Alicia" };
        const mockApi = vi.fn().mockResolvedValueOnce({ data: updatedCustomer });
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        await wrapper.find('[data-testid="edit-form"]').trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="success-msg"]').exists()).toBe(true);
    });

    it("displays validation error message on API failure", async () => {
        const apiError = {
            data: { errors: { email: ["The email has already been taken."] } },
        };
        const mockApi = vi.fn().mockRejectedValueOnce(apiError);
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        await wrapper.find('[data-testid="edit-form"]').trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="error-msg"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="error-msg"]').text()).toContain(
            "The email has already been taken."
        );
    });

    it("displays generic error message on non-validation API failure", async () => {
        const apiError = { data: { message: "Server error" } };
        const mockApi = vi.fn().mockRejectedValueOnce(apiError);
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useAuth", makeAuthStub(mockCustomer));

        const { default: EditPage } = await import("../pages/account/edit.vue");
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        await wrapper.find('[data-testid="edit-form"]').trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="error-msg"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="error-msg"]').text()).toContain("Server error");
    });
});
