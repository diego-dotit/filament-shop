import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals required by useLocalization
// ---------------------------------------------------------------------------

// useState: simulate Nuxt's shared state via a plain ref per key
const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", <T>(key: string, init?: () => T) => {
    if (!stateStore[key]) {
        stateStore[key] = ref<T>(init ? init() : (undefined as T));
    }
    return stateStore[key];
});

// ---------------------------------------------------------------------------
// localStorage mock
// ---------------------------------------------------------------------------

const localStorageMock = (() => {
    let store: Record<string, string> = {};
    return {
        getItem: (key: string) => store[key] ?? null,
        setItem: (key: string, value: string) => {
            store[key] = value;
        },
        removeItem: (key: string) => {
            delete store[key];
        },
        clear: () => {
            store = {};
        },
    };
})();

Object.defineProperty(globalThis, "localStorage", { value: localStorageMock, writable: true });

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("useLocalization", () => {
    beforeEach(() => {
        // Reset localStorage and state store before each test
        localStorageMock.clear();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
    });

    it('returns default language "en" when nothing is stored', async () => {
        const { useLocalization } = await import("../composables/useLocalization");
        const { language } = useLocalization();
        expect(language.value).toBe("en");
    });

    it('returns default currency "USD" when nothing is stored', async () => {
        const { useLocalization } = await import("../composables/useLocalization");
        const { currency } = useLocalization();
        expect(currency.value).toBe("USD");
    });

    it("restores language from localStorage on init", async () => {
        localStorageMock.setItem("app_language", "fr");
        const { useLocalization } = await import("../composables/useLocalization");
        const { language } = useLocalization();
        expect(language.value).toBe("fr");
    });

    it("restores currency from localStorage on init", async () => {
        localStorageMock.setItem("app_currency", "EUR");
        const { useLocalization } = await import("../composables/useLocalization");
        const { currency } = useLocalization();
        expect(currency.value).toBe("EUR");
    });

    it("setLanguage updates state and persists to localStorage", async () => {
        const { useLocalization } = await import("../composables/useLocalization");
        const { language, setLanguage } = useLocalization();
        setLanguage("es");
        expect(language.value).toBe("es");
        expect(localStorageMock.getItem("app_language")).toBe("es");
    });

    it("setCurrency updates state and persists to localStorage", async () => {
        const { useLocalization } = await import("../composables/useLocalization");
        const { currency, setCurrency } = useLocalization();
        setCurrency("GBP");
        expect(currency.value).toBe("GBP");
        expect(localStorageMock.getItem("app_currency")).toBe("GBP");
    });

    it("exposes available languages array with en, fr, es", async () => {
        const { useLocalization } = await import("../composables/useLocalization");
        const { availableLanguages } = useLocalization();
        expect(availableLanguages).toContain("en");
        expect(availableLanguages).toContain("fr");
        expect(availableLanguages).toContain("es");
    });

    it("exposes available currencies array with USD, EUR, GBP", async () => {
        const { useLocalization } = await import("../composables/useLocalization");
        const { availableCurrencies } = useLocalization();
        expect(availableCurrencies).toContain("USD");
        expect(availableCurrencies).toContain("EUR");
        expect(availableCurrencies).toContain("GBP");
    });
});
