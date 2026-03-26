import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { ref } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Page not found" }));
vi.stubGlobal("clearError", vi.fn());
vi.stubGlobal("useRouter", () => ({ back: vi.fn() }));

// ---------------------------------------------------------------------------
// Shared stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
    NuxtLayout: { template: "<div><slot /></div>" },
};

// ---------------------------------------------------------------------------
// Catch-all [...slug].vue tests
// ---------------------------------------------------------------------------

describe("Catch-all 404 page ([...slug].vue)", () => {
    it("renders a 404 / page not found message", async () => {
        const { default: CatchAll } = await import("../pages/[...slug].vue");
        const wrapper = mount(CatchAll, { global: { stubs: globalStubs } });
        expect(wrapper.text().toLowerCase()).toContain("not found");
    });

    it("includes a link to the homepage", async () => {
        const { default: CatchAll } = await import("../pages/[...slug].vue");
        const wrapper = mount(CatchAll, { global: { stubs: globalStubs } });
        // NuxtLink is stubbed as <a>, check that some anchor/link element exists
        expect(wrapper.find("a").exists()).toBe(true);
    });

    it("includes a way to go back or go home", async () => {
        const { default: CatchAll } = await import("../pages/[...slug].vue");
        const wrapper = mount(CatchAll, { global: { stubs: globalStubs } });
        const text = wrapper.text().toLowerCase();
        expect(text).toMatch(/home|back/);
    });
});

// ---------------------------------------------------------------------------
// error.vue tests
// ---------------------------------------------------------------------------

describe("error.vue (Nuxt error boundary)", () => {
    it("renders a generic error message for non-404 errors", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 500, message: "Internal Server Error" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        const text = wrapper.text().toLowerCase();
        expect(text).toMatch(/something went wrong|server error|error/);
    });

    it("renders page not found message for 404 errors", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        expect(wrapper.text().toLowerCase()).toContain("not found");
    });

    it("displays the statusCode in the rendered output", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("404");
    });

    it("includes a Home navigation link", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 404, message: "Not Found" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        expect(wrapper.text().toLowerCase()).toContain("home");
    });

    it("includes a Back navigation option", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 500, message: "Internal Server Error" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        const text = wrapper.text().toLowerCase();
        expect(text).toContain("back");
    });

    it("shows 500 statusCode for server errors", async () => {
        vi.stubGlobal("useError", () => ref({ statusCode: 500, message: "Internal Server Error" }));
        const { default: ErrorPage } = await import("../error.vue");
        const wrapper = mount(ErrorPage, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("500");
    });
});
