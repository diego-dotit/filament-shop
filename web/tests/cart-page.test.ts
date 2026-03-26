import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { ref, computed, onMounted } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

// onMounted: immediately invoke the callback (simulates component mounting)
vi.stubGlobal("onMounted", (cb: () => void | Promise<void>) => {
    return onMounted(cb);
});

// Stub navigateTo (Nuxt router helper)
const navigateTo = vi.fn();
vi.stubGlobal("navigateTo", navigateTo);

// Stub definePageMeta (no-op in tests)
vi.stubGlobal("definePageMeta", vi.fn());

// useState: simulate Nuxt's shared state via a plain ref per key
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

// useApi: return a no-op fetch stub
vi.stubGlobal("useApi", () => vi.fn());

// ---------------------------------------------------------------------------
// Cart / Auth stubs — overridden per test as needed
// ---------------------------------------------------------------------------

const mockFetchCart = vi.fn();
const mockUpdateItemQuantity = vi.fn();
const mockRemoveItem = vi.fn();

const mockCartItems = [
    {
        id: 1,
        product: { id: 10, name: "PLA Filament", slug: "pla-filament" },
        variant: { id: 100, sku: "PLA-RED-1KG" },
        quantity: 2,
        line_total: 39.98,
    },
    {
        id: 2,
        product: { id: 11, name: "PETG Filament", slug: "petg-filament" },
        variant: { id: 101, sku: "PETG-BLK-1KG" },
        quantity: 1,
        line_total: 24.99,
    },
];

const mockCartData = ref({
    id: "cart-uuid-1",
    items: mockCartItems,
    total: 64.97,
});

vi.stubGlobal("useCart", () => ({
    cart: mockCartData,
    items: computed(() => mockCartData.value?.items ?? []),
    itemCount: computed(() => 0),
    fetchCart: mockFetchCart,
    updateItemQuantity: mockUpdateItemQuantity,
    removeItem: mockRemoveItem,
}));

vi.stubGlobal("useAuth", () => ({
    user: ref({ id: 1, name: "Alice", email: "alice@example.com" }),
    isAuthenticated: computed(() => true),
    logout: vi.fn(),
}));

// ---------------------------------------------------------------------------
// Shared mount helpers
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
    CartItem: false, // render real CartItem (imported)
};

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

function makeItem(id: number, quantity: number) {
    return {
        id,
        product: { id: id * 10, name: `Product ${id}`, slug: `product-${id}` },
        variant: { id: id * 100, sku: `SKU-${id}` },
        quantity,
        line_total: quantity * 19.99,
    };
}

// ---------------------------------------------------------------------------
// CartItem component tests
// ---------------------------------------------------------------------------

describe("CartItem component", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("displays the product name", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("Product 1");
    });

    it("displays the variant SKU", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("SKU-1");
    });

    it("displays the quantity", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 3);
        const wrapper = mount(CartItemComp, {
            props: { item },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("3");
    });

    it("displays the line total", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2); // line_total = 39.98
        const wrapper = mount(CartItemComp, {
            props: { item },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("39.98");
    });

    it("calls updateItemQuantity with id and qty+1 when increment button clicked", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(5, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
            global: { stubs: globalStubs },
        });

        const incrementBtn = wrapper.find('[data-testid="increment"]');
        expect(incrementBtn.exists()).toBe(true);
        await incrementBtn.trigger("click");

        expect(mockUpdateItemQuantity).toHaveBeenCalledWith(5, 3);
    });

    it("calls updateItemQuantity with id and qty-1 when decrement button clicked", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(5, 3);
        const wrapper = mount(CartItemComp, {
            props: { item },
            global: { stubs: globalStubs },
        });

        const decrementBtn = wrapper.find('[data-testid="decrement"]');
        expect(decrementBtn.exists()).toBe(true);
        await decrementBtn.trigger("click");

        expect(mockUpdateItemQuantity).toHaveBeenCalledWith(5, 2);
    });

    it("calls removeItem with item id when remove button clicked", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(7, 1);
        const wrapper = mount(CartItemComp, {
            props: { item },
            global: { stubs: globalStubs },
        });

        const removeBtn = wrapper.find('[data-testid="remove"]');
        expect(removeBtn.exists()).toBe(true);
        await removeBtn.trigger("click");

        expect(mockRemoveItem).toHaveBeenCalledWith(7);
    });
});

// ---------------------------------------------------------------------------
// Cart page tests
// ---------------------------------------------------------------------------

describe("Cart page", () => {
    beforeEach(() => {
        vi.clearAllMocks();

        // Reset cart to default with items
        mockCartData.value = {
            id: "cart-uuid-1",
            items: mockCartItems,
            total: 64.97,
        };

        // Reset auth to authenticated
        vi.stubGlobal("useAuth", () => ({
            user: ref({ id: 1, name: "Alice", email: "alice@example.com" }),
            isAuthenticated: computed(() => true),
            logout: vi.fn(),
        }));

        vi.stubGlobal("useCart", () => ({
            cart: mockCartData,
            items: computed(() => mockCartData.value?.items ?? []),
            itemCount: computed(() => 0),
            fetchCart: mockFetchCart,
            updateItemQuantity: mockUpdateItemQuantity,
            removeItem: mockRemoveItem,
        }));
    });

    it("calls fetchCart on mount", async () => {
        const { default: CartPage } = await import("../pages/cart.vue");
        mount(CartPage, {
            global: {
                stubs: { NuxtLink: { template: "<a><slot /></a>" }, CartItem: true },
            },
        });
        await flushPromises();

        expect(mockFetchCart).toHaveBeenCalledOnce();
    });

    it("renders a CartItem for each item in the cart", async () => {
        const { default: CartPage } = await import("../pages/cart.vue");
        const wrapper = mount(CartPage, {
            global: {
                stubs: { NuxtLink: { template: "<a><slot /></a>" }, CartItem: true },
            },
        });
        await flushPromises();

        // With 2 cart items, there should be 2 CartItem stubs rendered
        const cartItems = wrapper.findAllComponents({ name: "CartItem" });
        expect(cartItems).toHaveLength(2);
    });

    it("shows empty cart state when there are no items", async () => {
        mockCartData.value = { id: "empty-cart", items: [], total: 0 };

        vi.stubGlobal("useCart", () => ({
            cart: mockCartData,
            items: computed(() => []),
            itemCount: computed(() => 0),
            fetchCart: mockFetchCart,
            updateItemQuantity: mockUpdateItemQuantity,
            removeItem: mockRemoveItem,
        }));

        const { default: CartPage } = await import("../pages/cart.vue");
        const wrapper = mount(CartPage, {
            global: {
                stubs: { NuxtLink: { template: "<a><slot /></a>" }, CartItem: true },
            },
        });
        await flushPromises();

        expect(wrapper.text()).toContain("empty");
    });

    it("displays order summary with cart total", async () => {
        const { default: CartPage } = await import("../pages/cart.vue");
        const wrapper = mount(CartPage, {
            global: {
                stubs: { NuxtLink: { template: "<a><slot /></a>" }, CartItem: true },
            },
        });
        await flushPromises();

        // Should display total 64.97
        expect(wrapper.text()).toContain("64.97");
    });

    it("has a Proceed to Checkout link navigating to /checkout", async () => {
        const { default: CartPage } = await import("../pages/cart.vue");
        const wrapper = mount(CartPage, {
            global: {
                stubs: {
                    NuxtLink: {
                        props: ["to"],
                        template: '<a :href="to"><slot /></a>',
                    },
                    CartItem: true,
                },
            },
        });
        await flushPromises();

        const links = wrapper.findAll("a");
        const checkoutLink = links.find(
            (l) =>
                l.text().toLowerCase().includes("checkout") || l.attributes("href") === "/checkout"
        );
        expect(checkoutLink).toBeDefined();
    });

    it("has a Continue Shopping link navigating to homepage", async () => {
        const { default: CartPage } = await import("../pages/cart.vue");
        const wrapper = mount(CartPage, {
            global: {
                stubs: {
                    NuxtLink: {
                        props: ["to"],
                        template: '<a :href="to"><slot /></a>',
                    },
                    CartItem: true,
                },
            },
        });
        await flushPromises();

        const links = wrapper.findAll("a");
        const homeLink = links.find(
            (l) =>
                l.text().toLowerCase().includes("shopping") ||
                l.text().toLowerCase().includes("continue") ||
                l.attributes("href") === "/"
        );
        expect(homeLink).toBeDefined();
    });

    it("redirects to login when user is not authenticated", async () => {
        vi.stubGlobal("useAuth", () => ({
            user: ref(null),
            isAuthenticated: computed(() => false),
            logout: vi.fn(),
        }));

        const { default: CartPage } = await import("../pages/cart.vue");
        mount(CartPage, {
            global: {
                stubs: { NuxtLink: { template: "<a><slot /></a>" }, CartItem: true },
            },
        });
        await flushPromises();

        expect(navigateTo).toHaveBeenCalledWith("/login");
    });
});
