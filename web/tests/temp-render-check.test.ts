import { describe, it, expect, vi } from "vitest";
import { mount, flushPromises } from "@vue/test-utils";
import { ref, reactive, onMounted, watch, nextTick, computed } from "vue";

vi.stubGlobal("ref", ref);
vi.stubGlobal("computed", computed);
vi.stubGlobal("reactive", reactive);
vi.stubGlobal("watch", watch);
vi.stubGlobal("nextTick", nextTick);
vi.stubGlobal("definePageMeta", vi.fn());
vi.stubGlobal("onMounted", (cb: () => void) => onMounted(cb));
vi.stubGlobal("onBeforeRouteLeave", vi.fn());
vi.stubGlobal("navigateTo", vi.fn());
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());
vi.stubGlobal("useAuth", () => ({ isAuthenticated: ref(true) }));
vi.stubGlobal("useCheckout", () => ({
    addresses: ref([{id:1,address_line_1:"1 Main St",address_line_2:null,city:"Springfield",postcode:"62701",country:"US"}]),
    billingAddressId: ref(null),
    shippingAddressId: ref(null),
    orderConfirmation: ref(null),
    error: ref(null),
    isSubmitting: ref(false),
    fetchAddresses: vi.fn(),
    selectBillingAddress: vi.fn(),
    selectShippingAddress: vi.fn(),
    submitOrder: vi.fn(),
}));

const origConsoleError = console.error;
const origConsoleWarn = console.warn;

describe("render check", () => {
    it("renders checkout form with content", async () => {
        const errors: unknown[] = [];
        console.error = (...args: unknown[]) => { errors.push(args); origConsoleError(...args); };
        console.warn = (...args: unknown[]) => { origConsoleWarn(...args); };
        
        const { default: CheckoutPage } = await import("../pages/checkout.vue");
        const wrapper = mount(CheckoutPage, {
            global: {
                stubs: { NuxtLink: { template: '<a><slot /></a>' }, OrderConfirmation: { template: '<div />' } },
            },
            attachTo: document.body,
        });
        await flushPromises();
        console.error = origConsoleError;
        
        console.log("Errors during render:", JSON.stringify(errors.map(e => String(e)).join('\n')));
        console.log("FULL HTML:", wrapper.html().substring(0, 1500));
        expect(wrapper.html()).toContain('checkout-form');
    });
});
