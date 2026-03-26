import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";

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

// useApi: return mockFetch directly
vi.stubGlobal("useApi", () => mockFetch);

// useState: simulate Nuxt shared state via refs
type AnyRef = ReturnType<typeof ref>;
const stateStore: Record<string, AnyRef> = {};
vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

// Stub navigateTo for redirect tests
const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);

// Default useCart stub — individual tests may override via vi.stubGlobal
const mockClearCart = vi.fn();
vi.stubGlobal("useCart", () => ({ clearCart: mockClearCart }));

// ---------------------------------------------------------------------------
// Helpers / Fixtures
// ---------------------------------------------------------------------------

const makeAddress = (id: number) => ({
    id,
    street: `${id} Main St`,
    city: "Springfield",
    state: "IL",
    zip: "62701",
    country: "US",
    phone: "555-000-000" + id,
});

const makeAddressesResponse = (ids: number[] = [1, 2]) => ({
    data: ids.map(makeAddress),
});

const makeOrderResponse = (id: number = 42, total: string = "99.99") => ({
    data: {
        id,
        total_amount: total,
        status: "pending",
        created_at: "2024-01-01T00:00:00Z",
    },
});

// ---------------------------------------------------------------------------
// Tests for useCheckout composable
// ---------------------------------------------------------------------------

describe("useCheckout composable", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();

        // Reset shared state between tests
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();
    });

    // ── fetchAddresses ─────────────────────────────────────────────────────────

    it("fetchAddresses: calls GET /customers/me/addresses and populates addresses", async () => {
        mockFetch.mockResolvedValueOnce(makeAddressesResponse([1, 2]));

        const { useCheckout } = await import("../composables/useCheckout");
        const { addresses, fetchAddresses } = useCheckout();

        await fetchAddresses();

        expect(mockFetch).toHaveBeenCalledWith("/customers/me/addresses");
        expect(addresses.value).toHaveLength(2);
        expect(addresses.value[0].id).toBe(1);
        expect(addresses.value[1].id).toBe(2);
    });

    it("fetchAddresses: addresses starts as empty array before fetch", async () => {
        const { useCheckout } = await import("../composables/useCheckout");
        const { addresses } = useCheckout();

        expect(addresses.value).toEqual([]);
    });

    // ── billing / shipping address selection ────────────────────────────────────

    it("billingAddressId and shippingAddressId start as null", async () => {
        const { useCheckout } = await import("../composables/useCheckout");
        const { billingAddressId, shippingAddressId } = useCheckout();

        expect(billingAddressId.value).toBeNull();
        expect(shippingAddressId.value).toBeNull();
    });

    it("selectBillingAddress: sets billingAddressId", async () => {
        const { useCheckout } = await import("../composables/useCheckout");
        const { billingAddressId, selectBillingAddress } = useCheckout();

        selectBillingAddress(3);

        expect(billingAddressId.value).toBe(3);
    });

    it("selectShippingAddress: sets shippingAddressId", async () => {
        const { useCheckout } = await import("../composables/useCheckout");
        const { shippingAddressId, selectShippingAddress } = useCheckout();

        selectShippingAddress(5);

        expect(shippingAddressId.value).toBe(5);
    });

    it("allows the same address to be selected for both billing and shipping", async () => {
        const { useCheckout } = await import("../composables/useCheckout");
        const { billingAddressId, shippingAddressId, selectBillingAddress, selectShippingAddress } =
            useCheckout();

        selectBillingAddress(1);
        selectShippingAddress(1);

        expect(billingAddressId.value).toBe(1);
        expect(shippingAddressId.value).toBe(1);
    });

    // ── submitOrder ────────────────────────────────────────────────────────────

    it("submitOrder: calls POST /orders with selected billing and shipping address IDs", async () => {
        mockFetch.mockResolvedValueOnce(makeOrderResponse());

        const { useCheckout } = await import("../composables/useCheckout");
        const { selectBillingAddress, selectShippingAddress, submitOrder } = useCheckout();

        selectBillingAddress(1);
        selectShippingAddress(2);
        await submitOrder();

        expect(mockFetch).toHaveBeenCalledWith(
            "/orders",
            expect.objectContaining({
                method: "POST",
                body: { billing_address_id: 1, shipping_address_id: 2 },
            })
        );
    });

    it("submitOrder: sets orderConfirmation on success with order ID and total", async () => {
        mockFetch.mockResolvedValueOnce(makeOrderResponse(42, "99.99"));

        const { useCheckout } = await import("../composables/useCheckout");
        const { orderConfirmation, selectBillingAddress, selectShippingAddress, submitOrder } =
            useCheckout();

        selectBillingAddress(1);
        selectShippingAddress(2);
        await submitOrder();

        expect(orderConfirmation.value).not.toBeNull();
        expect(orderConfirmation.value!.id).toBe(42);
        expect(orderConfirmation.value!.total_amount).toBe("99.99");
    });

    it("submitOrder: clears cart via clearCart() on success", async () => {
        const mockClearCart = vi.fn();
        vi.stubGlobal("useCart", () => ({ clearCart: mockClearCart }));

        mockFetch.mockResolvedValueOnce(makeOrderResponse());

        const { useCheckout } = await import("../composables/useCheckout");
        const { selectBillingAddress, selectShippingAddress, submitOrder } = useCheckout();

        selectBillingAddress(1);
        selectShippingAddress(2);
        await submitOrder();

        expect(mockClearCart).toHaveBeenCalledOnce();
    });

    it("submitOrder: sets error message on API failure", async () => {
        const apiError = Object.assign(new Error("Insufficient stock"), {
            statusCode: 422,
            data: { message: "Insufficient stock for the selected items" },
        });
        mockFetch.mockRejectedValueOnce(apiError);

        const { useCheckout } = await import("../composables/useCheckout");
        const { error, selectBillingAddress, selectShippingAddress, submitOrder } = useCheckout();

        selectBillingAddress(1);
        selectShippingAddress(2);
        await submitOrder();

        expect(error.value).toBeTruthy();
        expect(error.value).toContain("Insufficient stock");
    });

    it("submitOrder: does not set orderConfirmation on API failure", async () => {
        const apiError = Object.assign(new Error("Invalid address"), { statusCode: 422 });
        mockFetch.mockRejectedValueOnce(apiError);

        const { useCheckout } = await import("../composables/useCheckout");
        const { orderConfirmation, selectBillingAddress, selectShippingAddress, submitOrder } =
            useCheckout();

        selectBillingAddress(1);
        selectShippingAddress(2);
        await submitOrder();

        expect(orderConfirmation.value).toBeNull();
    });

    it("submitOrder: clears error before each attempt", async () => {
        const apiError = Object.assign(new Error("Error"), { statusCode: 500 });
        mockFetch.mockRejectedValueOnce(apiError);

        const { useCheckout } = await import("../composables/useCheckout");
        const { error, selectBillingAddress, selectShippingAddress, submitOrder } = useCheckout();

        selectBillingAddress(1);
        selectShippingAddress(2);
        await submitOrder();

        expect(error.value).toBeTruthy();

        // Second attempt: success — error should be cleared
        mockFetch.mockResolvedValueOnce(makeOrderResponse());
        await submitOrder();

        expect(error.value).toBeNull();
    });

    // ── isSubmitting ────────────────────────────────────────────────────────────

    it("isSubmitting: starts as false", async () => {
        const { useCheckout } = await import("../composables/useCheckout");
        const { isSubmitting } = useCheckout();

        expect(isSubmitting.value).toBe(false);
    });

    it("isSubmitting: is true during submitOrder and false after completion", async () => {
        let resolveOrder!: (v: unknown) => void;
        const pendingPromise = new Promise((res) => {
            resolveOrder = res;
        });
        mockFetch.mockReturnValueOnce(pendingPromise);

        const { useCheckout } = await import("../composables/useCheckout");
        const { isSubmitting, selectBillingAddress, selectShippingAddress, submitOrder } =
            useCheckout();

        selectBillingAddress(1);
        selectShippingAddress(2);

        const submitPromise = submitOrder();
        expect(isSubmitting.value).toBe(true);

        resolveOrder(makeOrderResponse());
        await submitPromise;

        expect(isSubmitting.value).toBe(false);
    });
});
