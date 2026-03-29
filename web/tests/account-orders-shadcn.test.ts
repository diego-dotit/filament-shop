/**
 * Tests for account/orders/index.vue shadcn migration (T6.6)
 * Verifies:
 * - No <style> or <style scoped> block in source
 * - No legacy custom CSS class names (account-orders, loading, error, empty-state, etc.)
 * - Imports shadcn Table components
 * - Imports shadcn Alert component
 * - Loading state uses Tailwind text class (no custom .loading class)
 * - Error state uses Alert with variant="destructive"
 * - Empty state uses Tailwind styling with link to products
 * - Order list uses shadcn Table with proper structure
 * - Table columns: Order ID (link), Date, Status, Total
 * - All order data renders correctly
 * - Header uses Tailwind text/margin classes
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

vi.stubGlobal("onMounted", (fn: () => void) => fn());

// ---------------------------------------------------------------------------
// useOrders stub
// ---------------------------------------------------------------------------

const mockOrders$ = ref<unknown[]>([]);
const mockLoading$ = ref(false);
const mockError$ = ref<string | null>(null);
const mockFetchOrders = vi.fn();

vi.stubGlobal("useOrders", () => ({
    orders: mockOrders$,
    loading: mockLoading$,
    error: mockError$,
    fetchOrders: mockFetchOrders,
}));

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const mockOrders = [
    {
        id: 101,
        status: "completed",
        total_amount: "59.99",
        created_at: "2024-01-15T10:00:00Z",
    },
    {
        id: 102,
        status: "pending",
        total_amount: "29.50",
        created_at: "2024-02-20T14:30:00Z",
    },
];

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
    Alert: {
        template: '<div class="alert" :data-variant="variant"><slot /></div>',
        props: ["variant"],
    },
    AlertDescription: { template: "<span><slot /></span>" },
};

// ---------------------------------------------------------------------------
// Source-level tests
// ---------------------------------------------------------------------------

describe("account/orders/index.vue shadcn migration — source checks", () => {
    const filePath = path.resolve(__dirname, "../pages/account/orders/index.vue");

    it("source file has no <style> block", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no legacy custom CSS class names", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        const legacyClasses = ["account-orders", "orders-list", "order-item", "order-link", "order-id", "order-date", "order-status", "order-total", "empty-state"];
        for (const cls of legacyClasses) {
            expect(source, `Expected source NOT to contain class "${cls}"`).not.toContain(`"${cls}"`);
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

    it("loading state uses Tailwind class (not custom .loading)", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain('class="loading"');
        expect(source).toContain("text-");
    });

    it("error state uses Alert component with variant destructive", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("Alert");
        expect(source).toContain("destructive");
    });

    it("uses shadcn Table component with full structure", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("<Table");
        expect(source).toContain("<TableHeader");
        expect(source).toContain("<TableBody");
        expect(source).toContain("<TableHead");
        expect(source).toContain("<TableRow");
        expect(source).toContain("<TableCell");
    });

    it("has all four table column headers: Order ID, Date, Status, Total", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).toContain("Order ID");
        expect(source).toContain("Date");
        expect(source).toContain("Status");
        expect(source).toContain("Total");
    });

    it("header h1 uses Tailwind text classes (no bare <h1>My Orders</h1>)", () => {
        const source = fs.readFileSync(filePath, "utf-8");
        // Should have Tailwind classes on h1
        expect(source).toContain("text-3xl");
        expect(source).toContain("font-bold");
    });
});

// ---------------------------------------------------------------------------
// Render tests
// ---------------------------------------------------------------------------

describe("account/orders/index.vue shadcn migration — render tests", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockIsAuthenticated.value = true;
        mockOrders$.value = [];
        mockLoading$.value = false;
        mockError$.value = null;
        mockFetchOrders.mockReset();
    });

    it("renders 'My Orders' heading", async () => {
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        expect(wrapper.html()).toContain("My Orders");
    });

    it("renders loading state", async () => {
        mockLoading$.value = true;
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        expect(wrapper.html()).toContain("Loading");
    });

    it("renders error state using Alert component", async () => {
        mockError$.value = "Failed to load orders";
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("Failed to load orders");
        expect(html).toContain("alert");
    });

    it("renders empty state with link to products", async () => {
        mockOrders$.value = [];
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("You haven't placed any orders yet");
        expect(html).toContain("Browse products");
    });

    it("renders order table with Order ID, Date, Status, Total columns", async () => {
        mockOrders$.value = mockOrders;
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("Order ID");
        expect(html).toContain("Date");
        expect(html).toContain("Status");
        expect(html).toContain("Total");
    });

    it("renders all order data (ID, status, total) in table rows", async () => {
        mockOrders$.value = mockOrders;
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("101");
        expect(html).toContain("completed");
        expect(html).toContain("59.99");
        expect(html).toContain("102");
        expect(html).toContain("pending");
        expect(html).toContain("29.50");
    });

    it("renders order ID links to order detail pages", async () => {
        mockOrders$.value = mockOrders;
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, { global: { stubs: globalStubs } });

        await wrapper.vm.$nextTick();
        const html = wrapper.html();
        expect(html).toContain("/account/orders/101");
        expect(html).toContain("/account/orders/102");
    });

    it("redirects unauthenticated user to /login", async () => {
        mockIsAuthenticated.value = false;

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        mount(OrdersPage, { global: { stubs: globalStubs } });

        expect(mockNavigateTo).toHaveBeenCalledWith("/login");
    });

    it("calls fetchOrders on mount when authenticated", async () => {
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        mount(OrdersPage, { global: { stubs: globalStubs } });

        expect(mockFetchOrders).toHaveBeenCalled();
    });
});
