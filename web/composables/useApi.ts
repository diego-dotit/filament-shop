// composables/useApi.ts
// Provides a thin wrapper around $api (injected by plugins/api.ts).
// Falls back to a plain $fetch instance so the composable works in
// non-plugin contexts such as unit tests or server utilities.
//
// Usage:
//   const api = useApi()
//   const products = await api('/products')
//   const result  = await api('/products', { method: 'POST', body: { name: 'PLA' } })

type FetchOptions = Parameters<typeof $fetch>[1];

export function useApi() {
    // Try to grab the plugin-provided instance first.
    // If called outside a Nuxt component tree (e.g. unit tests), fall back gracefully.
    let pluginFetch: typeof $fetch | undefined;

    try {
        const nuxtApp = useNuxtApp();
        if (nuxtApp.$api) {
            pluginFetch = nuxtApp.$api as typeof $fetch;
        }
    } catch {
        // useNuxtApp() throws when called outside of a Nuxt context.
    }

    /**
     * Make an authenticated API request.
     *
     * @param path    - Path relative to the configured base URL (e.g. `/products`)
     * @param options - Standard $fetch / ofetch request options
     */
    async function request<T = unknown>(path: string, options: FetchOptions = {}): Promise<T> {
        // Build auth + preference headers lazily (read from localStorage at call time)
        const existingHeaders = new Headers(options.headers as HeadersInit | undefined);

        if (typeof window !== "undefined") {
            const token = localStorage.getItem("auth_token");
            if (token) {
                existingHeaders.set("Authorization", `Bearer ${token}`);
            }

            existingHeaders.set("Accept-Language", localStorage.getItem("app_language") || "en");
            existingHeaders.set("Accept-Currency", localStorage.getItem("app_currency") || "USD");
        }

        const mergedOptions: FetchOptions = {
            ...options,
            headers: Object.fromEntries(existingHeaders.entries()),
        };

        if (pluginFetch) {
            return pluginFetch<T>(path, mergedOptions);
        }

        // Fallback: use global $fetch directly with configured base URL
        const config = useRuntimeConfig();
        return $fetch<T>(path, { baseURL: config.public.apiBaseUrl, ...mergedOptions });
    }

    return request;
}
