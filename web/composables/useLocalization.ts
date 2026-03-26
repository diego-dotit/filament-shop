// composables/useLocalization.ts
// Provides global language/currency state, persisted to localStorage.
// The api.ts plugin and useApi.ts composable read from the same localStorage
// keys ('app_language', 'app_currency') lazily on each request, so any change
// here is automatically picked up by the next API call.

export const availableLanguages = ["en", "fr", "es"] as const;
export const availableCurrencies = ["USD", "EUR", "GBP"] as const;

export type Language = (typeof availableLanguages)[number];
export type Currency = (typeof availableCurrencies)[number];

const LANGUAGE_KEY = "app_language";
const CURRENCY_KEY = "app_currency";

function readStorage(key: string, fallback: string): string {
    if (typeof window !== "undefined") {
        return localStorage.getItem(key) || fallback;
    }
    return fallback;
}

export function useLocalization() {
    const language = useState<Language>(
        "localization.language",
        () => readStorage(LANGUAGE_KEY, "en") as Language
    );

    const currency = useState<Currency>(
        "localization.currency",
        () => readStorage(CURRENCY_KEY, "USD") as Currency
    );

    function setLanguage(lang: Language) {
        language.value = lang;
        if (typeof window !== "undefined") {
            localStorage.setItem(LANGUAGE_KEY, lang);
        }
    }

    function setCurrency(curr: Currency) {
        currency.value = curr;
        if (typeof window !== "undefined") {
            localStorage.setItem(CURRENCY_KEY, curr);
        }
    }

    return {
        language,
        currency,
        availableLanguages,
        availableCurrencies,
        setLanguage,
        setCurrency,
    };
}
