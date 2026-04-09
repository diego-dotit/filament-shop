// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
    compatibilityDate: '2025-07-15',
    devtools: { 
        enabled: true 
    },
    modules: [
      '@nuxtjs/tailwindcss',
      'shadcn-nuxt',
      '@nuxt/icon',
      '@nuxt/fonts'
    ],
    shadcn: {
        prefix: '',
        componentDir: '@/components/ui',
    },
    vite: {
        server: {
            allowedHosts: [
                'dev.dotit.ro'
            ]
        }
    },
    fonts: {
        provider: 'local',
        families: [
            {
                name: 'DMfont',
                as: 'custom-dmfont-family',
                provider: 'local',
                src: '~/public/fonts/DM_Sans/DMSans-variable.ttf',
                subsets: ['latin', 'greek'],
                display: 'swap',
                weight: ['400', '700'],
                style: ['normal'],
                fallbacks: ['Arial'],
            },
            {
                name: 'DMfont',
                as: 'custom-dmfont-family',
                provider: 'local',
                src: '~/public/fonts/DM_Sans/DMSans-Italic-variable.ttf',
                subsets: ['latin', 'greek'],
                display: 'swap',
                weight: ['400', '700'],
                style: ['normal', 'italic'],
                fallbacks: ['Arial'],
            },
        ]
    }
})