import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { ref, computed, reactive, onMounted, watch, nextTick, h, defineComponent } from "vue";

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

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);

vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());

// ---------------------------------------------------------------------------
// Auth stub — authenticated by default
// ---------------------------------------------------------------------------
const mockIsAuthenticated = ref(true);
vi.stubGlobal("useAuth", () => ({
    isAuthenticated: mockIsAuthenticated,
}));

// ---------------------------------------------------------------------------
// useCheckout stub — addresses present by default so modal button renders
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

describe("checkout.vue — address modal overlay (T2.3)", () => {
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

    // ── Visibility (v-if) ─────────────────────────────────────────────────────

    it("modal overlay is NOT rendered when showAddressModal is false", async () => {
        const wrapper = await mountCheckoutPage();
        expect(wrapper.find(".modal-overlay").exists()).toBe(false);
    });

    it("modal overlay IS rendered when showAddressModal is set to true", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(wrapper.find(".modal-overlay").exists()).toBe(true);
    });

    // ── ARIA attributes ───────────────────────────────────────────────────────

    it("modal overlay has role='dialog'", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(wrapper.find(".modal-overlay").attributes("role")).toBe("dialog");
    });

    it("modal overlay has aria-modal='true'", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(wrapper.find(".modal-overlay").attributes("aria-modal")).toBe("true");
    });

    it("modal overlay has aria-labelledby pointing to the modal title id", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        const labelledBy = wrapper.find(".modal-overlay").attributes("aria-labelledby");
        expect(labelledBy).toBeTruthy();
        // The referenced element must exist in the modal
        const titleEl = wrapper.find(`#${labelledBy}`);
        expect(titleEl.exists()).toBe(true);
    });

    it("modal title element has the correct id used by aria-labelledby", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(wrapper.find("#address-modal-title").exists()).toBe(true);
    });

    // ── Modal structure ───────────────────────────────────────────────────────

    it("modal contains a header with the modal title", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(wrapper.find(".modal-header").exists()).toBe(true);
        expect(wrapper.find(".modal-header").text()).toBeTruthy();
    });

    it("modal contains a form element", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(wrapper.find(".modal-overlay form").exists()).toBe(true);
    });

    it("modal contains a footer with action buttons", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(wrapper.find(".modal-footer").exists()).toBe(true);
        expect(wrapper.find(".modal-footer button").exists()).toBe(true);
    });

    // ── Close behaviours ──────────────────────────────────────────────────────

    it("clicking the Cancel/close button sets showAddressModal to false", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();

        const cancelBtn = wrapper.find(".modal-footer .btn-secondary");
        expect(cancelBtn.exists()).toBe(true);
        await cancelBtn.trigger("click");
        expect(vm.showAddressModal).toBe(false);
    });

    it("clicking the overlay background (outside modal content) closes the modal", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();

        // Trigger click directly on overlay (simulates click.self)
        await wrapper.find(".modal-overlay").trigger("click");
        expect(vm.showAddressModal).toBe(false);
    });

    it("pressing Escape key closes the modal", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();

        await wrapper.find(".modal-overlay").trigger("keydown", { key: "Escape" });
        expect(vm.showAddressModal).toBe(false);
    });

    it("modal is removed from DOM after closing via cancel button", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        expect(wrapper.find(".modal-overlay").exists()).toBe(true);

        await wrapper.find(".modal-footer .btn-secondary").trigger("click");
        expect(wrapper.find(".modal-overlay").exists()).toBe(false);
    });

    // ── Focus management ──────────────────────────────────────────────────────

    it("modal overlay has tabindex='-1' to allow programmatic focus", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        // The modal content box (or overlay) should have tabindex="-1"
        const focusable = wrapper.find("[tabindex='-1']");
        expect(focusable.exists()).toBe(true);
    });

    it("Tab on last focusable element wraps to first and calls preventDefault", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as {
            showAddressModal: boolean;
            handleModalTabKey: (e: KeyboardEvent) => void;
        };
        vm.showAddressModal = true;
        await flushPromises();

        const overlay = wrapper.find(".modal-overlay");
        const focusableEls = overlay.element.querySelectorAll<HTMLElement>(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        const last = focusableEls[focusableEls.length - 1];
        const first = focusableEls[0];

        // Simulate focus being on the last element
        last.focus();
        Object.defineProperty(document, "activeElement", { value: last, configurable: true });

        const event = new KeyboardEvent("keydown", { key: "Tab", bubbles: true, cancelable: true });
        const preventDefaultSpy = vi.spyOn(event, "preventDefault");

        vm.handleModalTabKey(event);

        expect(preventDefaultSpy).toHaveBeenCalled();
    });

    it("Tab on a non-boundary element does NOT call preventDefault", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as {
            showAddressModal: boolean;
            handleModalTabKey: (e: KeyboardEvent) => void;
        };
        vm.showAddressModal = true;
        await flushPromises();

        const overlay = wrapper.find(".modal-overlay");
        const focusableEls = overlay.element.querySelectorAll<HTMLElement>(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        // Use a middle element (not first or last)
        const middle = focusableEls[Math.floor(focusableEls.length / 2)];
        middle.focus();
        Object.defineProperty(document, "activeElement", { value: middle, configurable: true });

        const event = new KeyboardEvent("keydown", { key: "Tab", bubbles: true, cancelable: true });
        const preventDefaultSpy = vi.spyOn(event, "preventDefault");

        vm.handleModalTabKey(event);

        expect(preventDefaultSpy).not.toHaveBeenCalled();
    });

    it("Shift+Tab on first focusable element wraps to last and calls preventDefault", async () => {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as {
            showAddressModal: boolean;
            handleModalTabKey: (e: KeyboardEvent) => void;
        };
        vm.showAddressModal = true;
        await flushPromises();

        const overlay = wrapper.find(".modal-overlay");
        const focusableEls = overlay.element.querySelectorAll<HTMLElement>(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );
        const first = focusableEls[0];

        first.focus();
        Object.defineProperty(document, "activeElement", { value: first, configurable: true });

        const event = new KeyboardEvent("keydown", { key: "Tab", shiftKey: true, bubbles: true, cancelable: true });
        const preventDefaultSpy = vi.spyOn(event, "preventDefault");

        vm.handleModalTabKey(event);

        expect(preventDefaultSpy).toHaveBeenCalled();
    });
});

// ---------------------------------------------------------------------------
// T2.4 — Address modal form fields
// ---------------------------------------------------------------------------

describe("checkout.vue — address modal form fields (T2.4)", () => {
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

    async function openModal() {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        return wrapper;
    }

    // ── Field rendering ───────────────────────────────────────────────────────

    it("modal form has an input for country", async () => {
        const wrapper = await openModal();
        expect(wrapper.find(".modal-form #country").exists()).toBe(true);
    });

    it("modal form has an input for city", async () => {
        const wrapper = await openModal();
        expect(wrapper.find(".modal-form #city").exists()).toBe(true);
    });

    it("modal form has an input for address_line_1", async () => {
        const wrapper = await openModal();
        expect(wrapper.find(".modal-form #address_line_1").exists()).toBe(true);
    });

    it("modal form has an input for address_line_2", async () => {
        const wrapper = await openModal();
        expect(wrapper.find(".modal-form #address_line_2").exists()).toBe(true);
    });

    it("modal form has an input for postcode", async () => {
        const wrapper = await openModal();
        expect(wrapper.find(".modal-form #postcode").exists()).toBe(true);
    });

    // ── form-group structure ──────────────────────────────────────────────────

    it("each field is wrapped in a .form-group div", async () => {
        const wrapper = await openModal();
        const formGroups = wrapper.findAll(".modal-form .form-group");
        expect(formGroups.length).toBe(5);
    });

    it("each field has an associated <label>", async () => {
        const wrapper = await openModal();
        const labels = wrapper.findAll(".modal-form .form-group label");
        expect(labels.length).toBe(5);
    });

    // ── Label text ────────────────────────────────────────────────────────────

    it("label for country reads 'Country *'", async () => {
        const wrapper = await openModal();
        const label = wrapper.find('.modal-form label[for="country"]');
        expect(label.text()).toBe("Country *");
    });

    it("label for city reads 'City *'", async () => {
        const wrapper = await openModal();
        const label = wrapper.find('.modal-form label[for="city"]');
        expect(label.text()).toBe("City *");
    });

    it("label for address_line_1 reads 'Address Line 1 *'", async () => {
        const wrapper = await openModal();
        const label = wrapper.find('.modal-form label[for="address_line_1"]');
        expect(label.text()).toBe("Address Line 1 *");
    });

    it("label for address_line_2 reads 'Address Line 2'", async () => {
        const wrapper = await openModal();
        const label = wrapper.find('.modal-form label[for="address_line_2"]');
        expect(label.text()).toBe("Address Line 2");
    });

    it("label for postcode reads 'Postcode *'", async () => {
        const wrapper = await openModal();
        const label = wrapper.find('.modal-form label[for="postcode"]');
        expect(label.text()).toBe("Postcode *");
    });

    // ── Required attributes ───────────────────────────────────────────────────

    it("country input has the required attribute", async () => {
        const wrapper = await openModal();
        expect(wrapper.find("#country").attributes("required")).toBeDefined();
    });

    it("city input has the required attribute", async () => {
        const wrapper = await openModal();
        expect(wrapper.find("#city").attributes("required")).toBeDefined();
    });

    it("address_line_1 input has the required attribute", async () => {
        const wrapper = await openModal();
        expect(wrapper.find("#address_line_1").attributes("required")).toBeDefined();
    });

    it("address_line_2 input does NOT have the required attribute", async () => {
        const wrapper = await openModal();
        expect(wrapper.find("#address_line_2").attributes("required")).toBeUndefined();
    });

    it("postcode input has the required attribute", async () => {
        const wrapper = await openModal();
        expect(wrapper.find("#postcode").attributes("required")).toBeDefined();
    });

    // ── v-model binding ───────────────────────────────────────────────────────

    it("typing in country input updates modalFormData.country", async () => {
        const wrapper = await openModal();
        const vm = wrapper.vm as unknown as { modalFormData: { country: string } };
        const input = wrapper.find<HTMLInputElement>("#country");
        await input.setValue("France");
        expect(vm.modalFormData.country).toBe("France");
    });

    it("typing in city input updates modalFormData.city", async () => {
        const wrapper = await openModal();
        const vm = wrapper.vm as unknown as { modalFormData: { city: string } };
        const input = wrapper.find<HTMLInputElement>("#city");
        await input.setValue("Paris");
        expect(vm.modalFormData.city).toBe("Paris");
    });

    it("typing in address_line_1 input updates modalFormData.address_line_1", async () => {
        const wrapper = await openModal();
        const vm = wrapper.vm as unknown as {
            modalFormData: { address_line_1: string };
        };
        const input = wrapper.find<HTMLInputElement>("#address_line_1");
        await input.setValue("12 Rue de Rivoli");
        expect(vm.modalFormData.address_line_1).toBe("12 Rue de Rivoli");
    });

    it("typing in postcode input updates modalFormData.postcode", async () => {
        const wrapper = await openModal();
        const vm = wrapper.vm as unknown as { modalFormData: { postcode: string } };
        const input = wrapper.find<HTMLInputElement>("#postcode");
        await input.setValue("75001");
        expect(vm.modalFormData.postcode).toBe("75001");
    });

    // ── Form reset on close ───────────────────────────────────────────────────

    it("modalFormData is cleared when modal is closed via Cancel button", async () => {
        const wrapper = await openModal();
        const vm = wrapper.vm as unknown as {
            showAddressModal: boolean;
            modalFormData: { country: string; city: string };
        };

        // Fill in some data
        await wrapper.find("#country").setValue("Germany");
        await wrapper.find("#city").setValue("Berlin");
        expect(vm.modalFormData.country).toBe("Germany");

        // Close via cancel button
        await wrapper.find(".modal-footer .btn-secondary").trigger("click");
        await flushPromises();

        // Data should be cleared
        expect(vm.modalFormData.country).toBe("");
        expect(vm.modalFormData.city).toBe("");
    });

    it("modalFormData fields are all empty strings when modal is first opened", async () => {
        const wrapper = await openModal();
        const vm = wrapper.vm as unknown as {
            modalFormData: {
                country: string;
                city: string;
                address_line_1: string;
                address_line_2: string;
                postcode: string;
            };
        };
        expect(vm.modalFormData.country).toBe("");
        expect(vm.modalFormData.city).toBe("");
        expect(vm.modalFormData.address_line_1).toBe("");
        expect(vm.modalFormData.address_line_2).toBe("");
        expect(vm.modalFormData.postcode).toBe("");
    });
});

// ---------------------------------------------------------------------------
// T2.5 — Address modal form validation & error display
// ---------------------------------------------------------------------------

describe("checkout.vue — address modal validation & error display (T2.5)", () => {
    let mockApiCall: ReturnType<typeof vi.fn>;

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

        // Default: API call succeeds
        mockApiCall = vi.fn().mockResolvedValue({});
        vi.stubGlobal("useApi", () => mockApiCall);
    });

    async function openModal() {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        return wrapper;
    }

    async function fillRequiredFields(wrapper: Awaited<ReturnType<typeof openModal>>) {
        await wrapper.find("#country").setValue("UK");
        await wrapper.find("#city").setValue("London");
        await wrapper.find("#address_line_1").setValue("1 Baker St");
        await wrapper.find("#postcode").setValue("NW1 6XE");
    }

    // ── No error on first open ───────────────────────────────────────────────

    it("no error message is shown when modal first opens", async () => {
        const wrapper = await openModal();
        expect(wrapper.find(".modal-form .error-msg").exists()).toBe(false);
    });

    // ── Error display on API failure ─────────────────────────────────────────

    it("error message is displayed when API returns a server error with message", async () => {
        mockApiCall.mockRejectedValueOnce({ data: { message: "Validation failed" } });
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();
        const errorEl = wrapper.find(".modal-form .error-msg");
        expect(errorEl.exists()).toBe(true);
        expect(errorEl.text()).toBe("Validation failed");
    });

    it("error message falls back to err.message when data.message is absent", async () => {
        mockApiCall.mockRejectedValueOnce({ message: "Network error" });
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();
        const errorEl = wrapper.find(".modal-form .error-msg");
        expect(errorEl.exists()).toBe(true);
        expect(errorEl.text()).toBe("Network error");
    });

    it("error message falls back to a default string when error has no message", async () => {
        mockApiCall.mockRejectedValueOnce({});
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();
        const errorEl = wrapper.find(".modal-form .error-msg");
        expect(errorEl.exists()).toBe(true);
        expect(errorEl.text()).toBeTruthy();
    });

    it("error message uses the error-msg CSS class for styling", async () => {
        mockApiCall.mockRejectedValueOnce({ data: { message: "Bad request" } });
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();
        expect(wrapper.find(".error-msg").exists()).toBe(true);
    });

    // ── Error cleared on resubmission ────────────────────────────────────────

    it("error is cleared at the start of a subsequent submission", async () => {
        mockApiCall
            .mockRejectedValueOnce({ data: { message: "First error" } })
            .mockResolvedValueOnce({});
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);

        // First submit triggers an error
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();
        expect(wrapper.find(".modal-form .error-msg").exists()).toBe(true);

        // Second submit: modal might close on success, but error should be gone
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();
        expect(wrapper.find(".modal-form .error-msg").exists()).toBe(false);
    });

    // ── Modal stays open on error ────────────────────────────────────────────

    it("modal stays open after a failed submission", async () => {
        mockApiCall.mockRejectedValueOnce({ data: { message: "Server error" } });
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();
        expect(wrapper.find(".modal-overlay").exists()).toBe(true);
    });

    // ── Modal closes on successful submission ────────────────────────────────

    it("modal closes after a successful form submission", async () => {
        mockApiCall.mockResolvedValueOnce({});
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();
        expect(wrapper.find(".modal-overlay").exists()).toBe(false);
    });
});

// ---------------------------------------------------------------------------
// T2.6 — Modal form submission: loading state, API call, response capture
// ---------------------------------------------------------------------------

describe("checkout.vue — address modal form submission (T2.6)", () => {
    let mockApiCall: ReturnType<typeof vi.fn>;

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

        // Default: API call succeeds and returns a new address
        mockApiCall = vi.fn().mockResolvedValue({
            data: {
                id: 99,
                country: "UK",
                city: "London",
                address_line_1: "1 Baker St",
                address_line_2: "",
                postcode: "NW1 6XE",
            },
        });
        vi.stubGlobal("useApi", () => mockApiCall);
    });

    async function openModal() {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        return wrapper;
    }

    async function fillRequiredFields(wrapper: Awaited<ReturnType<typeof openModal>>) {
        await wrapper.find("#country").setValue("UK");
        await wrapper.find("#city").setValue("London");
        await wrapper.find("#address_line_1").setValue("1 Baker St");
        await wrapper.find("#postcode").setValue("NW1 6XE");
    }

    // ── Submit button ─────────────────────────────────────────────────────────

    it("modal form has a submit button", async () => {
        const wrapper = await openModal();
        const submitBtn = wrapper.find('.modal-form button[type="submit"]');
        expect(submitBtn.exists()).toBe(true);
    });

    it("submit button shows 'Save Address' text by default", async () => {
        const wrapper = await openModal();
        const submitBtn = wrapper.find('.modal-form button[type="submit"]');
        expect(submitBtn.text()).toBe("Save Address");
    });

    // ── modalSubmitting ref ───────────────────────────────────────────────────

    it("modalSubmitting is false initially", async () => {
        const wrapper = await openModal();
        const vm = wrapper.vm as unknown as { modalSubmitting: boolean };
        expect(vm.modalSubmitting).toBe(false);
    });

    it("modalSubmitting is exposed via defineExpose", async () => {
        const wrapper = await openModal();
        expect("modalSubmitting" in wrapper.vm).toBe(true);
    });

    // ── Loading state during submission ───────────────────────────────────────

    it("submit button is disabled while submission is in progress", async () => {
        let resolveApi!: (value: unknown) => void;
        mockApiCall.mockReturnValue(
            new Promise((resolve) => {
                resolveApi = resolve;
            })
        );

        const wrapper = await openModal();
        await fillRequiredFields(wrapper);

        // Submit and do NOT flush promises yet — API is still pending
        wrapper.find(".modal-form").trigger("submit");
        await nextTick();

        const submitBtn = wrapper.find('.modal-form button[type="submit"]');
        expect(submitBtn.attributes("disabled")).toBeDefined();

        // Cleanup
        resolveApi({ data: { id: 99, country: "UK", city: "London", address_line_1: "1 Baker St", postcode: "NW1 6XE" } });
        await flushPromises();
    });

    it("submit button shows 'Saving...' text while submission is in progress", async () => {
        let resolveApi!: (value: unknown) => void;
        mockApiCall.mockReturnValue(
            new Promise((resolve) => {
                resolveApi = resolve;
            })
        );

        const wrapper = await openModal();
        await fillRequiredFields(wrapper);

        wrapper.find(".modal-form").trigger("submit");
        await nextTick();

        const submitBtn = wrapper.find('.modal-form button[type="submit"]');
        expect(submitBtn.text()).toBe("Saving...");

        // Cleanup
        resolveApi({ data: { id: 99, country: "UK", city: "London", address_line_1: "1 Baker St", postcode: "NW1 6XE" } });
        await flushPromises();
    });

    it("modalSubmitting resets to false after successful submission", async () => {
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        const vm = wrapper.vm as unknown as { modalSubmitting: boolean };
        expect(vm.modalSubmitting).toBe(false);
    });

    it("modalSubmitting resets to false after a failed submission", async () => {
        mockApiCall.mockRejectedValueOnce({ data: { message: "Server error" } });
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        const vm = wrapper.vm as unknown as { modalSubmitting: boolean };
        expect(vm.modalSubmitting).toBe(false);
    });

    // ── API call body ─────────────────────────────────────────────────────────

    it("API is called with POST method and the modal form data as body", async () => {
        const wrapper = await openModal();

        await wrapper.find("#country").setValue("Germany");
        await wrapper.find("#city").setValue("Berlin");
        await wrapper.find("#address_line_1").setValue("5 Unter den Linden");
        await wrapper.find("#address_line_2").setValue("Apt 2");
        await wrapper.find("#postcode").setValue("10117");

        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        expect(mockApiCall).toHaveBeenCalledWith(
            "/customers/me/addresses",
            expect.objectContaining({
                method: "POST",
                body: expect.objectContaining({
                    country: "Germany",
                    city: "Berlin",
                    address_line_1: "5 Unter den Linden",
                    address_line_2: "Apt 2",
                    postcode: "10117",
                }),
            })
        );
    });

    it("API is called with the correct endpoint '/customers/me/addresses'", async () => {
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        expect(mockApiCall).toHaveBeenCalledWith(
            "/customers/me/addresses",
            expect.anything()
        );
    });
});

// ---------------------------------------------------------------------------
// T2.7 — Post-save logic: close modal, refresh addresses, auto-select new address
// ---------------------------------------------------------------------------

describe("checkout.vue — post-save logic (T2.7)", () => {
    let mockApiCall: ReturnType<typeof vi.fn>;

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

        // Default: API POST succeeds, returns the newly created address
        mockApiCall = vi.fn().mockResolvedValue({
            data: {
                id: 99,
                country: "UK",
                city: "London",
                address_line_1: "1 Baker St",
                address_line_2: "",
                postcode: "NW1 6XE",
            },
        });
        vi.stubGlobal("useApi", () => mockApiCall);

        // Default: fetchAddresses succeeds
        mockFetchAddresses.mockResolvedValue(undefined);
    });

    async function openModal() {
        const wrapper = await mountCheckoutPage();
        const vm = wrapper.vm as unknown as { showAddressModal: boolean };
        vm.showAddressModal = true;
        await flushPromises();
        return wrapper;
    }

    async function fillRequiredFields(wrapper: Awaited<ReturnType<typeof openModal>>) {
        await wrapper.find("#country").setValue("UK");
        await wrapper.find("#city").setValue("London");
        await wrapper.find("#address_line_1").setValue("1 Baker St");
        await wrapper.find("#postcode").setValue("NW1 6XE");
    }

    // ── Auto-select new address ───────────────────────────────────────────────

    it("selectBillingAddress is called with the new address id after successful submission", async () => {
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        expect(mockSelectBillingAddress).toHaveBeenCalledWith(99);
    });

    it("selectShippingAddress is called with the new address id after successful submission", async () => {
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        expect(mockSelectShippingAddress).toHaveBeenCalledWith(99);
    });

    // ── Refresh address list ──────────────────────────────────────────────────

    it("fetchAddresses is called after successful submission", async () => {
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        expect(mockFetchAddresses).toHaveBeenCalled();
    });

    // ── Modal close on success ────────────────────────────────────────────────

    it("modal is closed after successful submission", async () => {
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        expect(wrapper.find(".modal-overlay").exists()).toBe(false);
    });

    // ── Form state reset on success ───────────────────────────────────────────

    it("form fields are cleared after successful submission", async () => {
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        const vm = wrapper.vm as unknown as {
            modalFormData: {
                country: string;
                city: string;
                address_line_1: string;
                postcode: string;
            };
        };
        expect(vm.modalFormData.country).toBe("");
        expect(vm.modalFormData.city).toBe("");
        expect(vm.modalFormData.address_line_1).toBe("");
        expect(vm.modalFormData.postcode).toBe("");
    });

    it("modalError is cleared after successful submission", async () => {
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        const vm = wrapper.vm as unknown as { modalError: string | null };
        expect(vm.modalError).toBeNull();
    });

    // ── fetchAddresses failure: modal still closes, error is shown ────────────

    it("modal is closed even if fetchAddresses fails after address creation", async () => {
        // First call (onMounted) resolves; second call (handleModalSubmit) rejects
        mockFetchAddresses
            .mockResolvedValueOnce(undefined)
            .mockRejectedValueOnce(new Error("Network error"));
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        expect(wrapper.find(".modal-overlay").exists()).toBe(false);
    });

    it("an error is shown when fetchAddresses fails after address creation", async () => {
        // First call (onMounted) resolves; second call (handleModalSubmit) rejects
        mockFetchAddresses
            .mockResolvedValueOnce(undefined)
            .mockRejectedValueOnce(new Error("Network error"));
        const wrapper = await openModal();
        await fillRequiredFields(wrapper);
        await wrapper.find(".modal-form").trigger("submit");
        await flushPromises();

        expect(wrapper.find(".error-message").exists()).toBe(true);
    });
});
