import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed, reactive } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

// Nuxt auto-imports Vue primitives as globals in components
vi.stubGlobal("ref", ref);
vi.stubGlobal("computed", computed);
vi.stubGlobal("reactive", reactive);

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

vi.stubGlobal("definePageMeta", vi.fn());

const mockNavigateTo = vi.fn();
vi.stubGlobal("navigateTo", mockNavigateTo);

// ---------------------------------------------------------------------------
// Shared stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
};

// ---------------------------------------------------------------------------
// Tests: Add New Address page — redirect logic (T2.1 bug fix)
// ---------------------------------------------------------------------------

describe("addresses/new.vue — redirect after save", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockNavigateTo.mockReset();
        vi.resetModules();
    });

    // ── Redirect to /checkout when query.redirect === "/checkout" ─────────────

    it('navigates to /checkout when redirect query param is "/checkout"', async () => {
        const mockApi = vi.fn().mockResolvedValueOnce({});
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useRoute", () => ({
            query: { redirect: "/checkout" },
        }));

        const { default: NewAddressPage } = await import(
            "../pages/account/addresses/new.vue"
        );
        const wrapper = mount(NewAddressPage, { global: { stubs: globalStubs } });

        // Fill required fields so the form is valid
        await wrapper.find("#country").setValue("US");
        await wrapper.find("#city").setValue("Springfield");
        await wrapper.find("#address_line_1").setValue("123 Main St");
        await wrapper.find("#postcode").setValue("62701");

        await wrapper.find("form").trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(mockNavigateTo).toHaveBeenCalledWith("/checkout");
    });

    // ── Redirect to /account/addresses when no redirect param ────────────────

    it("navigates to /account/addresses when redirect query param is absent", async () => {
        const mockApi = vi.fn().mockResolvedValueOnce({});
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useRoute", () => ({
            query: {},
        }));

        const { default: NewAddressPage } = await import(
            "../pages/account/addresses/new.vue"
        );
        const wrapper = mount(NewAddressPage, { global: { stubs: globalStubs } });

        await wrapper.find("#country").setValue("US");
        await wrapper.find("#city").setValue("Springfield");
        await wrapper.find("#address_line_1").setValue("123 Main St");
        await wrapper.find("#postcode").setValue("62701");

        await wrapper.find("form").trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(mockNavigateTo).toHaveBeenCalledWith("/account/addresses");
    });

    // ── Old bug: "checkout" without leading slash must NOT redirect to /checkout ─

    it('does NOT redirect to /checkout when redirect query param is "checkout" (no slash)', async () => {
        const mockApi = vi.fn().mockResolvedValueOnce({});
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useRoute", () => ({
            query: { redirect: "checkout" }, // old buggy value without leading slash
        }));

        const { default: NewAddressPage } = await import(
            "../pages/account/addresses/new.vue"
        );
        const wrapper = mount(NewAddressPage, { global: { stubs: globalStubs } });

        await wrapper.find("#country").setValue("US");
        await wrapper.find("#city").setValue("Springfield");
        await wrapper.find("#address_line_1").setValue("123 Main St");
        await wrapper.find("#postcode").setValue("62701");

        await wrapper.find("form").trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        // Must fall back to /account/addresses — not /checkout
        expect(mockNavigateTo).toHaveBeenCalledWith("/account/addresses");
        expect(mockNavigateTo).not.toHaveBeenCalledWith("/checkout");
    });

    // ── Invalid / arbitrary redirect values are rejected ─────────────────────

    it("falls back to /account/addresses for an arbitrary redirect value", async () => {
        const mockApi = vi.fn().mockResolvedValueOnce({});
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useRoute", () => ({
            query: { redirect: "https://evil.example.com" },
        }));

        const { default: NewAddressPage } = await import(
            "../pages/account/addresses/new.vue"
        );
        const wrapper = mount(NewAddressPage, { global: { stubs: globalStubs } });

        await wrapper.find("#country").setValue("US");
        await wrapper.find("#city").setValue("Springfield");
        await wrapper.find("#address_line_1").setValue("123 Main St");
        await wrapper.find("#postcode").setValue("62701");

        await wrapper.find("form").trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(mockNavigateTo).toHaveBeenCalledWith("/account/addresses");
        expect(mockNavigateTo).not.toHaveBeenCalledWith("https://evil.example.com");
    });

    // ── API error handling remains intact ─────────────────────────────────────

    it("shows error message when API call fails and does NOT navigate", async () => {
        const mockApi = vi
            .fn()
            .mockRejectedValueOnce({ data: { message: "Validation failed" } });
        vi.stubGlobal("useApi", () => mockApi);
        vi.stubGlobal("useRoute", () => ({
            query: { redirect: "/checkout" },
        }));

        const { default: NewAddressPage } = await import(
            "../pages/account/addresses/new.vue"
        );
        const wrapper = mount(NewAddressPage, { global: { stubs: globalStubs } });

        await wrapper.find("#country").setValue("US");
        await wrapper.find("#city").setValue("Springfield");
        await wrapper.find("#address_line_1").setValue("123 Main St");
        await wrapper.find("#postcode").setValue("62701");

        await wrapper.find("form").trigger("submit");
        await wrapper.vm.$nextTick();
        await wrapper.vm.$nextTick();

        expect(mockNavigateTo).not.toHaveBeenCalled();
        expect(wrapper.find(".error-msg").exists()).toBe(true);
        expect(wrapper.find(".error-msg").text()).toContain("Validation failed");
    });
});
