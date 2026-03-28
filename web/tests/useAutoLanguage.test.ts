/**
 * Tests for useAutoLanguage() composable.
 *
 * Acceptance criteria:
 *  - applyLocale(response) switches UI language when response.locale differs from current language
 *  - applyLocale(response) does NOT switch language when response.locale matches current language
 *  - applyLocale(response) does NOT switch language when response has no locale field
 *  - applyLocale(response) does NOT switch language when response is null/undefined
 *  - Only valid Language values ('en' | 'fr' | 'es') trigger a switch
 *  - After switch, useLocalization().language reflects new language
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals required by useLocalization (and therefore useAutoLanguage)
// ---------------------------------------------------------------------------

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

describe("useAutoLanguage()", () => {
    beforeEach(() => {
        localStorageMock.clear();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
    });

    it("switches language when response.locale differs from current UI language", async () => {
        const { useAutoLanguage } = await import("../composables/useAutoLanguage");
        const { useLocalization } = await import("../composables/useLocalization");

        const { language } = useLocalization();
        expect(language.value).toBe("en"); // default

        const { applyLocale } = useAutoLanguage();
        applyLocale({ locale: "fr" });

        expect(language.value).toBe("fr");
    });

    it("does NOT switch language when response.locale matches current UI language", async () => {
        const { useAutoLanguage } = await import("../composables/useAutoLanguage");
        const { useLocalization } = await import("../composables/useLocalization");
        const { setLanguage, language } = useLocalization();

        setLanguage("es");
        expect(language.value).toBe("es");

        const { applyLocale } = useAutoLanguage();
        const setLanguageSpy = vi.spyOn(useLocalization(), "setLanguage");

        applyLocale({ locale: "es" });

        // language unchanged, setLanguage not called
        expect(language.value).toBe("es");
        expect(setLanguageSpy).not.toHaveBeenCalled();
    });

    it("does NOT switch language when response has no locale field", async () => {
        const { useAutoLanguage } = await import("../composables/useAutoLanguage");
        const { useLocalization } = await import("../composables/useLocalization");
        const { language } = useLocalization();

        expect(language.value).toBe("en");

        const { applyLocale } = useAutoLanguage();
        applyLocale({});

        expect(language.value).toBe("en");
    });

    it("does NOT switch language when response is null", async () => {
        const { useAutoLanguage } = await import("../composables/useAutoLanguage");
        const { useLocalization } = await import("../composables/useLocalization");
        const { language } = useLocalization();

        expect(language.value).toBe("en");

        const { applyLocale } = useAutoLanguage();
        applyLocale(null);

        expect(language.value).toBe("en");
    });

    it("does NOT switch language when response is undefined", async () => {
        const { useAutoLanguage } = await import("../composables/useAutoLanguage");
        const { useLocalization } = await import("../composables/useLocalization");
        const { language } = useLocalization();

        expect(language.value).toBe("en");

        const { applyLocale } = useAutoLanguage();
        applyLocale(undefined);

        expect(language.value).toBe("en");
    });

    it("persists the new language to localStorage via setLanguage", async () => {
        const { useAutoLanguage } = await import("../composables/useAutoLanguage");
        const { applyLocale } = useAutoLanguage();

        applyLocale({ locale: "es" });

        expect(localStorageMock.getItem("app_language")).toBe("es");
    });

    it("switches language from 'fr' to 'en' when response.locale is 'en'", async () => {
        const { useAutoLanguage } = await import("../composables/useAutoLanguage");
        const { useLocalization } = await import("../composables/useLocalization");
        const { setLanguage, language } = useLocalization();

        setLanguage("fr");
        expect(language.value).toBe("fr");

        const { applyLocale } = useAutoLanguage();
        applyLocale({ locale: "en" });

        expect(language.value).toBe("en");
    });

    it("ignores response.locale values that are not valid Language types", async () => {
        const { useAutoLanguage } = await import("../composables/useAutoLanguage");
        const { useLocalization } = await import("../composables/useLocalization");
        const { language } = useLocalization();

        expect(language.value).toBe("en");

        const { applyLocale } = useAutoLanguage();
        applyLocale({ locale: "de" }); // 'de' is not a valid Language

        expect(language.value).toBe("en");
    });
});
