// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    devtools: { enabled: true },
    css: ["~/assets/css/globals.css"],

    runtimeConfig: {
        // Server-only secrets go here (not exposed to the client)
        // public values are exposed to the client bundle
        public: {
            // Read from NUXT_PUBLIC_API_BASE_URL env var (see .env.example)
            apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || "http://localhost:8000/api",
            // Read from NUXT_PUBLIC_APP_URL env var (see .env.example)
            appUrl: process.env.NUXT_PUBLIC_APP_URL || "http://localhost:3000",
        },
    },

    router: {
        // Restore saved scroll position on back/forward navigation; scroll to top otherwise.
        scrollBehavior(
            _to: unknown,
            _from: unknown,
            savedPosition: { top: number; left: number } | null
        ): { top: number; left: number } {
            if (savedPosition) {
                return savedPosition;
            }
            return { top: 0, left: 0 };
        },
    },

    typescript: {
        strict: true,
    },

    postcss: {
        plugins: {
            "@tailwindcss/postcss": {},
        },
    },

    components: {
        dirs: [
            {
                path: "~/components",
                extensions: ["vue"],
            },
        ],
    },
});
