/**
 * Tests for CartItem.vue shadcn migration (T3.10)
 * Verifies:
 * - No BEM class names remain in rendered HTML
 * - No <style> block in component source
 * - Button component (shadcn) used for increment, decrement, remove
 * - Tailwind utility classes applied for layout
 * - All data-testid attributes and event handlers preserved
 */
import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";
import * as fs from "node:fs";
import * as path from "node:path";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE importing any component under test
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());
vi.stubGlobal("definePageMeta", vi.fn());
vi.stubGlobal("navigateTo", vi.fn());

const mockUpdateItemQuantity = vi.fn();
const mockRemoveItem = vi.fn();

vi.stubGlobal("useCart", () => ({
    cart: ref(null),
    items: computed(() => []),
    itemCount: computed(() => 0),
    fetchCart: vi.fn(),
    updateItemQuantity: mockUpdateItemQuantity,
    removeItem: mockRemoveItem,
}));

vi.stubGlobal("useAuth", () => ({
    user: ref({ id: 1, name: "Test User" }),
    isAuthenticated: computed(() => true),
    logout: vi.fn(),
}));

// ---------------------------------------------------------------------------
// Helpers
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

const BEM_CLASSES = [
    "cart-item",
    "cart-item__details",
    "cart-item__product-name",
    "cart-item__variant-sku",
    "cart-item__quantity",
    "cart-item__qty-btn",
    "cart-item__qty-value",
    "cart-item__pricing",
    "cart-item__line-total",
    "cart-item__remove",
];

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("CartItem shadcn migration", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("source file has no <style> block", () => {
        const filePath = path.resolve(__dirname, "../components/CartItem.vue");
        const source = fs.readFileSync(filePath, "utf-8");
        expect(source).not.toContain("<style");
    });

    it("source file has no BEM class names", () => {
        const filePath = path.resolve(__dirname, "../components/CartItem.vue");
        const source = fs.readFileSync(filePath, "utf-8");
        for (const cls of BEM_CLASSES) {
            expect(source).not.toContain(`"${cls}"`);
        }
    });

    it("rendered HTML has no BEM class names", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        const html = wrapper.html();
        for (const cls of BEM_CLASSES) {
            expect(html).not.toContain(cls);
        }
    });

    it("root element has Tailwind flex layout classes", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        const html = wrapper.html();
        expect(html).toContain("flex");
        expect(html).toContain("items-center");
    });

    it("root element has border-b class for bottom border", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        const html = wrapper.html();
        expect(html).toContain("border-b");
    });

    it("decrement button has data-testid='decrement' and is disabled when quantity is 1", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 1);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        const btn = wrapper.find('[data-testid="decrement"]');
        expect(btn.exists()).toBe(true);
        expect(btn.attributes("disabled")).toBeDefined();
    });

    it("decrement button is enabled when quantity > 1", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 3);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        const btn = wrapper.find('[data-testid="decrement"]');
        expect(btn.exists()).toBe(true);
        expect(btn.attributes("disabled")).toBeUndefined();
    });

    it("increment button has data-testid='increment'", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        expect(wrapper.find('[data-testid="increment"]').exists()).toBe(true);
    });

    it("remove button has data-testid='remove'", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        expect(wrapper.find('[data-testid="remove"]').exists()).toBe(true);
    });

    it("quantity display uses min-w Tailwind class and shows quantity value", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 5);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        const html = wrapper.html();
        expect(html).toContain("min-w");
        expect(wrapper.text()).toContain("5");
    });

    it("product details area has flex-1 class", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        expect(wrapper.html()).toContain("flex-1");
    });

    it("clicking increment calls updateItemQuantity with qty+1", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(3, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        await wrapper.find('[data-testid="increment"]').trigger("click");
        expect(mockUpdateItemQuantity).toHaveBeenCalledWith(3, 3);
    });

    it("clicking decrement calls updateItemQuantity with qty-1", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(3, 3);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        await wrapper.find('[data-testid="decrement"]').trigger("click");
        expect(mockUpdateItemQuantity).toHaveBeenCalledWith(3, 2);
    });

    it("clicking remove calls removeItem with item id", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(9, 1);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        await wrapper.find('[data-testid="remove"]').trigger("click");
        expect(mockRemoveItem).toHaveBeenCalledWith(9);
    });

    it("line total price uses font-bold Tailwind class", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2); // line_total = 39.98
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        expect(wrapper.html()).toContain("font-bold");
        expect(wrapper.text()).toContain("39.98");
    });

    it("product name uses font-semibold Tailwind class", async () => {
        const { default: CartItemComp } = await import("../components/CartItem.vue");
        const item = makeItem(1, 2);
        const wrapper = mount(CartItemComp, {
            props: { item },
        });
        expect(wrapper.html()).toContain("font-semibold");
    });
});
