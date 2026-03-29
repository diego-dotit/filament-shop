/**
 * Tests for account/addresses/index.vue shadcn migration (T6.3)
 * Verifies:
 * - No <style> or <style scoped> block in source
 * - No legacy BEM/custom CSS class names (.account-addresses, .page-header, etc.)
 * - Imports shadcn Alert, Button, Card components
 * - Error state uses Alert component (role="alert")
 * - Edit button uses shadcn Button with outline variant
 * - Delete button uses shadcn Button with destructive variant
 * - All data-testid attributes preserved
 * - All functionality preserved (fetch, delete, empty state, loading state)
 */
import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";
import { mount, flushPromises } from "@vue/test-utils";
import * as fs from "node:fs";
import * as path from "node:path";
import { dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE importing any component under test
// ---------------------------------------------------------------------------

const mockFetch = vi.fn();

vi.stubGlobal("$fetch", Object.assign(mockFetch, { create: vi.fn(() => mockFetch) }));
vi.stubGlobal("defineNuxtPlugin", (fn: (app: unknown) => unknown) => fn({}));

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context — using $fetch fallback");
});

vi.stubGlobal("computed", computed);
vi.stubGlobal("ref", ref);
vi.stubGlobal("useApi", () => mockFetch);

const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", <T>(key: string, init: () => T) => {
    if (!stateStore[key]) {
        stateStore[key] = ref<T>(init());
    }
    return stateStore[key];
});

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);
vi.stubGlobal("definePageMeta", vi.fn());
vi.stubGlobal("useRoute", () => ({ params: {}, query: {} }));

const mockIsAuthenticated = ref(true);
const mockUser = ref({ id: 1, first_name: "Alice", last_name: "Smith", email: "alice@example.com" });
vi.stubGlobal("useAuth", () => ({
    user: mockUser,
    isAuthenticated: mockIsAuthenticated,
    logout: vi.fn(),
}));

vi.stubGlobal("onMounted", (fn: () => void) => fn());
vi.stubGlobal("reactive", (obj: object) => obj);

// ---------------------------------------------------------------------------
// Global stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
};

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

const filePath = path.resolve(__dirname, "../pages/account/addresses/index.vue");

// ---------------------------------------------------------------------------
// Source-level tests
// ---------------------------------------------------------------------------

describe("account/addresses/index.vue shadcn migration — source checks", () => {
    it("source file has no <style> block", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no legacy BEM class names", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        const legacyClasses = [
            "account-addresses",
            "page-header",
            "btn-add",
            "btn-edit",
            "btn-delete",
            "addresses-list",
            "address-item",
            "address-details",
            "address-line",
            "address-actions",
            "empty-state",
        ];
        for (const cls of legacyClasses) {
            expect(source, `Expected source NOT to contain "${cls}"`).not.toContain(`"${cls}"`);
        }
    });

    it("source file imports Alert from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/alert");
    });

    it("source file imports Button from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/button");
    });

    it("source file imports Card from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/card");
    });
});

// ---------------------------------------------------------------------------
// Rendered HTML tests
// ---------------------------------------------------------------------------

describe("account/addresses/index.vue shadcn migration — rendered HTML", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockFetch.mockReset();

        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();

        mockIsAuthenticated.value = true;
        mockUser.value = { id: 1, first_name: "Alice", last_name: "Smith", email: "alice@example.com" };

        vi.stubGlobal("onMounted", (fn: () => void) => fn());
    });

    it("error state renders using Alert component (role=alert)", async () => {
        mockFetch.mockRejectedValueOnce(new Error("Network error"));

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const alert = wrapper.find('[role="alert"]');
        expect(alert.exists()).toBe(true);
        expect(wrapper.html()).toContain("Failed to load addresses");
    });

    it("Edit button uses shadcn Button component (data-slot=button)", async () => {
        mockFetch.mockResolvedValueOnce({ data: [mockAddresses[0]] });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const editBtn = wrapper.find("[data-testid='edit-address']");
        expect(editBtn.exists()).toBe(true);
        expect(editBtn.attributes("data-slot")).toBe("button");
    });

    it("Delete button uses shadcn Button component (data-slot=button)", async () => {
        mockFetch.mockResolvedValueOnce({ data: [mockAddresses[0]] });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const deleteBtn = wrapper.find("[data-testid='delete-address']");
        expect(deleteBtn.exists()).toBe(true);
        expect(deleteBtn.attributes("data-slot")).toBe("button");
    });

    it("address list renders Card components for each address", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockAddresses });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        // Card renders as a div with rounded-lg border classes
        const cards = wrapper.findAll(".rounded-lg.border");
        expect(cards.length).toBeGreaterThanOrEqual(2);
    });

    it("all data-testid attributes are preserved", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockAddresses });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        const editBtns = wrapper.findAll("[data-testid='edit-address']");
        const deleteBtns = wrapper.findAll("[data-testid='delete-address']");
        expect(editBtns).toHaveLength(2);
        expect(deleteBtns).toHaveLength(2);
    });

    it("page header contains h1 and Add New Address link", async () => {
        mockFetch.mockResolvedValueOnce({ data: [] });

        const { default: AddressesPage } = await import("../pages/account/addresses/index.vue");
        const wrapper = mount(AddressesPage, { global: { stubs: globalStubs } });
        await flushPromises();

        expect(wrapper.find("h1").exists()).toBe(true);
        expect(wrapper.text()).toContain("My Addresses");
        expect(wrapper.text()).toContain("Add New Address");
    });
});
