import { describe, it, expect, vi } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// Header uses useAuth() and useCart(), both of which call useState() and
// useApi() — all Nuxt auto-imported globals not available in unit tests.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

// useState: simulate Nuxt's shared state via a plain ref per key
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));

// useApi: return a no-op fetch stub
vi.stubGlobal("useApi", () => vi.fn());

// useAuth: default stub — not authenticated
vi.stubGlobal("useAuth", () => ({
    user: ref(null),
    isAuthenticated: computed(() => false),
    logout: vi.fn(),
}));

// useCart: default stub — empty cart
vi.stubGlobal("useCart", () => ({
    cart: ref(null),
    itemCount: computed(() => 0),
}));

// useLocalization: default stub — en / USD
vi.stubGlobal("useLocalization", () => ({
    language: ref("en"),
    currency: ref("USD"),
    availableLanguages: ["en", "fr", "es"],
    availableCurrencies: ["USD", "EUR", "GBP"],
    setLanguage: vi.fn(),
    setCurrency: vi.fn(),
}));

// ---------------------------------------------------------------------------
// Shared mount options: stub NuxtLink so router is not required
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: { template: "<a><slot /></a>" },
};

// ---------------------------------------------------------------------------
// Header tests
// ---------------------------------------------------------------------------

describe("Header component", () => {
    it("renders a <header> element", async () => {
        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });
        expect(wrapper.find("header").exists()).toBe(true);
    });

    it("renders Home and Cart navigation links", async () => {
        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("Home");
        expect(wrapper.text()).toContain("Cart");
    });

    it("shows login and register links when not authenticated", async () => {
        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("Login");
        expect(wrapper.text()).toContain("Register");
    });

    it("shows logout button when authenticated", async () => {
        vi.stubGlobal("useAuth", () => ({
            user: ref({ id: 1, name: "Alice", email: "alice@example.com" }),
            isAuthenticated: computed(() => true),
            logout: vi.fn(),
        }));

        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("Logout");
        expect(wrapper.find("button").exists()).toBe(true);
    });

    it("displays live cart item count", async () => {
        vi.stubGlobal("useAuth", () => ({
            user: ref(null),
            isAuthenticated: computed(() => false),
            logout: vi.fn(),
        }));
        vi.stubGlobal("useCart", () => ({
            cart: ref(null),
            itemCount: computed(() => 5),
        }));

        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("5");
    });

    it("renders a language selector with available languages", async () => {
        vi.stubGlobal("useAuth", () => ({
            user: ref(null),
            isAuthenticated: computed(() => false),
            logout: vi.fn(),
        }));
        vi.stubGlobal("useCart", () => ({
            cart: ref(null),
            itemCount: computed(() => 0),
        }));

        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });

        const languageSelect = wrapper.find('select[aria-label="Language"]');
        expect(languageSelect.exists()).toBe(true);

        const options = languageSelect.findAll("option");
        const values = options.map((o) => o.element.value);
        expect(values).toContain("en");
        expect(values).toContain("fr");
        expect(values).toContain("es");
    });

    it("renders a currency selector with available currencies", async () => {
        vi.stubGlobal("useAuth", () => ({
            user: ref(null),
            isAuthenticated: computed(() => false),
            logout: vi.fn(),
        }));
        vi.stubGlobal("useCart", () => ({
            cart: ref(null),
            itemCount: computed(() => 0),
        }));

        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });

        const currencySelect = wrapper.find('select[aria-label="Currency"]');
        expect(currencySelect.exists()).toBe(true);

        const options = currencySelect.findAll("option");
        const values = options.map((o) => o.element.value);
        expect(values).toContain("USD");
        expect(values).toContain("EUR");
        expect(values).toContain("GBP");
    });

    it("calls setLanguage when language selector changes", async () => {
        const setLanguage = vi.fn();
        const setCurrency = vi.fn();
        vi.stubGlobal("useAuth", () => ({
            user: ref(null),
            isAuthenticated: computed(() => false),
            logout: vi.fn(),
        }));
        vi.stubGlobal("useCart", () => ({
            cart: ref(null),
            itemCount: computed(() => 0),
        }));
        vi.stubGlobal("useLocalization", () => ({
            language: ref("en"),
            currency: ref("USD"),
            availableLanguages: ["en", "fr", "es"],
            availableCurrencies: ["USD", "EUR", "GBP"],
            setLanguage,
            setCurrency,
        }));

        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });

        const languageSelect = wrapper.find('select[aria-label="Language"]');
        await languageSelect.setValue("fr");
        expect(setLanguage).toHaveBeenCalledWith("fr");
    });

    it("calls setCurrency when currency selector changes", async () => {
        const setLanguage = vi.fn();
        const setCurrency = vi.fn();
        vi.stubGlobal("useAuth", () => ({
            user: ref(null),
            isAuthenticated: computed(() => false),
            logout: vi.fn(),
        }));
        vi.stubGlobal("useCart", () => ({
            cart: ref(null),
            itemCount: computed(() => 0),
        }));
        vi.stubGlobal("useLocalization", () => ({
            language: ref("en"),
            currency: ref("USD"),
            availableLanguages: ["en", "fr", "es"],
            availableCurrencies: ["USD", "EUR", "GBP"],
            setLanguage,
            setCurrency,
        }));

        const { default: Header } = await import("../components/Header.vue");
        const wrapper = mount(Header, { global: { stubs: globalStubs } });

        const currencySelect = wrapper.find('select[aria-label="Currency"]');
        await currencySelect.setValue("EUR");
        expect(setCurrency).toHaveBeenCalledWith("EUR");
    });
});

// ---------------------------------------------------------------------------
// Footer tests
// ---------------------------------------------------------------------------

describe("Footer component", () => {
    it("renders a <footer> element", async () => {
        const { default: Footer } = await import("../components/Footer.vue");
        const wrapper = mount(Footer, { global: { stubs: globalStubs } });
        expect(wrapper.find("footer").exists()).toBe(true);
    });

    it("renders static footer links (About, Contact, Terms)", async () => {
        const { default: Footer } = await import("../components/Footer.vue");
        const wrapper = mount(Footer, { global: { stubs: globalStubs } });
        expect(wrapper.text()).toContain("About");
        expect(wrapper.text()).toContain("Contact");
        expect(wrapper.text()).toContain("Terms");
    });
});

// ---------------------------------------------------------------------------
// Default layout tests
// ---------------------------------------------------------------------------

describe("Default layout", () => {
    it("renders Header, slot, and Footer", async () => {
        const { default: DefaultLayout } = await import("../layouts/default.vue");
        const wrapper = mount(DefaultLayout, {
            slots: {
                default: "<main>Page Content</main>",
            },
            global: {
                stubs: {
                    Header: { template: "<header>Header</header>" },
                    Footer: { template: "<footer>Footer</footer>" },
                },
            },
        });
        expect(wrapper.find("header").exists()).toBe(true);
        expect(wrapper.find("footer").exists()).toBe(true);
        expect(wrapper.text()).toContain("Page Content");
    });

    it("renders Header before Footer in DOM order", async () => {
        const { default: DefaultLayout } = await import("../layouts/default.vue");
        const wrapper = mount(DefaultLayout, {
            slots: {
                default: "<p>content</p>",
            },
            global: {
                stubs: {
                    Header: { template: "<header>Header</header>" },
                    Footer: { template: "<footer>Footer</footer>" },
                },
            },
        });
        const header = wrapper.find("header");
        const footer = wrapper.find("footer");
        expect(header.exists()).toBe(true);
        expect(footer.exists()).toBe(true);
        // Compare DOM positions: header node must appear before footer node
        const position = header.element.compareDocumentPosition(footer.element);
        // DOCUMENT_POSITION_FOLLOWING = 4 → footer comes after header ✓
        expect(position & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
    });
});
