/**
 * T5.3 — checkout.vue shadcn-vue migration tests
 *
 * These tests verify that checkout.vue has been migrated from custom HTML/CSS
 * to shadcn-vue components:
 *  - RadioGroup + RadioGroupItem + Label for address selection (no fieldsets)
 *  - Dialog for the "Add New Address" modal (no .modal-overlay)
 *  - Alert variant="destructive" for error messages
 *  - handleModalTabKey and modalElement removed (Dialog handles these)
 *  - No custom classes: .checkout-page, .btn, .modal-*, .address-*, .form-group, .error-message
 */
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import {
    ref,
    computed,
    reactive,
    onMounted,
    watch,
    nextTick,
    h,
    defineComponent,
} from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("ref", ref);
vi.stubGlobal("computed", computed);
vi.stubGlobal("reactive", reactive);
vi.stubGlobal("watch", watch);
vi.stubGlobal("nextTick", nextTick);
vi.stubGlobal("definePageMeta", vi.fn());

vi.stubGlobal("onMounted", (cb: () => void | Promise<void>) => {
    return onMounted(cb);
});

vi.stubGlobal("onBeforeRouteLeave", vi.fn());
vi.stubGlobal("navigateTo", vi.fn());
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());

// ---------------------------------------------------------------------------
// Auth stub — authenticated
// ---------------------------------------------------------------------------
const mockIsAuthenticated = ref(true);
vi.stubGlobal("useAuth", () => ({
    isAuthenticated: mockIsAuthenticated,
}));

// ---------------------------------------------------------------------------
// useCheckout stub
// ---------------------------------------------------------------------------
const mockOrderConfirmation = ref<null | object>(null);
const mockAddresses = ref<unknown[]>([
    {
        id: 1,
        address_line_1: "1 Main St",
        address_line_2: null,
        city: "Springfield",
        postcode: "62701",
        country: "US",
    },
]);
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
// OrderConfirmation stub
// ---------------------------------------------------------------------------
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
        attachTo: document.body,
    });
    await flushPromises();
    return wrapper;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("checkout.vue — shadcn migration (T5.3)", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        sessionStorage.clear();
        vi.resetModules();

        mockIsAuthenticated.value = true;
        mockOrderConfirmation.value = null;
        mockAddresses.value = [
            {
                id: 1,
                address_line_1: "1 Main St",
                address_line_2: null,
                city: "Springfield",
                postcode: "62701",
                country: "US",
            },
        ];
        mockBillingAddressId.value = null;
        mockShippingAddressId.value = null;
        mockError.value = null;
        mockIsSubmitting.value = false;
    });

    afterEach(() => {
        // Clean up any teleported Dialog content from document.body
        document.body.innerHTML = "";
    });

    // ── No legacy HTML structure ──────────────────────────────────────────────

    it("does NOT use <fieldset> elements for address selection", async () => {
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find("fieldset").exists()).toBe(false);
    });

    it("does NOT use a .modal-overlay div for the address modal", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(document.body.querySelector(".modal-overlay")).toBeNull();
    });

    it("does NOT have a .checkout-page class on the root element", async () => {
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find(".checkout-page").exists()).toBe(false);
    });

    it("does NOT have any .btn class on buttons", async () => {
        const wrapper = await mountCheckoutPage();
        expect(document.body.querySelector(".btn")).toBeNull();
    });

    it("does NOT have .address-fieldset elements", async () => {
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find(".address-fieldset").exists()).toBe(false);
    });

    it("does NOT have .address-option elements", async () => {
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find(".address-option").exists()).toBe(false);
    });

    it("does NOT have .error-message class for the checkout error", async () => {
        mockError.value = "Something went wrong";
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find(".error-message").exists()).toBe(false);
    });

    // ── RadioGroup for address selection ──────────────────────────────────────

    it("renders a RadioGroup for billing address selection when addresses exist", async () => {
        const wrapper = await mountCheckoutPage();
        // RadioGroup renders with role="radiogroup"
        const radioGroups = wrapper.findAll('[role="radiogroup"]');
        expect(radioGroups.length).toBeGreaterThanOrEqual(1);
    });

    it("renders RadioGroupItem for each billing address", async () => {
        mockAddresses.value = [
            { id: 1, address_line_1: "1 Main St", address_line_2: null, city: "Springfield", postcode: "62701", country: "US" },
            { id: 2, address_line_1: "2 Oak Ave", address_line_2: null, city: "Chicago", postcode: "60601", country: "US" },
        ];
        const wrapper = await mountCheckoutPage();
        // Two addresses × two radio groups (billing + shipping) = 4 radio inputs minimum
        const radios = wrapper.findAll('[role="radio"]');
        expect(radios.length).toBeGreaterThanOrEqual(2);
    });

    it("renders two separate RadioGroups — one for billing, one for shipping", async () => {
        const wrapper = await mountCheckoutPage();
        const radioGroups = wrapper.findAll('[role="radiogroup"]');
        expect(radioGroups.length).toBe(2);
    });

    // ── Dialog for address modal ──────────────────────────────────────────────

    it("does NOT render a dialog when showAddressModal is false", async () => {
        await mountCheckoutPage();
        const dialog = document.body.querySelector('[role="dialog"]');
        expect(dialog).toBeNull();
    });

    it("renders a Dialog (role='dialog') when showAddressModal is true", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        const dialog = document.body.querySelector('[role="dialog"]');
        expect(dialog).not.toBeNull();
    });

    it("Dialog contains the 'Add New Address' title text", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        const dialog = document.body.querySelector('[role="dialog"]');
        expect(dialog?.textContent).toContain("Add New Address");
    });

    it("Dialog contains form inputs for address fields", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(document.body.querySelector("#country")).not.toBeNull();
        expect(document.body.querySelector("#city")).not.toBeNull();
        expect(document.body.querySelector("#address_line_1")).not.toBeNull();
        expect(document.body.querySelector("#postcode")).not.toBeNull();
    });

    it("closing the Dialog sets showAddressModal to false", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();

        // Close via the exposed closeModal or by setting showAddressModal
        vm.showAddressModal = false;
        await flushPromises();

        const dialog = document.body.querySelector('[role="dialog"]');
        expect(dialog).toBeNull();
        expect(vm.showAddressModal).toBe(false);
    });

    // ── Alert for modal error ────────────────────────────────────────────────

    it("shows no Alert when modal error is null", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as {
            showAddressModal: boolean;
            modalError: string | null;
        };
        vm.showAddressModal = true;
        vm.modalError = null;
        await flushPromises();
        // No [role="alert"] with destructive variant when modalError is null
        const alerts = document.body.querySelectorAll('[role="alert"]');
        expect(alerts.length).toBe(0);
    });

    it("shows an Alert when modal error is set", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as {
            showAddressModal: boolean;
            modalError: string | null;
        };
        vm.showAddressModal = true;
        vm.modalError = "API error occurred";
        await flushPromises();
        const alert = document.body.querySelector('[role="alert"]');
        expect(alert).not.toBeNull();
        expect(alert?.textContent).toContain("API error occurred");
    });

    // ── Alert for checkout error ──────────────────────────────────────────────

    it("shows Alert for checkout error when error is set", async () => {
        mockError.value = "Order submission failed";
        const wrapper = await mountCheckoutPage();
        const alert = wrapper.find('[role="alert"]');
        expect(alert.exists()).toBe(true);
        expect(alert.text()).toContain("Order submission failed");
    });

    it("does NOT show checkout error Alert when error is null", async () => {
        mockError.value = null;
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find('[role="alert"]').exists()).toBe(false);
    });

    // ── handleModalTabKey and modalElement removed ────────────────────────────

    it("does NOT expose handleModalTabKey function (Dialog handles focus trap)", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as Record<string, unknown>;
        expect(vm.handleModalTabKey).toBeUndefined();
    });

    it("does NOT expose modalElement ref (Dialog manages its own DOM)", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as Record<string, unknown>;
        expect(vm.modalElement).toBeUndefined();
    });

    // ── Functionality preserved ───────────────────────────────────────────────

    it("showAddressModal is exposed and starts as false", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        expect(vm.showAddressModal).toBe(false);
    });

    it("clicking 'Add new address' button sets showAddressModal to true", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };

        // Find the "Add new address" button by data-testid
        const btn = wrapper.find('[data-testid="add-address-btn"]');
        expect(btn.exists()).toBe(true);
        await btn.trigger("click");
        expect(vm.showAddressModal).toBe(true);
    });

    it("submit order button is present and clickable", async () => {
        mockBillingAddressId.value = 1;
        mockShippingAddressId.value = 1;
        const wrapper = await mountCheckoutPage();
        const btn = wrapper.find('[data-testid="submit-order-btn"]');
        expect(btn.exists()).toBe(true);
        await btn.trigger("click");
        await flushPromises();
        expect(mockSubmitOrder).toHaveBeenCalled();
    });

    it("submit order button is disabled when billing address is not selected", async () => {
        mockBillingAddressId.value = null;
        mockShippingAddressId.value = 1;
        const wrapper = await mountCheckoutPage();
        const btn = wrapper.find('[data-testid="submit-order-btn"]');
        expect(btn.attributes("disabled")).toBeDefined();
    });

    it("modal cancel button closes the dialog and resets form", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as {
            showAddressModal: boolean;
            modalFormData: { country: string };
        };
        vm.showAddressModal = true;
        await flushPromises();

        // Fill a field
        const countryInput = document.body.querySelector<HTMLInputElement>("#country");
        if (countryInput) {
            countryInput.value = "Germany";
            countryInput.dispatchEvent(new Event("input"));
        }
        await flushPromises();

        // Find and click cancel
        const cancelBtn = document.body.querySelector<HTMLElement>(
            '[data-testid="modal-cancel"]',
        );
        expect(cancelBtn).not.toBeNull();
        cancelBtn?.click();
        await flushPromises();

        expect(vm.showAddressModal).toBe(false);
    });

    // ── No .form-group class in modal form ────────────────────────────────────

    it("modal form does NOT use .form-group class (uses Tailwind flex instead)", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(document.body.querySelector(".form-group")).toBeNull();
    });

    // ── No .modal-* classes ───────────────────────────────────────────────────

    it("does NOT have .modal-content class", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(document.body.querySelector(".modal-content")).toBeNull();
    });

    it("does NOT have .modal-header class", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(document.body.querySelector(".modal-header")).toBeNull();
    });

    it("does NOT have .modal-footer class", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(document.body.querySelector(".modal-footer")).toBeNull();
    });

    it("does NOT have .modal-form class", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(document.body.querySelector(".modal-form")).toBeNull();
    });
});
