import pluginVue from "eslint-plugin-vue";
import tsParser from "@typescript-eslint/parser";
import prettierConfig from "eslint-config-prettier";

export default [
    ...pluginVue.configs["flat/recommended"],
    {
        files: ["**/*.{js,mjs,ts,vue}"],
        rules: {
            indent: ["error", 4],
            "vue/html-indent": ["error", 4],
        },
    },
    {
        files: ["**/*.ts"],
        languageOptions: {
            parser: tsParser,
        },
    },
    {
        files: ["**/*.vue"],
        languageOptions: {
            parserOptions: {
                parser: tsParser,
            },
        },
    },
    // Disable formatting rules that conflict with Prettier
    prettierConfig,
    // Nuxt pages, layouts, and special files use single-word names by convention
    {
        files: ["pages/**/*.vue", "layouts/**/*.vue", "error.vue", "components/*.vue"],
        rules: {
            "vue/multi-word-component-names": "off",
        },
    },
];
