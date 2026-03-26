import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { ref, computed, onMounted, h, defineComponent } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("definePageMeta", vi.fn());

// onMounted: run immediately so side-effects execute in tests
vi.stubGlobal("onMounted", (cb: () => void | Promise<void>) => {
    return onMounted(cb);
});

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);

// useState: plain ref per key (no SSR sharing needed in tests)
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

vi.stubGlobal("useApi", () => vi.fn());

// ---------------------------------------------------------------------------
// Auth stub — default: authenticated
// ---------------------------------------------------------------------------
const mockIsAuthenticated = ref(true);
vi.stubGlobal("useAuth", () => ({
    isAuthenticated: mockIsAuthenticated,
}));

// ---------------------------------------------------------------------------
// useCheckout stub — default: no confirmation, empty addresses
// ---------------------------------------------------------------------------
const mockOrderConfirmation = ref<null | {
    id: number;
    total_amount: string;
    status: string;
    created_at: string;
}>(null);
const mockAddresses = ref<unknown[]>([]);
const mockBillingAddressId = ref<number | null>(null);
const mockShippingAddressId = ref<number | null>(null);
const mockError = ref<string | null>(null);
const mockIsSubmitting = ref(false);
const mockFetchAddresses = vi.fn();
const mockSelectBillingAddress = vi.fn();
const mockSelectShippingAddress = vi.fn();
const mockSubmitOrder = vi.fn();

vi.stubGlobal("useCheckout", () => ({
    addresses: mockAddresses,
    billingAddressId: mockBillingAddressId,
    shippingAddressId: mockShippingAddressId,
    orderConfirmation: mockOrderConfirmation,
    error: mockError,
    isSubmitting: mockIsSubmitting,
    fetchAddresses: mockFetchAddresses,
    selectBillingAddress: mockSelectBillingAddress,
    selectShippingAddress: mockSelectShippingAddress,
    submitOrder: mockSubmitOrder,
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const SESSION_KEY = "checkout.orderConfirmation";

const sampleConfirmation = {
    id: 42,
    total_amount: "99.99",
    status: "pending",
    created_at: "2024-01-01T00:00:00Z",
};

// OrderConfirmation stub as a proper component
const OrderConfirmationStub = defineComponent({
    name: "OrderConfirmation",
    props: {
        orderId: { type: Number, required: true },
        totalAmount: { type: String, required: true },
        createdAt: { type: String, required: true },
    },
    setup(props) {
        return () =>
            h("div", { "data-testid": "order-confirmation" }, [
                h("p", `Order #${props.orderId}`),
                h("p", `Total: $${props.totalAmount}`),
                h("a", { href: `/account/orders/${props.orderId}` }, "View Order"),
                h("a", { href: "/" }, "Continue Shopping"),
            ]);
    },
});

async function mountCheckoutPage() {
    const { default: CheckoutPage } = await import("../pages/checkout.vue");
    const wrapper = mount(CheckoutPage, {
        global: {
            stubs: {
                NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' },
                OrderConfirmation: OrderConfirmationStub,
            },
        },
    });
    await flushPromises();
    return wrapper;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("checkout.vue page", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        sessionStorage.clear();
        vi.resetModules();

        // Reset stubs to defaults
        mockIsAuthenticated.value = true;
        mockOrderConfirmation.value = null;
        mockAddresses.value = [];
        mockBillingAddressId.value = null;
        mockShippingAddressId.value = null;
        mockError.value = null;
        mockIsSubmitting.value = false;
    });

    // ── Authentication ─────────────────────────────────────────────────────────

    it("redirects unauthenticated users to /login", async () => {
        mockIsAuthenticated.value = false;
        await mountCheckoutPage();
        expect(mockNavigateTo).toHaveBeenCalledWith("/login");
    });

    it("does not redirect when user is authenticated", async () => {
        mockIsAuthenticated.value = true;
        await mountCheckoutPage();
        expect(mockNavigateTo).not.toHaveBeenCalled();
    });

    // ── Checkout form visible ─────────────────────────────────────────────────

    it("shows the checkout form when there is no order confirmation", async () => {
        mockOrderConfirmation.value = null;
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find(".checkout-form").exists()).toBe(true);
        expect(wrapper.find('[data-testid="order-confirmation"]').exists()).toBe(false);
    });

    // ── Order confirmation display ─────────────────────────────────────────────

    it("shows OrderConfirmation component when orderConfirmation is set", async () => {
        mockOrderConfirmation.value = sampleConfirmation;
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find('[data-testid="order-confirmation"]').exists()).toBe(true);
        expect(wrapper.find(".checkout-form").exists()).toBe(false);
    });

    it("passes orderId to OrderConfirmation", async () => {
        mockOrderConfirmation.value = sampleConfirmation;
        const wrapper = await mountCheckoutPage();
        expect(wrapper.text()).toContain("42");
    });

    it("passes totalAmount to OrderConfirmation", async () => {
        mockOrderConfirmation.value = sampleConfirmation;
        const wrapper = await mountCheckoutPage();
        expect(wrapper.text()).toContain("99.99");
    });

    // ── sessionStorage persistence ─────────────────────────────────────────────

    it("persists orderConfirmation in sessionStorage after successful submit", async () => {
        // submitOrder sets orderConfirmation as a side effect
        mockSubmitOrder.mockImplementation(() => {
            mockOrderConfirmation.value = sampleConfirmation;
        });
        mockAddresses.value = [
            {
                id: 1,
                street: "1 Main St",
                city: "Springfield",
                state: "IL",
                zip: "62701",
                country: "US",
                phone: "555-0001",
            },
        ];

        const wrapper = await mountCheckoutPage();

        // Trigger handleSubmitOrder
        await wrapper.vm.handleSubmitOrder();
        await flushPromises();

        const stored = sessionStorage.getItem(SESSION_KEY);
        expect(stored).not.toBeNull();
        const parsed = JSON.parse(stored!);
        expect(parsed.id).toBe(42);
        expect(parsed.total_amount).toBe("99.99");
    });

    it("restores orderConfirmation from sessionStorage on page load", async () => {
        // Pre-populate sessionStorage
        sessionStorage.setItem(SESSION_KEY, JSON.stringify(sampleConfirmation));

        const wrapper = await mountCheckoutPage();

        // The confirmation should be shown (restored from sessionStorage)
        expect(wrapper.find('[data-testid="order-confirmation"]').exists()).toBe(true);
    });

    it("does not fetch addresses when sessionStorage has a prior confirmation", async () => {
        sessionStorage.setItem(SESSION_KEY, JSON.stringify(sampleConfirmation));
        await mountCheckoutPage();
        expect(mockFetchAddresses).not.toHaveBeenCalled();
    });

    it("fetches addresses on mount when no confirmation exists", async () => {
        mockAddresses.value = [];
        await mountCheckoutPage();
        expect(mockFetchAddresses).toHaveBeenCalled();
    });

    // ── handleSubmitOrder ──────────────────────────────────────────────────────

    it("calls submitOrder() when Submit Order button is clicked", async () => {
        mockAddresses.value = [
            {
                id: 1,
                street: "1 Main St",
                city: "Springfield",
                state: "IL",
                zip: "62701",
                country: "US",
                phone: "555-0001",
            },
        ];
        mockBillingAddressId.value = 1;
        mockShippingAddressId.value = 1;

        const wrapper = await mountCheckoutPage();
        const btn = wrapper.find(".submit-order-btn");
        await btn.trigger("click");
        await flushPromises();

        expect(mockSubmitOrder).toHaveBeenCalled();
    });

    it("submit button is disabled when billing or shipping address is not selected", async () => {
        mockAddresses.value = [
            {
                id: 1,
                street: "1 Main St",
                city: "Springfield",
                state: "IL",
                zip: "62701",
                country: "US",
                phone: "555-0001",
            },
        ];
        // IDs stay null (defaults)
        const wrapper = await mountCheckoutPage();
        const btn = wrapper.find(".submit-order-btn");
        expect(btn.attributes("disabled")).toBeDefined();
    });
});
