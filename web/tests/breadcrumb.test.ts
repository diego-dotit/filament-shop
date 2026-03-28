/**
 * Tests for Breadcrumb component.
 *
 * Acceptance criteria:
 *  - Breadcrumb always shows "Home" as the first item with a link to "/"
 *  - Items passed via props render in order after "Home"
 *  - Each item with a `url` renders as a NuxtLink (clickable link)
 *  - Items without `url` render as a non-link span (typically the current page)
 *  - Separators appear between breadcrumb items
 *  - Item names are displayed as provided (parent resolves language)
 *  - Empty items array shows only Home
 *  - Items with url render an anchor with the correct href
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";
import type { SlugRecord } from "../composables/useSlug";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE importing the component under test.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);

const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", (key: string, init: () => unknown) => {
    if (!stateStore[key]) {
        stateStore[key] = ref(init());
    }
    return stateStore[key];
});

// ---------------------------------------------------------------------------
// Import component after globals are stubbed
// ---------------------------------------------------------------------------

import Breadcrumb from "../components/Breadcrumb.vue";

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

function makeSlugs(locale: string, slug: string): SlugRecord[] {
    return [{ locale, slug }];
}

const globalStubs = {
    NuxtLink: {
        props: ["to"],
        template: `<a :href="to"><slot /></a>`,
    },
};

// ---------------------------------------------------------------------------
// BreadcrumbItem type (mirrors component props)
// ---------------------------------------------------------------------------

interface BreadcrumbItem {
    id: number;
    name: string;
    slugs: SlugRecord[];
    url?: string;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe("Breadcrumb", () => {
    beforeEach(() => {
        // Reset shared state between tests
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }
    });

    it("renders Home as the first breadcrumb item", () => {
        const wrapper = mount(Breadcrumb, {
            props: { items: [] },
            global: { stubs: globalStubs },
        });

        const links = wrapper.findAll("a");
        expect(links.length).toBeGreaterThanOrEqual(1);
        expect(links[0].attributes("href")).toBe("/");
        expect(links[0].text()).toBe("Home");
    });

    it("renders only Home when items array is empty", () => {
        const wrapper = mount(Breadcrumb, {
            props: { items: [] },
            global: { stubs: globalStubs },
        });

        const links = wrapper.findAll("a");
        expect(links).toHaveLength(1);
        expect(links[0].attributes("href")).toBe("/");
    });

    it("renders item names in the correct order after Home", () => {
        const items: BreadcrumbItem[] = [
            { id: 1, name: "PLA Filaments", slugs: makeSlugs("en", "pla-filaments"), url: "/pla-filaments" },
            { id: 2, name: "1.75mm", slugs: makeSlugs("en", "1-75mm"), url: "/pla-filaments/1-75mm" },
        ];

        const wrapper = mount(Breadcrumb, {
            props: { items },
            global: { stubs: globalStubs },
        });

        const text = wrapper.text();
        const homeIndex = text.indexOf("Home");
        const catIndex = text.indexOf("PLA Filaments");
        const subcatIndex = text.indexOf("1.75mm");

        expect(homeIndex).toBeGreaterThanOrEqual(0);
        expect(catIndex).toBeGreaterThan(homeIndex);
        expect(subcatIndex).toBeGreaterThan(catIndex);
    });

    it("renders items with url as NuxtLink anchors with correct href", () => {
        const items: BreadcrumbItem[] = [
            { id: 1, name: "PLA Filaments", slugs: makeSlugs("en", "pla-filaments"), url: "/pla-filaments" },
            { id: 2, name: "1.75mm", slugs: makeSlugs("en", "1-75mm"), url: "/pla-filaments/1-75mm" },
        ];

        const wrapper = mount(Breadcrumb, {
            props: { items },
            global: { stubs: globalStubs },
        });

        const links = wrapper.findAll("a");
        // links[0] = Home, links[1] = PLA Filaments, links[2] = 1.75mm
        expect(links).toHaveLength(3);
        expect(links[1].attributes("href")).toBe("/pla-filaments");
        expect(links[2].attributes("href")).toBe("/pla-filaments/1-75mm");
    });

    it("renders item without url as non-link text (current page)", () => {
        const items: BreadcrumbItem[] = [
            { id: 1, name: "PLA Filaments", slugs: makeSlugs("en", "pla-filaments"), url: "/pla-filaments" },
            { id: 2, name: "PLA Red", slugs: makeSlugs("en", "pla-red") /* no url */ },
        ];

        const wrapper = mount(Breadcrumb, {
            props: { items },
            global: { stubs: globalStubs },
        });

        const links = wrapper.findAll("a");
        // Home + PLA Filaments = 2 links; PLA Red has no url → not a link
        expect(links).toHaveLength(2);

        // "PLA Red" text should still be visible
        expect(wrapper.text()).toContain("PLA Red");
    });

    it("renders separators between breadcrumb items", () => {
        const items: BreadcrumbItem[] = [
            { id: 1, name: "Category", slugs: makeSlugs("en", "category"), url: "/category" },
            { id: 2, name: "Product", slugs: makeSlugs("en", "product") },
        ];

        const wrapper = mount(Breadcrumb, {
            props: { items },
            global: { stubs: globalStubs },
        });

        // Should have at least 2 separator elements (Home→Category, Category→Product)
        const separators = wrapper.findAll("[data-testid='breadcrumb-separator']");
        expect(separators.length).toBeGreaterThanOrEqual(2);
    });

    it("renders correct item names in text content", () => {
        const items: BreadcrumbItem[] = [
            {
                id: 10,
                name: "Filamentos PLA",
                slugs: [
                    { locale: "en", slug: "pla-filaments" },
                    { locale: "es", slug: "filamentos-pla" },
                ],
                url: "/filamentos-pla",
            },
        ];

        const wrapper = mount(Breadcrumb, {
            props: { items },
            global: { stubs: globalStubs },
        });

        expect(wrapper.text()).toContain("Filamentos PLA");
    });

    it("renders three-level hierarchy: Home → Category → Subcategory → Product", () => {
        const items: BreadcrumbItem[] = [
            { id: 1, name: "PLA Filaments", slugs: makeSlugs("en", "pla-filaments"), url: "/pla-filaments" },
            { id: 2, name: "1.75mm", slugs: makeSlugs("en", "1-75mm"), url: "/pla-filaments/1-75mm" },
            { id: 3, name: "PLA Red", slugs: makeSlugs("en", "pla-red"), url: "/pla-filaments/1-75mm/pla-red" },
        ];

        const wrapper = mount(Breadcrumb, {
            props: { items },
            global: { stubs: globalStubs },
        });

        const links = wrapper.findAll("a");
        expect(links).toHaveLength(4); // Home + 3 items

        expect(links[0].attributes("href")).toBe("/");
        expect(links[1].attributes("href")).toBe("/pla-filaments");
        expect(links[2].attributes("href")).toBe("/pla-filaments/1-75mm");
        expect(links[3].attributes("href")).toBe("/pla-filaments/1-75mm/pla-red");

        expect(links[0].text()).toBe("Home");
        expect(links[1].text()).toBe("PLA Filaments");
        expect(links[2].text()).toBe("1.75mm");
        expect(links[3].text()).toBe("PLA Red");
    });

    it("has nav element with aria-label for accessibility", () => {
        const wrapper = mount(Breadcrumb, {
            props: { items: [] },
            global: { stubs: globalStubs },
        });

        const nav = wrapper.find("nav");
        expect(nav.exists()).toBe(true);
        expect(nav.attributes("aria-label")).toBeTruthy();
    });
});
