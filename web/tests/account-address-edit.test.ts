import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { ref, reactive, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("ref", ref);
vi.stubGlobal("reactive", reactive);
vi.stubGlobal("definePageMeta", vi.fn());

// onMounted: run the callback synchronously so side effects run in tests
vi.stubGlobal("onMounted", (cb: () => void | Promise<void>) => { void cb(); });

vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

vi.stubGlobal("useApi", () => vi.fn());

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// Default useRoute stub — overridden per-test where needed
vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));

// ---------------------------------------------------------------------------
// Shared stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: '<a><slot /></a>' },
};

// ---------------------------------------------------------------------------
// Fixture data
// ---------------------------------------------------------------------------

const mockAddress = {
    id: 42,
    country: "US",
    city: "New York",
    address_line_1: "123 Main St",
    address_line_2: "Apt 4B",
    postcode: "10001",
};

// ---------------------------------------------------------------------------
// Tests: Account Address Edit page
// ---------------------------------------------------------------------------

describe("Account Address Edit page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockNavigateTo.mockReset();
        vi.resetModules();
    });

    // ── Auth middleware ──────────────────────────────────────────────────────

    it("defines auth page middleware", async () => {
        const mockDefinePageMeta = vi.fn();
        vi.stubGlobal("definePageMeta", mockDefinePageMeta);
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        vi.stubGlobal("useApi", () => vi.fn().mockResolvedValue({ data: mockAddress }));

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        mount(EditPage, { global: { stubs: globalStubs } });

        expect(mockDefinePageMeta).toHaveBeenCalledWith(
            expect.objectContaining({ middleware: "auth" })
        );
    });

    // ── Form rendering ───────────────────────────────────────────────────────

    it("renders the edit form", async () => {
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        vi.stubGlobal("useApi", () => vi.fn().mockResolvedValue({ data: mockAddress }));

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });

        expect(wrapper.find('[data-testid="edit-address-form"]').exists()).toBe(true);
    });

    it("pre-fills form fields after fetching address data", async () => {
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        const mockApi = vi.fn().mockResolvedValue({ data: mockAddress });
        vi.stubGlobal("useApi", () => mockApi);

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });
        await flushPromises();

        expect(
            (wrapper.find('[data-testid="input-country"]').element as HTMLInputElement).value
        ).toBe("US");
        expect(
            (wrapper.find('[data-testid="input-city"]').element as HTMLInputElement).value
        ).toBe("New York");
        expect(
            (wrapper.find('[data-testid="input-address-line-1"]').element as HTMLInputElement).value
        ).toBe("123 Main St");
        expect(
            (wrapper.find('[data-testid="input-address-line-2"]').element as HTMLInputElement).value
        ).toBe("Apt 4B");
        expect(
            (wrapper.find('[data-testid="input-postcode"]').element as HTMLInputElement).value
        ).toBe("10001");
    });

    it("fetches address from GET /customers/me/addresses/{id} on mount", async () => {
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        const mockApi = vi.fn().mockResolvedValue({ data: mockAddress });
        vi.stubGlobal("useApi", () => mockApi);

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        mount(EditPage, { global: { stubs: globalStubs } });
        await flushPromises();

        expect(mockApi).toHaveBeenCalledWith(
            "/customers/me/addresses/42",
            expect.objectContaining({ method: "GET" })
        );
    });

    // ── Cancel button ────────────────────────────────────────────────────────

    it("cancel button navigates to /account/addresses", async () => {
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        vi.stubGlobal("useApi", () => vi.fn().mockResolvedValue({ data: mockAddress }));

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });
        await flushPromises();

        await wrapper.find('[data-testid="cancel-btn"]').trigger("click");

        expect(mockNavigateTo).toHaveBeenCalledWith("/account/addresses");
    });

    // ── Submit ───────────────────────────────────────────────────────────────

    it("submit calls PUT /customers/me/addresses/{id} with form data", async () => {
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        const mockApi = vi
            .fn()
            .mockResolvedValueOnce({ data: mockAddress }) // GET
            .mockResolvedValueOnce({ data: mockAddress }); // PUT
        vi.stubGlobal("useApi", () => mockApi);

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });
        await flushPromises();

        await wrapper.find('[data-testid="edit-address-form"]').trigger("submit");
        await flushPromises();

        expect(mockApi).toHaveBeenCalledWith(
            "/customers/me/addresses/42",
            expect.objectContaining({
                method: "PUT",
                body: expect.objectContaining({
                    country: "US",
                    city: "New York",
                    address_line_1: "123 Main St",
                    postcode: "10001",
                }),
            })
        );
    });

    it("redirects to /account/addresses after successful save", async () => {
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        const mockApi = vi
            .fn()
            .mockResolvedValueOnce({ data: mockAddress }) // GET
            .mockResolvedValueOnce({ data: mockAddress }); // PUT
        vi.stubGlobal("useApi", () => mockApi);

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });
        await flushPromises();

        await wrapper.find('[data-testid="edit-address-form"]').trigger("submit");
        await flushPromises();

        expect(mockNavigateTo).toHaveBeenCalledWith("/account/addresses");
    });

    it("displays error message on API failure", async () => {
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        const apiError = { data: { message: "Address not found." } };
        const mockApi = vi
            .fn()
            .mockResolvedValueOnce({ data: mockAddress }) // GET
            .mockRejectedValueOnce(apiError); // PUT
        vi.stubGlobal("useApi", () => mockApi);

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });
        await flushPromises();

        await wrapper.find('[data-testid="edit-address-form"]').trigger("submit");
        await flushPromises();

        expect(wrapper.find('[data-testid="error-msg"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="error-msg"]').text()).toContain("Address not found.");
    });

    it("disables submit button while saving", async () => {
        vi.stubGlobal("useRoute", () => ({ params: { id: "42" }, query: {} }));
        // GET resolves immediately; PUT never resolves (pending)
        let resolvePut!: (v: unknown) => void;
        const pendingPut = new Promise((resolve) => { resolvePut = resolve; });
        const mockApi = vi
            .fn()
            .mockResolvedValueOnce({ data: mockAddress })
            .mockReturnValueOnce(pendingPut);
        vi.stubGlobal("useApi", () => mockApi);

        const { default: EditPage } = await import(
            "../pages/account/addresses/[id]/edit.vue"
        );
        const wrapper = mount(EditPage, { global: { stubs: globalStubs } });
        await flushPromises();

        // Trigger submit but don't await completion
        wrapper.find('[data-testid="edit-address-form"]').trigger("submit");
        await wrapper.vm.$nextTick();

        const btn = wrapper.find('[data-testid="submit-btn"]');
        expect((btn.element as HTMLButtonElement).disabled).toBe(true);

        // Clean up
        resolvePut({ data: mockAddress });
    });
});
