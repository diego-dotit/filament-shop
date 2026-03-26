import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed, onMounted } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("onMounted", onMounted);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());

// Shared mock for fetchProducts
const mockFetchProducts = vi.fn();
const mockProducts = ref([
    {
        id: 1,
        name: "PLA Filament",
        slug: "pla-filament",
        price: "19.99",
        images: ["https://example.com/pla.jpg"],
        variants: [],
        attributes: {},
    },
    {
        id: 2,
        name: "PETG Filament",
        slug: "petg-filament",
        price: "24.99",
        images: [],
        variants: [],
        attributes: {},
    },
]);
const mockCurrentPage = ref(1);
const mockPageSize = ref(15);
const mockTotalPages = computed(() => 3);

vi.stubGlobal("useProducts", () => ({
    products: mockProducts,
    currentPage: mockCurrentPage,
    pageSize: mockPageSize,
    totalPages: mockTotalPages,
    fetchProducts: mockFetchProducts,
}));

// Stub useCategories so index.vue can call it without errors
vi.stubGlobal("useCategories", () => ({
    categories: ref([]),
    fetchCategories: vi.fn().mockResolvedValue(undefined),
    error: ref(null),
}));

// Stub useAuth and useCart for Header (used in layout)
vi.stubGlobal("useAuth", () => ({
    user: ref(null),
    isAuthenticated: computed(() => false),
    logout: vi.fn(),
}));
vi.stubGlobal("useCart", () => ({
    cart: ref(null),
    itemCount: computed(() => 0),
}));

// ---------------------------------------------------------------------------
// Shared stubs
// ---------------------------------------------------------------------------
const globalStubs = {
    NuxtLink: {
        template: '<a :href="to"><slot /></a>',
        props: ["to"],
    },
};

// ---------------------------------------------------------------------------
// ProductCard tests
// ---------------------------------------------------------------------------

describe("ProductCard component", () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it("renders the product name", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "19.99",
                    images: ["https://example.com/pla.jpg"],
                    variants: [],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("PLA Filament");
    });

    it("renders the product price", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "19.99",
                    images: ["https://example.com/pla.jpg"],
                    variants: [],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        expect(wrapper.text()).toContain("19.99");
    });

    it("renders a product image when images are available", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "19.99",
                    images: ["https://example.com/pla.jpg"],
                    variants: [],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        const img = wrapper.find("img");
        expect(img.exists()).toBe(true);
        expect(img.attributes("src")).toBe("https://example.com/pla.jpg");
    });

    it("renders a placeholder/fallback when no images are available", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 2,
                    name: "PETG Filament",
                    slug: "petg-filament",
                    price: "24.99",
                    images: [],
                    variants: [],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        // Should render img with a placeholder src or a fallback element
        const img = wrapper.find("img");
        expect(img.exists()).toBe(true);
        // Placeholder src should not be empty
        expect(img.attributes("src")).toBeTruthy();
    });

    it("renders a link to /products/{slug}", async () => {
        const { default: ProductCard } = await import("../components/ProductCard.vue");
        const wrapper = mount(ProductCard, {
            props: {
                product: {
                    id: 1,
                    name: "PLA Filament",
                    slug: "pla-filament",
                    price: "19.99",
                    images: ["https://example.com/pla.jpg"],
                    variants: [],
                    attributes: {},
                },
            },
            global: { stubs: globalStubs },
        });
        const link = wrapper.find("a");
        expect(link.exists()).toBe(true);
        expect(link.attributes("href")).toContain("pla-filament");
    });
});

// ---------------------------------------------------------------------------
// Homepage (index.vue) tests
// ---------------------------------------------------------------------------

describe("Homepage (index.vue)", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockCurrentPage.value = 1;
        mockProducts.value = [
            {
                id: 1,
                name: "PLA Filament",
                slug: "pla-filament",
                price: "19.99",
                images: ["https://example.com/pla.jpg"],
                variants: [],
                attributes: {},
            },
            {
                id: 2,
                name: "PETG Filament",
                slug: "petg-filament",
                price: "24.99",
                images: [],
                variants: [],
                attributes: {},
            },
        ];
    });

    it("calls fetchProducts on mount with page 1 and pageSize", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        mount(IndexPage, { global: { stubs: { ...globalStubs, ProductCard: true } } });
        await new Promise((r) => setTimeout(r, 0));
        expect(mockFetchProducts).toHaveBeenCalledWith(1, 15);
    });

    it("renders a ProductCard for each product", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, {
            global: {
                stubs: {
                    ...globalStubs,
                    ProductCard: {
                        template: '<div class="product-card">{{ product.name }}</div>',
                        props: ["product"],
                    },
                },
            },
        });
        await new Promise((r) => setTimeout(r, 0));
        const cards = wrapper.findAll(".product-card");
        expect(cards).toHaveLength(2);
        expect(cards[0].text()).toContain("PLA Filament");
        expect(cards[1].text()).toContain("PETG Filament");
    });

    it("renders pagination Previous and Next buttons", async () => {
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, {
            global: {
                stubs: { ...globalStubs, ProductCard: true },
            },
        });
        await new Promise((r) => setTimeout(r, 0));
        expect(wrapper.text()).toMatch(/prev(ious)?/i);
        expect(wrapper.text()).toMatch(/next/i);
    });

    it("Previous button is disabled on the first page", async () => {
        mockCurrentPage.value = 1;
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, {
            global: {
                stubs: { ...globalStubs, ProductCard: true },
            },
        });
        await new Promise((r) => setTimeout(r, 0));
        const buttons = wrapper.findAll("button");
        const prevBtn = buttons.find((b) => /prev(ious)?/i.test(b.text()));
        expect(prevBtn?.attributes("disabled")).toBeDefined();
    });

    it("Next button is disabled on the last page", async () => {
        mockCurrentPage.value = 3; // totalPages = 3
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, {
            global: {
                stubs: { ...globalStubs, ProductCard: true },
            },
        });
        await new Promise((r) => setTimeout(r, 0));
        const buttons = wrapper.findAll("button");
        const nextBtn = buttons.find((b) => /next/i.test(b.text()));
        expect(nextBtn?.attributes("disabled")).toBeDefined();
    });

    it("displays the current page number", async () => {
        mockCurrentPage.value = 2;
        const { default: IndexPage } = await import("../pages/index.vue");
        const wrapper = mount(IndexPage, {
            global: {
                stubs: { ...globalStubs, ProductCard: true },
            },
        });
        await new Promise((r) => setTimeout(r, 0));
        expect(wrapper.text()).toContain("2");
    });
});
