/**
 * Tests that verify ReviewForm.vue has been migrated to shadcn components.
 * These check structural requirements: no BEM classes, no style block,
 * and that shadcn Button / Textarea are used in the template.
 */

import { describe, it, expect, vi, beforeEach } from "vitest";
import { mount } from "@vue/test-utils";
import { ref, computed } from "vue";
import { readFileSync, existsSync } from "node:fs";
import { resolve, dirname } from "node:path";
import { fileURLToPath } from "node:url";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal("computed", computed);
vi.stubGlobal("useState", <T>(_key: string, init: () => T) => ref<T>(init()));
vi.stubGlobal("useApi", () => vi.fn());
vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context");
});
vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const __dirname = dirname(fileURLToPath(import.meta.url));
const componentPath = resolve(__dirname, "../components/ReviewForm.vue");

function readComponent(): string {
    return readFileSync(componentPath, "utf-8");
}

function setupAuthStub({ isAuthenticated = true } = {}) {
    const user = isAuthenticated
        ? ref({ id: 1, name: "Alice", email: "alice@example.com" })
        : ref(null);
    vi.stubGlobal("useAuth", () => ({
        user,
        isAuthenticated: computed(() => user.value !== null),
    }));
}

// ---------------------------------------------------------------------------
// Structural tests (source code inspection)
// ---------------------------------------------------------------------------

describe("ReviewForm.vue — shadcn migration: source structure", () => {
    it("component file exists", () => {
        expect(existsSync(componentPath)).toBe(true);
    });

    it("has NO <style> block (scoped CSS removed)", () => {
        const src = readComponent();
        expect(src).not.toMatch(/<style/);
    });

    it("has NO BEM class names (.review-form__*)", () => {
        const src = readComponent();
        expect(src).not.toMatch(/review-form__/);
    });

    it("imports Button from @/components/ui/button", () => {
        const src = readComponent();
        expect(src).toMatch(/from ['"]@\/components\/ui\/button['"]/);
    });

    it("imports Textarea from @/components/ui/textarea", () => {
        const src = readComponent();
        expect(src).toMatch(/from ['"]@\/components\/ui\/textarea['"]/);
    });

    it("uses <Button> in template", () => {
        const src = readComponent();
        expect(src).toMatch(/<Button/);
    });

    it("uses <Textarea> in template", () => {
        const src = readComponent();
        expect(src).toMatch(/<Textarea/);
    });

    it("submit button uses data-testid='submit-review'", () => {
        const src = readComponent();
        expect(src).toMatch(/data-testid="submit-review"/);
    });

    it("submit Button has :disabled binding", () => {
        const src = readComponent();
        // e.g. :disabled="submitting"
        expect(src).toMatch(/:disabled="submitting"/);
    });

    it("star Button components have data-testid star-N pattern", () => {
        const src = readComponent();
        expect(src).toMatch(/data-testid="`star-/);
    });

    it("Textarea has maxlength='500' (not 501)", () => {
        const src = readComponent();
        expect(src).toMatch(/maxlength="500"/);
    });

    it("Textarea has rows='4' attribute", () => {
        const src = readComponent();
        expect(src).toMatch(/rows="4"/);
    });

    it("character count display has conditional text-red-500 class", () => {
        const src = readComponent();
        expect(src).toMatch(/text-red-500/);
    });

    it("rating error paragraph has role='alert'", () => {
        const src = readComponent();
        // ratingError shown with role="alert"
        expect(src).toMatch(/role="alert"/);
    });
});

// ---------------------------------------------------------------------------
// Runtime behaviour tests (mount the migrated component)
// ---------------------------------------------------------------------------

describe("ReviewForm.vue — shadcn migration: runtime behaviour", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        vi.resetModules();
    });

    it("renders 5 star buttons via Button components", async () => {
        setupAuthStub({ isAuthenticated: true });
        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, { props: { productId: 1 } });

        const starButtons = wrapper.findAll('[data-testid^="star-"]');
        expect(starButtons.length).toBe(5);
    });

    it("star buttons show filled star (★) when rating >= star", async () => {
        setupAuthStub({ isAuthenticated: true });
        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, { props: { productId: 1 } });

        // Click star-3
        await wrapper.find('[data-testid="star-3"]').trigger("click");

        // Stars 1, 2, 3 should show ★; stars 4, 5 should show ☆
        expect(wrapper.find('[data-testid="star-1"]').text()).toBe("★");
        expect(wrapper.find('[data-testid="star-3"]').text()).toBe("★");
        expect(wrapper.find('[data-testid="star-4"]').text()).toBe("☆");
    });

    it("renders Textarea (native textarea element) for comment input", async () => {
        setupAuthStub({ isAuthenticated: true });
        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, { props: { productId: 1 } });

        expect(wrapper.find("textarea").exists()).toBe(true);
    });

    it("shows character count display with / 500 text", async () => {
        setupAuthStub({ isAuthenticated: true });
        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, { props: { productId: 1 } });

        expect(wrapper.text()).toContain("/ 500");
    });

    it("submit button is disabled while submitting", async () => {
        setupAuthStub({ isAuthenticated: true });
        const mockApi = vi.fn().mockImplementation(
            () => new Promise((resolve) => setTimeout(resolve, 5000))
        );
        vi.stubGlobal("useApi", () => mockApi);

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, { props: { productId: 1 } });

        // Select rating to pass validation
        await wrapper.find('[data-testid="star-5"]').trigger("click");

        // Trigger submit (don't await the promise)
        wrapper.find('[data-testid="submit-review"]').trigger("click");
        await wrapper.vm.$nextTick();

        const submitBtn = wrapper.find('[data-testid="submit-review"]');
        expect(submitBtn.attributes("disabled")).toBeDefined();
    });

    it("shows 'Submitting...' text while submitting", async () => {
        setupAuthStub({ isAuthenticated: true });
        const mockApi = vi.fn().mockImplementation(
            () => new Promise((resolve) => setTimeout(resolve, 5000))
        );
        vi.stubGlobal("useApi", () => mockApi);

        const { default: ReviewForm } = await import("../components/ReviewForm.vue");
        const wrapper = mount(ReviewForm, { props: { productId: 1 } });

        await wrapper.find('[data-testid="star-4"]').trigger("click");
        wrapper.find('[data-testid="submit-review"]').trigger("click");
        await wrapper.vm.$nextTick();

        expect(wrapper.find('[data-testid="submit-review"]').text()).toContain("Submitting");
    });
});
