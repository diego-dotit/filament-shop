import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";
import { mount } from "@vue/test-utils";

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

// Stub Vue's computed with the real implementation so computed props work
vi.stubGlobal("computed", computed);

// useApi: return mockFetch directly so the composable can make requests
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

// useRoute stub (default: id = '42')
const mockRoute = { params: { id: "42" } };
vi.stubGlobal("useRoute", () => mockRoute);

// useAuth stub (default: authenticated)
const mockIsAuthenticated = ref(true);
const mockUser = ref({ id: 1, name: "Alice", email: "alice@example.com" });
vi.stubGlobal("useAuth", () => ({
    user: mockUser,
    isAuthenticated: mockIsAuthenticated,
    logout: vi.fn(),
}));

// onMounted stub — runs callback synchronously so tests can assert side effects
vi.stubGlobal("onMounted", (fn: () => void) => fn());

// ---------------------------------------------------------------------------
// useOrders global stub — used by Vue pages (Nuxt auto-import).
// Composable tests bypass this by importing the real module directly.
// ---------------------------------------------------------------------------
const mockOrders$ = ref<unknown[]>([]);
const mockCurrentOrder$ = ref<unknown>(null);
const mockLoading$ = ref(false);
const mockError$ = ref<string | null>(null);
const mockFetchOrders = vi.fn();
const mockFetchOrder = vi.fn();

vi.stubGlobal("useOrders", () => ({
    orders: mockOrders$,
    currentOrder: mockCurrentOrder$,
    loading: mockLoading$,
    error: mockError$,
    fetchOrders: mockFetchOrders,
    fetchOrder: mockFetchOrder,
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
// Tests for useOrders composable
// ---------------------------------------------------------------------------

describe("useOrders composable", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();

        // Reset shared state between tests
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();
    });

    // ── fetchOrders ────────────────────────────────────────────────────────────

    it("fetchOrders: calls GET /customers/me/orders", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockOrders });

        const { useOrders } = await import("../composables/useOrders");
        const { fetchOrders } = useOrders();

        await fetchOrders();

        expect(mockFetch).toHaveBeenCalledWith("/customers/me/orders");
    });

    it("fetchOrders: populates orders state from API response", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockOrders });

        const { useOrders } = await import("../composables/useOrders");
        const { orders, fetchOrders } = useOrders();

        await fetchOrders();

        expect(orders.value).toHaveLength(2);
        expect(orders.value[0].id).toBe(101);
        expect(orders.value[0].status).toBe("completed");
        expect(orders.value[0].total_amount).toBe("59.99");
    });

    it("fetchOrders: orders starts as empty array before fetch", async () => {
        const { useOrders } = await import("../composables/useOrders");
        const { orders } = useOrders();

        expect(orders.value).toEqual([]);
    });

    it("fetchOrders: sets loading true during fetch and false after", async () => {
        let resolvePromise!: (value: unknown) => void;
        mockFetch.mockReturnValueOnce(
            new Promise((resolve) => {
                resolvePromise = resolve;
            })
        );

        const { useOrders } = await import("../composables/useOrders");
        const { loading, fetchOrders } = useOrders();

        const fetchPromise = fetchOrders();
        expect(loading.value).toBe(true);

        resolvePromise({ data: [] });
        await fetchPromise;

        expect(loading.value).toBe(false);
    });

    it("fetchOrders: sets error on failure", async () => {
        mockFetch.mockRejectedValueOnce(new Error("Network error"));

        const { useOrders } = await import("../composables/useOrders");
        const { error, fetchOrders } = useOrders();

        await fetchOrders();

        expect(error.value).toBe("Failed to load orders");
    });

    // ── fetchOrder (single) ───────────────────────────────────────────────────

    it("fetchOrder: calls GET /customers/me/orders/{id}", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockOrder });

        const { useOrders } = await import("../composables/useOrders");
        const { fetchOrder } = useOrders();

        await fetchOrder(42);

        expect(mockFetch).toHaveBeenCalledWith("/customers/me/orders/42");
    });

    it("fetchOrder: populates currentOrder with order data including items and addresses", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockOrder });

        const { useOrders } = await import("../composables/useOrders");
        const { currentOrder, fetchOrder } = useOrders();

        await fetchOrder(42);

        expect(currentOrder.value).not.toBeNull();
        expect(currentOrder.value!.id).toBe(42);
        expect(currentOrder.value!.items).toHaveLength(2);
        expect(currentOrder.value!.items[0].product_name).toBe("PLA Filament");
        expect(currentOrder.value!.billing_address.city).toBe("Springfield");
        expect(currentOrder.value!.shipping_address.city).toBe("Shelbyville");
    });

    it("fetchOrder: sets error on failure", async () => {
        mockFetch.mockRejectedValueOnce(new Error("Not Found"));

        const { useOrders } = await import("../composables/useOrders");
        const { error, fetchOrder } = useOrders();

        await fetchOrder(999);

        expect(error.value).toBe("Failed to load order");
    });
});

// ---------------------------------------------------------------------------
// Tests for Account Orders List Page (orders.vue)
// ---------------------------------------------------------------------------

describe("Account Orders List Page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();

        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();

        // Reset auth to authenticated by default
        mockIsAuthenticated.value = true;
        mockUser.value = { id: 1, name: "Alice", email: "alice@example.com" };

        // Reset orders mock state
        mockOrders$.value = [];
        mockCurrentOrder$.value = null;
        mockLoading$.value = false;
        mockError$.value = null;
        mockFetchOrders.mockReset();
        mockFetchOrder.mockReset();
    });

    const globalStubs = {
        NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
    };

    it("redirects unauthenticated users to /login", async () => {
        mockIsAuthenticated.value = false;

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        mount(OrdersPage, {
            global: { stubs: globalStubs },
        });

        expect(mockNavigateTo).toHaveBeenCalledWith("/login");
    });

    it("calls GET /customers/me/orders on mount when authenticated", async () => {
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        mount(OrdersPage, {
            global: { stubs: globalStubs },
        });

        expect(mockFetchOrders).toHaveBeenCalled();
    });

    it("displays order list with order ID, status, and total", async () => {
        mockOrders$.value = mockOrders;
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, {
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        const html = wrapper.html();
        expect(html).toContain("101");
        expect(html).toContain("completed");
        expect(html).toContain("59.99");
    });

    it("shows empty state when no orders returned", async () => {
        mockOrders$.value = [];
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, {
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.html()).toContain("You haven't placed any orders yet");
    });

    it("renders links to order detail pages", async () => {
        mockOrders$.value = mockOrders;
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, {
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        const html = wrapper.html();
        expect(html).toContain("/account/orders/101");
        expect(html).toContain("/account/orders/102");
    });

    it("shows loading state during fetch", async () => {
        mockLoading$.value = true;
        mockFetchOrders.mockResolvedValueOnce(undefined);

        const { default: OrdersPage } = await import("../pages/account/orders/index.vue");
        const wrapper = mount(OrdersPage, {
            global: { stubs: globalStubs },
        });

        expect(wrapper.html()).toContain("Loading");
    });
});

// ---------------------------------------------------------------------------
// Tests for Account Order Detail Page (orders/[id].vue)
// ---------------------------------------------------------------------------

describe("Account Order Detail Page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();

        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();

        mockIsAuthenticated.value = true;
        mockUser.value = { id: 1, name: "Alice", email: "alice@example.com" };
        // Set route id param to '42'
        mockRoute.params.id = "42";

        // Reset orders mock state
        mockOrders$.value = [];
        mockCurrentOrder$.value = null;
        mockLoading$.value = false;
        mockError$.value = null;
        mockFetchOrders.mockReset();
        mockFetchOrder.mockReset();
    });

    const globalStubs = {
        NuxtLink: { template: '<a :href="to"><slot /></a>', props: ["to"] },
    };

    it("redirects unauthenticated users to /login", async () => {
        mockIsAuthenticated.value = false;

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        mount(OrderDetailPage, {
            global: { stubs: globalStubs },
        });

        expect(mockNavigateTo).toHaveBeenCalledWith("/login");
    });

    it("fetches GET /customers/me/orders/{id} on mount", async () => {
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        mount(OrderDetailPage, {
            global: { stubs: globalStubs },
        });

        expect(mockFetchOrder).toHaveBeenCalledWith("42");
    });

    it("displays order ID, status, and total", async () => {
        mockCurrentOrder$.value = mockOrder;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, {
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        const html = wrapper.html();
        expect(html).toContain("42");
        expect(html).toContain("completed");
        expect(html).toContain("89.99");
    });

    it("displays order items with product name, variant, quantity, and price", async () => {
        mockCurrentOrder$.value = mockOrder;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, {
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        const html = wrapper.html();
        expect(html).toContain("PLA Filament");
        expect(html).toContain("Red 1kg");
        expect(html).toContain("PETG Filament");
        expect(html).toContain("Black 1kg");
    });

    it("displays billing and shipping addresses", async () => {
        mockCurrentOrder$.value = mockOrder;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, {
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        const html = wrapper.html();
        expect(html).toContain("Springfield");
        expect(html).toContain("Shelbyville");
        expect(html).toContain("123 Main St");
        expect(html).toContain("456 Oak Ave");
    });

    it("has a back link to the orders list", async () => {
        mockCurrentOrder$.value = mockOrder;
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, {
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.html()).toContain("/account/orders");
    });

    it("shows error state when order fetch fails", async () => {
        mockError$.value = "Failed to load order";
        mockFetchOrder.mockResolvedValueOnce(undefined);

        const { default: OrderDetailPage } = await import("../pages/account/orders/[id].vue");
        const wrapper = mount(OrderDetailPage, {
            global: { stubs: globalStubs },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.html()).toContain("Failed to load order");
    });
});
