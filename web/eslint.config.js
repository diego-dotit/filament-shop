import pluginVue from "eslint-plugin-vue";
import tsParser from "@typescript-eslint/parser";

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
];
