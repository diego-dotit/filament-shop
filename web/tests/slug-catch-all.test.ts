/**
 * Tests for web/pages/[...slug].vue catch-all route handler.
 *
 * Acceptance criteria:
 *  - Route captures multi-segment URLs as route.params.slug array
 *  - Last segment is used as product slug for API call
 *  - Intermediate segments are used for category hierarchy validation
 *  - Shows product details when all segments resolve successfully
 *  - Throws 404 when product is not found (fetchProductBySlug returns null)
 *  - Throws 404 when category resolution fails (fetchCategoryBySlug throws)
 *  - Breadcrumb shows full category → subcategory → product hierarchy
 *  - Calls applyLocale with the product API response
 *  - Single-segment URL (just product slug) resolves without category lookups
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE importing component
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

const mockCreateError = vi.fn((opts: { statusCode: number; statusMessage?: string }) => {
    const err = new Error(opts.statusMessage ?? String(opts.statusCode));
    (err as unknown as Record<string, unknown>).statusCode = opts.statusCode;
    return err;
});
vi.stubGlobal("createError", mockCreateError);

vi.stubGlobal("useApi", () => vi.fn());
vi.stubGlobal("useCart", () => ({ addItem: vi.fn() }));
vi.stubGlobal("useAuth", () => ({ user: ref(null) }));

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const makeProduct = (overrides: Record<string, unknown> = {}) => ({
    id: 42,
    name: "Blue PLA Filament",
    slug: "blue-pla",
    description: "A blue PLA filament.",
    price: "19.99",
    images: ["https://example.com/blue.jpg"],
    variants: [],
    attributes: { material: "PLA", color: "Blue" },
    locale: "en",
    ...overrides,
});

const makeCategory = (id: number, name: string, slug: string) => ({
    id,
    name,
    slug,
    image: null,
    children: [],
    locale: "en",
});

// ---------------------------------------------------------------------------
// Default stubs helper
// ---------------------------------------------------------------------------

function setupStubs({
    slugParams = ["pla-filament", "color-variants", "blue-pla"],
    product = makeProduct(),
    fetchProductBySlug = vi.fn().mockResolvedValue(product),
    fetchCategoryBySlug = vi.fn().mockImplementation((slug: string) => {
        if (slug === "pla-filament") return Promise.resolve(makeCategory(1, "PLA Filament", slug));
        if (slug === "color-variants") return Promise.resolve(makeCategory(2, "Color Variants", slug));
        return Promise.reject(Object.assign(new Error("Not Found"), { statusCode: 404 }));
    }),
    applyLocale = vi.fn(),
} = {}) {
    vi.stubGlobal("useRoute", () => ({
        params: { slug: slugParams },
    }));

    vi.stubGlobal("useProducts", () => ({
        fetchProductBySlug,
        error: ref<string | null>(null),
    }));

    vi.stubGlobal("useCategories", () => ({
        fetchCategoryBySlug,
        error: ref<string | null>(null),
    }));

    vi.stubGlobal("useAutoLanguage", () => ({
        applyLocale,
    }));

    return { fetchProductBySlug, fetchCategoryBySlug, applyLocale };
}

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
    ReviewForm: { template: "<div />" },
    ProductCard: { template: "<div />" },
    Breadcrumb: { props: ["items"], template: '<nav>{{ items ? items.map((i) => i.name).join(" ") : "" }}</nav>' },
    CategoryChip: { template: "<span><slot /></span>" },
};

// ---------------------------------------------------------------------------
// Import component AFTER stubs are in place
// ---------------------------------------------------------------------------

// eslint-disable-next-line @typescript-eslint/consistent-type-imports
let SlugPage: typeof import("../pages/[...slug].vue").default;

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("[...slug].vue catch-all route handler", () => {
    beforeEach(async () => {
        vi.clearAllMocks();
        // Lazy import so stubs are in place before module evaluation
        const mod = await import("../pages/[...slug].vue?t=" + Date.now());
        SlugPage = mod.default;
    });

    it("shows loading state initially", async () => {
        const { fetchProductBySlug } = setupStubs({
            fetchProductBySlug: vi.fn().mockReturnValue(new Promise(() => {})), // never resolves
        });

        const wrapper = mount(SlugPage, { global: { stubs: globalStubs } });

        expect(wrapper.text()).toContain("Loading");
        expect(fetchProductBySlug).not.toHaveBeenCalled(); // not called synchronously
    });

    it("displays product details after successful resolution of 3-segment URL", async () => {
        const product = makeProduct();
        setupStubs({ slugParams: ["pla-filament", "color-variants", "blue-pla"], product });

        const wrapper = mount(SlugPage, { global: { stubs: globalStubs } });

        // Wait for all async operations (onMounted)
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("Blue PLA Filament");
    });

    it("calls fetchProductBySlug with the last segment of the URL", async () => {
        const { fetchProductBySlug } = setupStubs({
            slugParams: ["pla-filament", "color-variants", "blue-pla"],
        });

        mount(SlugPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));

        expect(fetchProductBySlug).toHaveBeenCalledWith("blue-pla");
    });

    it("calls fetchCategoryBySlug for each intermediate segment", async () => {
        const { fetchCategoryBySlug } = setupStubs({
            slugParams: ["pla-filament", "color-variants", "blue-pla"],
        });

        mount(SlugPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));

        expect(fetchCategoryBySlug).toHaveBeenCalledWith("pla-filament");
        expect(fetchCategoryBySlug).toHaveBeenCalledWith("color-variants");
        expect(fetchCategoryBySlug).toHaveBeenCalledTimes(2);
    });

    it("throws 404 when fetchProductBySlug returns null", async () => {
        setupStubs({ fetchProductBySlug: vi.fn().mockResolvedValue(null) });

        mount(SlugPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));

        expect(mockCreateError).toHaveBeenCalledWith(
            expect.objectContaining({ statusCode: 404 })
        );
    });

    it("throws 404 when a category segment fails to resolve", async () => {
        setupStubs({
            slugParams: ["nonexistent-cat", "blue-pla"],
            fetchCategoryBySlug: vi.fn().mockRejectedValue(
                Object.assign(new Error("Not Found"), { statusCode: 404 })
            ),
        });

        mount(SlugPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));

        expect(mockCreateError).toHaveBeenCalledWith(
            expect.objectContaining({ statusCode: 404 })
        );
    });

    it("calls applyLocale with the product API response", async () => {
        const product = makeProduct({ locale: "fr" });
        const { applyLocale } = setupStubs({
            product,
            fetchProductBySlug: vi.fn().mockResolvedValue(product),
        });

        mount(SlugPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));

        expect(applyLocale).toHaveBeenCalledWith(product);
    });

    it("shows breadcrumb with full category → product hierarchy for 3-segment URL", async () => {
        setupStubs({
            slugParams: ["pla-filament", "color-variants", "blue-pla"],
        });

        const wrapper = mount(SlugPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        const breadcrumbText = wrapper.text();
        expect(breadcrumbText).toContain("PLA Filament");
        expect(breadcrumbText).toContain("Color Variants");
        expect(breadcrumbText).toContain("Blue PLA Filament");
    });

    it("resolves single-segment URL without calling fetchCategoryBySlug", async () => {
        const product = makeProduct({ slug: "standalone-product" });
        const { fetchCategoryBySlug } = setupStubs({
            slugParams: ["standalone-product"],
            product,
            fetchProductBySlug: vi.fn().mockResolvedValue(product),
        });

        mount(SlugPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));

        expect(fetchCategoryBySlug).not.toHaveBeenCalled();
    });

    it("displays product name for single-segment URL", async () => {
        const product = makeProduct({ slug: "standalone-product", name: "Standalone Product" });
        setupStubs({
            slugParams: ["standalone-product"],
            product,
            fetchProductBySlug: vi.fn().mockResolvedValue(product),
        });

        const wrapper = mount(SlugPage, { global: { stubs: globalStubs } });
        await new Promise((r) => setTimeout(r, 0));
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain("Standalone Product");
    });
});
