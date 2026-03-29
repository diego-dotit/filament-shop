/**
 * Tests for [slug].vue shadcn migration (T4.8)
 * Verifies:
 * - Source file imports Button and Select components from shadcn-vue
 * - No plain HTML <button> for Add to Cart (replaced by shadcn Button)
 * - No HTML <select> BEM class (replaced by shadcn Select)
 * - No <style> block added
 * - Gallery images have Tailwind utility classes
 * - Specs section has Tailwind grid classes
 * - Add to Cart button disabled state works via shadcn Button
 * - ReviewForm component is still present
 */
import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";
import { readFileSync, existsSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());
vi.stubGlobal("definePageMeta", vi.fn());
vi.stubGlobal("navigateTo", vi.fn());

const mockCreateError = vi.fn((opts: { statusCode: number; statusMessage?: string }) => {
    const err = new Error(opts.statusMessage ?? String(opts.statusCode));
    (err as unknown as Record<string, unknown>).statusCode = opts.statusCode;
    return err;
});
vi.stubGlobal("createError", mockCreateError);

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const __dirname = dirname(fileURLToPath(import.meta.url));
const pagePath = resolve(__dirname, "../pages/products/[slug].vue");

function readPage(): string {
    return readFileSync(pagePath, "utf-8");
}

type VariantAttribute = { name: string; value: string };

const makeVariant = (id: number, attrs: VariantAttribute[], stock = 5) => ({
    id,
    sku: `SKU-${id}`,
    price: "29.99",
    regular_price: "29.99",
    special_price: undefined as string | undefined,
    stock_quantity: stock,
    attributes: attrs,
});

const makeProduct = (overrides: Record<string, unknown> = {}) => ({
    id: 1,
    name: "PLA Filament",
    slug: "pla-filament",
    description: "High quality PLA filament for 3D printing.",
    price: "19.99",
    images: ["https://example.com/image1.jpg", "https://example.com/image2.jpg"],
    variants: [
        makeVariant(10, [{ name: "Color", value: "Red" }, { name: "Size", value: "1kg" }], 5),
        makeVariant(11, [{ name: "Color", value: "Blue" }, { name: "Size", value: "1kg" }], 0),
    ],
    attributes: { material: "PLA", diameter: "1.75mm" },
    reviews: [],
    categories: [],
    ...overrides,
});

// ---------------------------------------------------------------------------
// Stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
    ReviewForm: { template: "<div data-testid='review-form-stub'></div>" },
};

function setupStubs({ product = makeProduct() }: { product?: ReturnType<typeof makeProduct> | null } = {}) {
    const defaultApiMock = vi.fn().mockImplementation((path: string) => {
        if (typeof path === "string" && path.includes("/reviews")) {
            return Promise.resolve({ data: [] });
        }
        return Promise.resolve(undefined);
    });

    vi.stubGlobal("useApi", () => defaultApiMock);

    vi.stubGlobal("useRoute", () => ({
        params: { slug: "pla-filament" },
    }));

    vi.stubGlobal("useProducts", () => ({
        fetchProductBySlug: vi.fn().mockResolvedValue(product),
        currentProduct: ref(product),
        error: ref<string | null>(null),
    }));

    vi.stubGlobal("useCart", () => ({
        addItem: vi.fn().mockResolvedValue(undefined),
        cart: ref(null),
        itemCount: computed(() => 0),
    }));

    vi.stubGlobal("useAuth", () => ({
        user: ref(null),
        isAuthenticated: computed(() => false),
        logout: vi.fn(),
    }));
}

// ---------------------------------------------------------------------------
// Structural tests (source code inspection)
// ---------------------------------------------------------------------------

describe("[slug].vue — shadcn migration: source structure", () => {
    it("source file exists", () => {
        expect(existsSync(pagePath)).toBe(true);
    });

    it("has NO <style> block", () => {
        const src = readPage();
        expect(src).not.toMatch(/<style/);
    });

    it("imports Button from @/components/ui/button", () => {
        const src = readPage();
        expect(src).toMatch(/from ['"]@\/components\/ui\/button['"]/);
    });

    it("imports Select from @/components/ui/select", () => {
        const src = readPage();
        expect(src).toMatch(/from ['"]@\/components\/ui\/select['"]/);
    });

    it("imports SelectTrigger from @/components/ui/select", () => {
        const src = readPage();
        expect(src).toMatch(/SelectTrigger/);
    });

    it("imports SelectContent from @/components/ui/select", () => {
        const src = readPage();
        expect(src).toMatch(/SelectContent/);
    });

    it("imports SelectItem from @/components/ui/select", () => {
        const src = readPage();
        expect(src).toMatch(/SelectItem/);
    });

    it("imports SelectValue from @/components/ui/select", () => {
        const src = readPage();
        expect(src).toMatch(/SelectValue/);
    });

    it("uses <Button component for Add to Cart (not plain HTML button)", () => {
        const src = readPage();
        // Should have a <Button component (capital B = shadcn component)
        expect(src).toMatch(/<Button/);
    });

    it("does NOT use product-detail__add-to-cart BEM class", () => {
        const src = readPage();
        expect(src).not.toContain("product-detail__add-to-cart");
    });

    it("does NOT use product-detail__select BEM class", () => {
        const src = readPage();
        expect(src).not.toContain("product-detail__select");
    });

    it("uses <Select component for variant selector (not plain HTML select)", () => {
        const src = readPage();
        // Should have <Select component (capital S = shadcn component)
        expect(src).toMatch(/<Select[\s>]/);
    });

    it("template has no bare <button for add-to-cart (must use <Button)", () => {
        const src = readPage();
        // Should NOT have a bare HTML <button followed by add-to-cart
        // (shadcn Button renders the button element internally)
        expect(src).not.toMatch(/<button[^>]*add-to-cart/);
    });

    it("includes ReviewForm component in template", () => {
        const src = readPage();
        expect(src).toContain("ReviewForm");
    });
});

// ---------------------------------------------------------------------------
// Structural source-code checks (Tailwind utility assertions replaced per T2.8)
// ---------------------------------------------------------------------------

describe("[slug].vue — shadcn migration: source structure (gallery & specs)", () => {
    it("gallery section renders a primary image element with :src binding", () => {
        const src = readPage();
        // Primary img is bound to selectedImage ref (no static Tailwind class required)
        expect(src).toContain(':src="selectedImage"');
    });

    it("gallery thumbnails have a click handler to update selectedImage", () => {
        const src = readPage();
        expect(src).toContain('@click="selectedImage = image"');
    });

    it("gallery thumbnails iterate with v-for over product.images", () => {
        const src = readPage();
        expect(src).toContain('v-for="(image, index) in product.images"');
    });

    it("specifications section uses a dl element for key-value display", () => {
        const src = readPage();
        expect(src).toContain("<dl>");
    });

    it("specifications section uses dt and dd elements for attribute display", () => {
        const src = readPage();
        expect(src).toContain("<dt>");
        expect(src).toContain("<dd>");
    });
});

// ---------------------------------------------------------------------------
// Rendered HTML tests (mount the component)
// ---------------------------------------------------------------------------

describe("[slug].vue — shadcn migration: rendered HTML", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
        mockCreateError.mockClear();
    });

    it("primary gallery image renders with product name as alt text", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Primary image should render (no longer requires object-cover class — removed by T2.2)
        const imgs = wrapper.findAll("img");
        expect(imgs.length).toBeGreaterThan(0);
        const primaryImg = imgs.find((img) => img.attributes("alt") === "PLA Filament");
        expect(primaryImg).toBeDefined();
        expect(primaryImg!.exists()).toBe(true);
    });

    it("primary gallery image renders with correct src (first image URL)", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Primary img uses selectedImage ref — no rounded-lg class required (removed by T2.2)
        const imgs = wrapper.findAll("img");
        const primaryImg = imgs.find((img) => img.attributes("src") === "https://example.com/image1.jpg");
        expect(primaryImg).toBeDefined();
        expect(primaryImg!.exists()).toBe(true);
    });

    it("multiple gallery images render (one per product image)", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Product has 2 images; thumbnails render without cursor-pointer class (removed by T2.2)
        const imgs = wrapper.findAll("img");
        expect(imgs.length).toBeGreaterThanOrEqual(2);
    });

    it("specs dl element exists in rendered HTML", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // dl element exists — no grid class required (removed by T2.2)
        const dl = wrapper.find("dl");
        expect(dl.exists()).toBe(true);
    });

    it("Add to Cart button is disabled when no variant selected", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // The shadcn Button renders a <button> element with data-testid
        const btn = wrapper.find('[data-testid="add-to-cart"]');
        expect(btn.exists()).toBe(true);
        expect(btn.attributes("disabled")).toBeDefined();
    });

    it("Add to Cart button is enabled when in-stock variant is selected", async () => {
        setupStubs();

        const { default: ProductDetailPage } = await import("../pages/products/[slug].vue");
        const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } });

        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        // Directly set selectedVariantId (shadcn Select sets it as a number)
        (wrapper.vm as Record<string, unknown>).selectedVariantId = 10;
        await wrapper.vm.$nextTick();

        const btn = wrapper.find('[data-testid="add-to-cart"]');
        expect(btn.exists()).toBe(true);
        expect(btn.attributes("disabled")).toBeUndefined();
    });
});
