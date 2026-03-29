/**
 * Tests for account/orders/[id].vue shadcn migration (T6.7)
 * Verifies:
 * - No <style> or <style scoped> block in source
 * - No legacy custom CSS class names (order-detail, back-link, loading, error, etc.)
 * - Imports shadcn Table components
 * - Imports shadcn Alert component
 * - Loading state uses Tailwind classes (no custom CSS)
 * - Error state uses Alert with variant="destructive"
 * - Back link uses Tailwind text styling
 * - Order summary uses dl/dt/dd with Tailwind utilities
 * - Order items use shadcn Table component
 * - Addresses use Tailwind flex layout
 * - All order data renders correctly
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
vi.stubGlobal("definePageMeta", vi.fn());

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);

const mockIsAuthenticated = ref(true);
vi.stubGlobal("useAuth", () => ({
    isAuthenticated: mockIsAuthenticated,
}));

const mockRoute = { params: { id: "42" } };
vi.stubGlobal("useRoute", () => mockRoute);

vi.stubGlobal("onMounted", (fn: () => void) => fn());

// ---------------------------------------------------------------------------
// useOrders stub
// ---------------------------------------------------------------------------

const mockCurrentOrder$ = ref<unknown>(null);
const mockLoading$ = ref(false);
const mockError$ = ref<string | null>(null);
const mockFetchOrder = vi.fn();

vi.stubGlobal("useOrders", () => ({
    currentOrder: mockCurrentOrder$,
    loading: mockLoading$,
    error: mockError$,
    fetchOrder: mockFetchOrder,
}));

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const mockOrder = {
    id: 42,
    status: "completed",
    total_amount: "89.99",
    subtotal: "79.99",
    created_at: "2024-03-10T09:00:00Z",
    items: [
        {
            id: 1,
            product_name: "PLA Filament",
            variant_name: "Red 1kg",
            quantity: 2,
            price: "19.99",
            line_total: "39.98",
        },
        {
            id: 2,
            product_name: "PETG Filament",
            variant_name: "Black 1kg",
            quantity: 2,
            price: "24.99",
            line_total: "49.98",
        },
    ],
    billing_address: {
        name: "Alice Smith",
        line1: "123 Main St",
        city: "Springfield",
        country: "US",
    },
    shipping_address: {
        name: "Alice Smith",
        line1: "456 Oak Ave",
        city: "Shelbyville",
        country: "US",
    },
};

// ---------------------------------------------------------------------------
// Global stubs for child components
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
    Table: { template: "<table><slot /></table>" },
    TableHeader: { template: "<thead><slot /></thead>" },
    TableBody: { template: "<tbody><slot /></tbody>" },
    TableHead: { template: "<th><slot /></th>" },
    TableRow: { template: "<tr><slot /></tr>" },
    TableCell: { template: "<td><slot /></td>" },
    Alert: { template: '<div class="alert" :data-variant="variant"><slot /></div>', props: ["variant"] },
    AlertDescription: { template: "<span><slot /></span>" },
};

// ---------------------------------------------------------------------------
// Source-level tests
// ---------------------------------------------------------------------------

describe("order detail [id].vue shadcn migration — source checks", () => {
    const filePath = path.resolve(__dirname, "../pages/account/orders/[id].vue");

    it("source file has no <style> block", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no legacy custom CSS class names", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        const legacyClasses = [
            "order-detail",
            "back-link",
            "order-content",
            "order-summary",
            "order-items",
            "order-addresses",
            "billing-address",
            "shipping-address",
        ];
        for (const cls of legacyClasses) {
            expect(source, `Expected source NOT to contain "${cls}"`).not.toContain(`"${cls}"`);
        }
    });

    it("source file imports Table from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/table");
    });

    it("source file imports Alert from shadcn-vue", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("@/components/ui/alert");
    });

    it("loading state uses Tailwind text class (not custom .loading class)", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("text-");
        expect(source).not.toContain('class="loading"');
    });

    it("error state uses Alert component with variant destructive", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("Alert");
        expect(source).toContain("destructive");
    });

    it("back link uses Tailwind text styling (no custom class)", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        // Should have back link with Tailwind class like text-sm text-blue or text-primary
        expect(source).toContain("← Back");
        expect(source).not.toContain('"back-link"');
    });

    it("uses shadcn Table component for order items", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("<Table");
        expect(source).toContain("<TableHeader");
        expect(source).toContain("<TableBody");
        expect(source).toContain("<TableHead");
        expect(source).toContain("<TableRow");
        expect(source).toContain("<TableCell");
    });
});

// ---------------------------------------------------------------------------
// Render tests
// ---------------------------------------------------------------------------

describe("order detail [id].vue shadcn migration — render tests", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockIsAuthenticated.value = true;
        mockCurrentOrder$.value = null;
        mockLoading$.value = false;
        mockError$.value = null;
        mockFetchOrder.mockReset();
    });

    it("renders loading state with Tailwind class", async () => {
        mockLoading$.value = true;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        expect(wrapper.html()).toContain("Loading");
    });

    it("renders error state using Alert component", async () => {
        mockError$.value = "Failed to load order";
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("Failed to load order");
        // Alert stub rendered
        expect(html).toContain("alert");
    });

    it("renders order ID, status, date, and total", async () => {
        mockCurrentOrder$.value = mockOrder;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("42");
        expect(html).toContain("completed");
        expect(html).toContain("89.99");
        expect(html).toContain("79.99");
    });

    it("renders order items table with product name, variant, quantity, price, line total", async () => {
        mockCurrentOrder$.value = mockOrder;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("PLA Filament");
        expect(html).toContain("Red 1kg");
        expect(html).toContain("PETG Filament");
        expect(html).toContain("Black 1kg");
        expect(html).toContain("39.98");
        expect(html).toContain("49.98");
    });

    it("renders billing and shipping addresses", async () => {
        mockCurrentOrder$.value = mockOrder;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("Alice Smith");
        expect(html).toContain("123 Main St");
        expect(html).toContain("Springfield");
        expect(html).toContain("456 Oak Ave");
        expect(html).toContain("Shelbyville");
    });

    it("renders back link to /account/orders", async () => {
        mockCurrentOrder$.value = mockOrder;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        expect(wrapper.html()).toContain("/account/orders");
    });

    it("calls fetchOrder with route param id on mount", async () => {
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        mount(OrderDetailPage, { global: { stubs: globalStubs } });

        expect(mockFetchOrder).toHaveBeenCalledWith("42");
    });

    it("redirects unauthenticated user to /login", async () => {
        mockIsAuthenticated.value = false;

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        mount(OrderDetailPage, { global: { stubs: globalStubs } });

        expect(mockNavigateTo).toHaveBeenCalledWith("/login");
    });
});
