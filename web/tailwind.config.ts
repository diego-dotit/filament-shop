import type { Config } from "tailwindcss"

export default {
  content: [
    "./pages/**/*.{js,ts,jsx,tsx,vue}",
    "./components/**/*.{js,ts,jsx,tsx,vue}",
    "./layouts/**/*.{js,ts,jsx,tsx,vue}",
    "./composables/**/*.{js,ts}",
    "./app.vue",
    "./error.vue",
  ],
} satisfies Config
