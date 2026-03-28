import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";
import { mount, flushPromises } from "@vue/test-utils";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// ---------------------------------------------------------------------------

const mockFetch = vi.fn();

vi.stubGlobal("$fetch", Object.assign(mockFetch, { create: vi.fn(() => mockFetch) }));
vi.stubGlobal("defineNuxtPlugin", (fn: (app: unknown) => unknown) => fn({}));

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// useNuxtApp throws → useApi falls back to $fetch
vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context — using $fetch fallback");
});

vi.stubGlobal("computed", computed);

// useApi: return mockFetch so the page can make requests
vi.stubGlobal("useApi", () => mockFetch);

// useState: simulate Nuxt's shared state via a single ref per key
const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", <T>(key: string, init: () => T) => {
    if (!stateStore[key]) {
        stateStore[key] = ref<T>(init());
    }
    return stateStore[key];
});

// Nuxt page/routing stubs
const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);
vi.stubGlobal("definePageMeta", vi.fn());
vi.stubGlobal("useRoute", () => ({ params: {}, query: {} }));

// useAuth stub (default: authenticated)
const mockIsAuthenticated = ref(true);
const mockUser = ref({ id: 1, first_name: "Alice", last_name: "Smith", email: "alice@example.com" });
vi.stubGlobal("useAuth", () => ({
    user: mockUser,
    isAuthenticated: mockIsAuthenticated,
    logout: vi.fn(),
}));

// onMounted stub — runs callback synchronously so tests can assert side effects
vi.stubGlobal("onMounted", (fn: () => void) => fn());

// reactive / ref stubs — use real Vue implementations
vi.stubGlobal("ref", ref);
vi.stubGlobal("reactive", (obj: object) => obj);

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const mockAddresses = [
    {
        id: 1,
        address_line_1: "123 Main St",
        address_line_2: "Apt 4",
        city: "Springfield",
        postcode: "12345",
        country: "US",
    },
    {
        id: 2,
        address_line_1: "456 Oak Ave",
        address_line_2: null,
        city: "Shelbyville",
        postcode: "67890",
        country: "US",
    },
];

// ---------------------------------------------------------------------------
// Tests for Account Addresses List Page (addresses/index.vue)
// ---------------------------------------------------------------------------

describe("Account Addresses List Page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockFetch.mockReset();
        localStorage.clear();

        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();

        mockIsAuthenticated.value = true;
        mockUser.value = { id: 1, first_name: "Alice", last_name: "Smith", email: "alice@example.com" };

        // Restore onMounted to run synchronously
        vi.stubGlobal("onMounted", (fn: () => void) => fn());
    });

    const globalStubs = {
        NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
    };

    it("redirects unauthenticated users to /login", async () => {
        mockIsAuthenticated.value = false;
        mockFetch.mockResolvedValueOnce({ data: [] });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        mount(AddressesPage, { global: { stubs: globalStubs } });

        expect(mockNavigateTo).toHaveBeenCalledWith("/login");
    });

    it("calls GET /customers/me/addresses on mount when authenticated", async () => {
        mockFetch.mockResolvedValueOnce({ data: [] });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        // api is called with /customers/me/addresses as first arg
        expect(mockFetch).toHaveBeenCalled();
        expect(mockFetch.mock.calls[0][0]).toBe("/customers/me/addresses");
    });

    it("calls GET /customers/me/addresses on mount (any call)", async () => {
        mockFetch.mockResolvedValueOnce({ data: [] });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        // api is called with /customers/me/addresses as first arg
        expect(mockFetch).toHaveBeenCalled();
        expect(mockFetch.mock.calls[0][0]).toBe("/customers/me/addresses");
    });

    it("displays address list with street, city, postcode, and country", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockAddresses });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const html = wrapper.html();
        expect(html).toContain("123 Main St");
        expect(html).toContain("Springfield");
        expect(html).toContain("12345");
        expect(html).toContain("US");
        expect(html).toContain("456 Oak Ave");
        expect(html).toContain("Shelbyville");
    });

    it("shows Edit and Delete buttons for each address", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockAddresses });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const html = wrapper.html();
        expect(html).toContain("Edit");
        expect(html).toContain("Delete");

        // Two addresses — two of each button
        const editButtons = wrapper.findAll("[data-testid='edit-address']");
        const deleteButtons = wrapper.findAll("[data-testid='delete-address']");
        expect(editButtons).toHaveLength(2);
        expect(deleteButtons).toHaveLength(2);
    });

    it("shows empty-state message when no addresses exist", async () => {
        mockFetch.mockResolvedValueOnce({ data: [] });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        expect(wrapper.html()).toContain("No saved addresses");
    });

    it("shows loading state during fetch", async () => {
        let resolvePromise!: (value: unknown) => void;
        mockFetch.mockReturnValueOnce(
            new Promise((resolve) => {
                resolvePromise = resolve;
            })
        );

        // Override onMounted to NOT run synchronously for this test
        vi.stubGlobal("onMounted", (_fn: () => void) => {
            /* don't run — keep loading = true */
        });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });

        expect(wrapper.html()).toContain("Loading");

        resolvePromise({ data: [] });
    });

    it("shows error message when fetch fails", async () => {
        mockFetch.mockRejectedValueOnce(new Error("Network error"));

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        expect(wrapper.html()).toContain("Failed to load addresses");
    });

    it("clicking Delete calls confirm() and then DELETE endpoint if confirmed", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockAddresses });
        // Second call: DELETE
        mockFetch.mockResolvedValueOnce({ data: null });

        const confirmMock = vi.fn().mockReturnValue(true);
        vi.stubGlobal("confirm", confirmMock);

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const deleteButtons = wrapper.findAll("[data-testid='delete-address']");
        await deleteButtons[0].trigger("click");
        await flushPromises();

        expect(confirmMock).toHaveBeenCalled();
        expect(mockFetch).toHaveBeenCalledWith(
            `/customers/me/addresses/${mockAddresses[0].id}`,
            expect.objectContaining({ method: "DELETE" })
        );
    });

    it("clicking Delete does NOT call DELETE endpoint when user cancels confirm", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockAddresses });

        const confirmMock = vi.fn().mockReturnValue(false);
        vi.stubGlobal("confirm", confirmMock);

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const deleteButtons = wrapper.findAll("[data-testid='delete-address']");
        await deleteButtons[0].trigger("click");
        await flushPromises();

        // Only the initial GET was called; no DELETE
        expect(mockFetch).toHaveBeenCalledTimes(1);
    });

    it("removes deleted address from the list after successful DELETE", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockAddresses });
        mockFetch.mockResolvedValueOnce({ data: null });

        vi.stubGlobal("confirm", () => true);

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const deleteButtons = wrapper.findAll("[data-testid='delete-address']");
        await deleteButtons[0].trigger("click");
        await flushPromises();

        // "123 Main St" should be gone; "456 Oak Ave" should remain
        const html = wrapper.html();
        expect(html).not.toContain("123 Main St");
        expect(html).toContain("456 Oak Ave");
    });

    it("Edit button links to /account/addresses/{id}/edit", async () => {
        mockFetch.mockResolvedValueOnce({ data: [mockAddresses[0]] });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const html = wrapper.html();
        expect(html).toContain(`/account/addresses/${mockAddresses[0].id}/edit`);
    });
});
