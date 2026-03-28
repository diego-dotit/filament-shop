// composables/useAutoLanguage.ts
// Detects when an API response carries a `locale` field and, if that locale
// differs from the current UI language, switches the active language via
// useLocalization().setLanguage().  Only valid Language values trigger a switch.

import { useLocalization, availableLanguages, type Language } from "~/composables/useLocalization";

/**
 * Minimal shape an API response must have to carry a locale hint.
 * All other fields are allowed but not required.
 */
export interface LocaleResponse {
    locale?: string;
    [key: string]: unknown;
}

/**
 * Checks whether a string is a known Language value.
 */
function isValidLanguage(value: string): value is Language {
    return (availableLanguages as readonly string[]).includes(value);
}

/**
 * useAutoLanguage()
 *
 * Returns `applyLocale(response)` — call it right after receiving a slug-based
 * API response.  The guard logic is:
 *   1. response must be non-null/non-undefined
 *   2. response.locale must be present
 *   3. response.locale must be a valid Language value
 *   4. response.locale must differ from the current UI language
 *
 * When all guards pass, calls setLanguage() which updates reactive state AND
 * persists to localStorage.
 */
export function useAutoLanguage() {
    const { language, setLanguage } = useLocalization();

    function applyLocale(response: LocaleResponse | null | undefined): void {
        if (!response) return;

        const responseLocale = response.locale;
        if (!responseLocale) return;
        if (!isValidLanguage(responseLocale)) return;
        if (responseLocale === language.value) return;

        setLanguage(responseLocale);
    }

    return { applyLocale };
}
