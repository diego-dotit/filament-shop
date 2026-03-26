import pluginVue from "eslint-plugin-vue";

export default [
    ...pluginVue.configs["flat/recommended"],
    {
        rules: {
            indent: ["error", 4],
            "vue/html-indent": ["error", 4],
        },
    },
];
