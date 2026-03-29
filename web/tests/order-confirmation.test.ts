import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref } from "vue";
import { readFileSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const __dirname = dirname(fileURLToPath(import.meta.url));
const componentPath = resolve(__dirname, "../components/OrderConfirmation.vue");

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("definePageMeta", vi.fn());

// NuxtLink is stubbed via global stubs in mount() calls below.

// ---------------------------------------------------------------------------
// Tests for OrderConfirmation component
// ---------------------------------------------------------------------------

describe("OrderConfirmation component", () => {
    beforeEach(() => {
        vi.resetModules();
    });

    it("displays the order ID", async () => {
        const { default: OrderConfirmation } = await import("../components/OrderConfirmation.vue");
        const wrapper = mount(OrderConfirmation, {
            props: { orderId: 42, totalAmount: "99.99", createdAt: "2024-01-01T00:00:00Z" },
            global: {
                stubs: { NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' } },
            },
        });

        expect(wrapper.text()).toContain("42");
    });

    it("displays the order total amount", async () => {
        const { default: OrderConfirmation } = await import("../components/OrderConfirmation.vue");
        const wrapper = mount(OrderConfirmation, {
            props: { orderId: 7, totalAmount: "149.95", createdAt: "2024-01-01T00:00:00Z" },
            global: {
                stubs: { NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' } },
            },
        });

        expect(wrapper.text()).toContain("149.95");
    });

    it("shows an estimated delivery message", async () => {
        const { default: OrderConfirmation } = await import("../components/OrderConfirmation.vue");
        const wrapper = mount(OrderConfirmation, {
            props: { orderId: 1, totalAmount: "50.00", createdAt: "2024-01-01T00:00:00Z" },
            global: {
                stubs: { NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' } },
            },
        });

        expect(wrapper.text().toLowerCase()).toMatch(/delivery|business days/);
    });

    it("shows a link to /account/orders/{orderId}", async () => {
        const { default: OrderConfirmation } = await import("../components/OrderConfirmation.vue");
        const wrapper = mount(OrderConfirmation, {
            props: { orderId: 42, totalAmount: "99.99", createdAt: "2024-01-01T00:00:00Z" },
            global: {
                stubs: { NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' } },
            },
        });

        const links = wrapper.findAll("a");
        const orderLink = links.find((l) => l.attributes("href") === "/account/orders/42");
        expect(orderLink).toBeDefined();
    });

    it("shows a continue shopping link to /", async () => {
        const { default: OrderConfirmation } = await import("../components/OrderConfirmation.vue");
        const wrapper = mount(OrderConfirmation, {
            props: { orderId: 42, totalAmount: "99.99", createdAt: "2024-01-01T00:00:00Z" },
            global: {
                stubs: { NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' } },
            },
        });

        const links = wrapper.findAll("a");
        const homeLink = links.find((l) => l.attributes("href") === "/");
        expect(homeLink).toBeDefined();
    });

    it("shows a success heading or confirmation message", async () => {
        const { default: OrderConfirmation } = await import("../components/OrderConfirmation.vue");
        const wrapper = mount(OrderConfirmation, {
            props: { orderId: 1, totalAmount: "25.00", createdAt: "2024-01-01T00:00:00Z" },
            global: {
                stubs: { NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' } },
            },
        });

        expect(wrapper.text().toLowerCase()).toMatch(/thank you|order.*placed|success/);
    });
});

// ---------------------------------------------------------------------------
// Shadcn migration tests (T3.11)
// ---------------------------------------------------------------------------

describe("OrderConfirmation shadcn migration", () => {
    it("has no <style scoped> block", () => {
        const source = readFileSync(componentPath, "utf-8");
        expect(source).not.toMatch(/<style/);
    });

    it("has no custom CSS class names (btn, order-confirmation, etc.)", () => {
        const source = readFileSync(componentPath, "utf-8");
        expect(source).not.toMatch(/class="[^"]*\bbtn\b/);
        expect(source).not.toMatch(/class="[^"]*\border-confirmation\b/);
        expect(source).not.toMatch(/class="[^"]*\bconfirmation-heading\b/);
        expect(source).not.toMatch(/class="[^"]*\bconfirmation-subtitle\b/);
        expect(source).not.toMatch(/class="[^"]*\bconfirmation-summary\b/);
        expect(source).not.toMatch(/class="[^"]*\bconfirmation-actions\b/);
        expect(source).not.toMatch(/class="[^"]*\bdelivery-message\b/);
    });

    it("imports and uses Alert component", () => {
        const source = readFileSync(componentPath, "utf-8");
        expect(source).toMatch(/from ['"]@\/components\/ui\/alert['"]/);
        expect(source).toMatch(/<Alert/);
    });

    it("Alert uses green styling via class prop", () => {
        const source = readFileSync(componentPath, "utf-8");
        expect(source).toMatch(/border-green/);
        expect(source).toMatch(/bg-green/);
    });

    it("imports and uses Button component", () => {
        const source = readFileSync(componentPath, "utf-8");
        expect(source).toMatch(/from ['"]@\/components\/ui\/button['"]/);
        expect(source).toMatch(/<Button/);
    });

    it("View Order Details Button uses default variant (primary/blue)", () => {
        const source = readFileSync(componentPath, "utf-8");
        expect(source).toMatch(/variant="default"/);
    });

    it("Continue Shopping Button uses secondary variant (gray)", () => {
        const source = readFileSync(componentPath, "utf-8");
        expect(source).toMatch(/variant="secondary"/);
    });

    it("renders Alert with role=alert attribute", async () => {
        const { default: OrderConfirmation } = await import("../components/OrderConfirmation.vue");
        const wrapper = mount(OrderConfirmation, {
            props: { orderId: 5, totalAmount: "75.00", createdAt: "2024-01-01T00:00:00Z" },
            global: {
                stubs: { NuxtLink: { props: ["to"], template: '<a :href="to"><slot /></a>' } },
            },
        });
        expect(wrapper.find('[role="alert"]').exists()).toBe(true);
    });

    it("heading has green Tailwind typography class", () => {
        const source = readFileSync(componentPath, "utf-8");
        expect(source).toMatch(/text-green-7/);
        expect(source).toMatch(/font-bold/);
    });
});
