import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// ---------------------------------------------------------------------------

const mockFetch = vi.fn();

vi.stubGlobal("$fetch", Object.assign(mockFetch, { create: vi.fn(() => mockFetch) }));

// defineNuxtPlugin: execute the factory immediately and return the result
vi.stubGlobal("defineNuxtPlugin", (fn: (app: unknown) => unknown) => fn({}));

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context — using $fetch fallback");
});

vi.stubGlobal("computed", computed);

vi.stubGlobal("useApi", () => mockFetch);

// Mutable auth state — tests control isAuthenticated
const mockRestoreSession = vi.fn();
const isAuthenticatedRef = ref(false);

vi.stubGlobal("useAuth", () => ({
    restoreSession: mockRestoreSession,
    isAuthenticated: isAuthenticatedRef,
    user: ref(null),
}));

// useState: simulate Nuxt's shared state via a single ref per key
const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", <T>(key: string, init: () => T) => {
    if (!stateStore[key]) {
        stateStore[key] = ref<T>(init());
    }
    return stateStore[key];
});

// ---------------------------------------------------------------------------
// Tests for auth.client plugin — cart loading on startup
// ---------------------------------------------------------------------------

describe("auth.client plugin — cart initialization", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();

        // Reset auth state
        isAuthenticatedRef.value = false;

        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();
    });

    it("calls fetchCart() after restoreSession() completes for a guest user", async () => {
        // Guest: isAuthenticated stays false, no localStorage cart
        // fetchCart() should initialize a default guest cart
        await import("../plugins/auth.client");

        const { useCart } = await import("../composables/useCart");
        const { cart } = useCart();

        // Cart should be initialized (not null) even for guests with no stored cart
        expect(cart.value).not.toBeNull();
        expect(cart.value).toMatchObject({ id: "guest", items: [] });
    });

    it("calls fetchCart() after restoreSession() for a guest with items in localStorage", async () => {
        const storedCart = {
            id: "guest",
            items: [
                {
                    id: 1,
                    product: { id: 10, name: "PLA Filament", slug: "pla" },
                    variant: { id: 100, sku: "PLA-RED" },
                    quantity: 3,
                    line_total: 59.97,
                },
            ],
            total: 59.97,
        };
        localStorage.setItem("guest_cart", JSON.stringify(storedCart));

        // Guest: isAuthenticated stays false
        await import("../plugins/auth.client");

        const { useCart } = await import("../composables/useCart");
        const { cart, itemCount } = useCart();

        expect(cart.value).toEqual(storedCart);
        expect(itemCount.value).toBe(3);
    });

    it("calls fetchCart() via API after restoreSession() for an authenticated user", async () => {
        // Simulate restoreSession setting isAuthenticated = true
        mockRestoreSession.mockImplementationOnce(async () => {
            isAuthenticatedRef.value = true;
        });

        const mockCartData = {
            id: "cart-uuid",
            items: [
                {
                    id: 2,
                    product: { id: 20, name: "ABS Filament", slug: "abs" },
                    variant: { id: 200, sku: "ABS-BLACK" },
                    quantity: 2,
                    line_total: 39.98,
                },
            ],
            total: 39.98,
        };
        // API cart fetch returns cart data
        mockFetch.mockResolvedValueOnce({ data: mockCartData });

        await import("../plugins/auth.client");

        const { useCart } = await import("../composables/useCart");
        const { cart, itemCount } = useCart();

        expect(mockFetch).toHaveBeenCalledWith("/cart");
        expect(cart.value).toEqual(mockCartData);
        expect(itemCount.value).toBe(2);
    });

    it("does not break app startup when fetchCart() API call throws an error", async () => {
        // Authenticated user whose cart API fails
        mockRestoreSession.mockImplementationOnce(async () => {
            isAuthenticatedRef.value = true;
        });
        mockFetch.mockRejectedValueOnce(new Error("Network error"));

        // Plugin should not throw even when fetchCart fails
        await expect(import("../plugins/auth.client")).resolves.not.toThrow();
    });

    it("does not break app startup when localStorage cart JSON is corrupt", async () => {
        // Guest with a broken localStorage entry
        localStorage.setItem("guest_cart", "invalid-json{{{");

        // Plugin should not throw — useCart._loadGuestCart handles parse errors
        await expect(import("../plugins/auth.client")).resolves.not.toThrow();
    });
});
