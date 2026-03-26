// plugins/api.ts
// Nuxt 3 plugin: provides $api — a $fetch wrapper that injects the auth token.
// Usage in components: const { $api } = useNuxtApp()
// Prefer the useApi() composable in /composables/useApi.ts instead.

export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig()
  const baseURL: string = (config.public.apiBaseUrl as string) ?? 'http://localhost:8000'

  /**
   * Auth-aware fetch function.
   * Reads the token lazily from localStorage at call-time so that
   * tokens stored after page load are always picked up.
   */
  const apiFetch = $fetch.create({
    baseURL,
    onRequest({ options }) {
      if (typeof window !== 'undefined') {
        const token = localStorage.getItem('auth_token')
        const language = localStorage.getItem('app_language') || 'en'
        const currency = localStorage.getItem('app_currency') || 'USD'

        const extraHeaders: Record<string, string> = {
          'Accept-Language': language,
          'Accept-Currency': currency,
        }

        if (token) {
          extraHeaders['Authorization'] = `Bearer ${token}`
        }

        const existingHeaders = new Headers(options.headers as HeadersInit | undefined)
        Object.entries(extraHeaders).forEach(([k, v]) => existingHeaders.set(k, v))
        options.headers = Object.fromEntries(existingHeaders.entries())
      }
    },
  })

  return {
    provide: {
      api: apiFetch,
    },
  }
})
