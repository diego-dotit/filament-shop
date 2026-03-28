/**
 * Tests for CategoryChip component.
 *
 * Acceptance criteria:
 *  - Renders category name
 *  - Links to /categories/{slug} using language-aware slug from slugs array
 *  - Falls back to default locale ('en') slug when current language not found
 *  - Falls back to category.slug (legacy) when no slugs array present
 *  - Reactively updates href when language changes
 *  - Works as a chip/pill suitable for both homepage and categories overview
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("ref", ref);
vi.stubGlobal("computed", computed);

const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

// Language mock — default to 'en', tests can override via stateStore
vi.stubGlobal("useLocalization", () => ({
    language: stateStore["localization.language"] ?? ref("en"),
}));

// ---------------------------------------------------------------------------
// Shared stubs
// ---------------------------------------------------------------------------

const globalStubs = {
    NuxtLink: {
        template: '<a :href="to"><slot /></a>',
        props: ["to"],
    },
};

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeCategoryNoSlugs() {
    return {
        id: 1,
        name: "PLA Filaments",
        slug: "pla-filaments",
        children: [],
    };
}

function makeCategoryWithSlugs() {
    return {
        id: 1,
        name: "PLA Filaments",
        slug: "pla-filaments",
        slugs: [
            { locale: "en", slug: "pla-filaments" },
            { locale: "es", slug: "filamentos-pla" },
            { locale: "fr", slug: "filaments-pla" },
        ],
        children: [],
    };
}

function makeCategoryEnOnly() {
    return {
        id: 2,
        name: "PETG",
        slug: "petg",
        slugs: [{ locale: "en", slug: "petg" }],
        children: [],
    };
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("CategoryChip component", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
        // Reset language to 'en'
        stateStore["localization.language"] = ref("en");
        vi.stubGlobal("useLocalization", () => ({
            language: stateStore["localization.language"],
        }));
        vi.resetModules();
    });

    // -----------------------------------------------------------------------
    // Rendering
    // -----------------------------------------------------------------------

    it("renders the category name", async () => {
        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: { category: makeCategoryNoSlugs() },
            global: { stubs: globalStubs },
        });

        expect(wrapper.text()).toContain("PLA Filaments");
    });

    // -----------------------------------------------------------------------
    // Link href — no slugs array (legacy fallback)
    // -----------------------------------------------------------------------

    it("links to /{category.slug} when no slugs array present", async () => {
        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: { category: makeCategoryNoSlugs() },
            global: { stubs: globalStubs },
        });

        const link = wrapper.find("a");
        expect(link.attributes("href")).toBe("/pla-filaments");
    });

    // -----------------------------------------------------------------------
    // Link href — with slugs array, English locale
    // -----------------------------------------------------------------------

    it("links to /{en-slug} when language is 'en' and slugs are present", async () => {
        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: { category: makeCategoryWithSlugs() },
            global: { stubs: globalStubs },
        });

        const link = wrapper.find("a");
        expect(link.attributes("href")).toBe("/pla-filaments");
    });

    // -----------------------------------------------------------------------
    // Link href — with slugs array, Spanish locale
    // -----------------------------------------------------------------------

    it("links using the Spanish slug when language is 'es'", async () => {
        stateStore["localization.language"] = ref("es");
        vi.stubGlobal("useLocalization", () => ({
            language: stateStore["localization.language"],
        }));

        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: { category: makeCategoryWithSlugs() },
            global: { stubs: globalStubs },
        });

        const link = wrapper.find("a");
        expect(link.attributes("href")).toBe("/filamentos-pla");
    });

    // -----------------------------------------------------------------------
    // Link href — fallback to default locale
    // -----------------------------------------------------------------------

    it("falls back to English slug when current language has no matching slug", async () => {
        stateStore["localization.language"] = ref("de");
        vi.stubGlobal("useLocalization", () => ({
            language: stateStore["localization.language"],
        }));

        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: { category: makeCategoryEnOnly() },
            global: { stubs: globalStubs },
        });

        const link = wrapper.find("a");
        // 'de' not available → falls back to 'en'
        expect(link.attributes("href")).toBe("/petg");
    });

    // -----------------------------------------------------------------------
    // Reactivity — language change updates the link
    // -----------------------------------------------------------------------

    it("updates the link href when language changes", async () => {
        const language = ref("en");
        stateStore["localization.language"] = language;
        vi.stubGlobal("useLocalization", () => ({
            language: stateStore["localization.language"],
        }));

        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: { category: makeCategoryWithSlugs() },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").attributes("href")).toBe("/pla-filaments");

        // Change language to Spanish
        language.value = "es";
        await wrapper.vm.$nextTick();

        expect(wrapper.find("a").attributes("href")).toBe("/filamentos-pla");
    });

    // -----------------------------------------------------------------------
    // Fallback to legacy slug when slugs empty array
    // -----------------------------------------------------------------------

    it("falls back to category.slug when slugs array is empty", async () => {
        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const categoryWithEmptySlugs = {
            id: 3,
            name: "ABS",
            slug: "abs",
            slugs: [],
            children: [],
        };

        const wrapper = mount(CategoryChip, {
            props: { category: categoryWithEmptySlugs },
            global: { stubs: globalStubs },
        });

        const link = wrapper.find("a");
        expect(link.attributes("href")).toBe("/abs");
    });

    // -----------------------------------------------------------------------
    // parentSlug prop — prepends parent path to href
    // -----------------------------------------------------------------------

    it("prepends parentSlug to the href when parentSlug is provided", async () => {
        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: {
                category: makeCategoryNoSlugs(),
                parentSlug: "electronics",
            },
            global: { stubs: globalStubs },
        });

        const link = wrapper.find("a");
        expect(link.attributes("href")).toBe("/electronics/pla-filaments");
    });

    it("uses parentSlug with language-aware slug from slugs array", async () => {
        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: {
                category: makeCategoryWithSlugs(),
                parentSlug: "electronics",
            },
            global: { stubs: globalStubs },
        });

        const link = wrapper.find("a");
        expect(link.attributes("href")).toBe("/electronics/pla-filaments");
    });

    // -----------------------------------------------------------------------
    // Chip appearance — renders as a link element
    // -----------------------------------------------------------------------

    it("renders as an anchor element (via NuxtLink)", async () => {
        const { default: CategoryChip } = await import("../components/CategoryChip.vue");
        const wrapper = mount(CategoryChip, {
            props: { category: makeCategoryNoSlugs() },
            global: { stubs: globalStubs },
        });

        expect(wrapper.find("a").exists()).toBe(true);
    });
});
