import { describe, it, expect, vi, beforeEach } from "vitest";
import { ref, computed } from "vue";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// The composable uses auto-imported globals, so we must expose them on
// `globalThis` before the modules are loaded.
// ---------------------------------------------------------------------------

const mockFetch = vi.fn();

vi.stubGlobal("$fetch", Object.assign(mockFetch, { create: vi.fn(() => mockFetch) }));

vi.stubGlobal("defineNuxtPlugin", (fn: (app: unknown) => unknown) => fn({}));

vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// useNuxtApp throws → useApi falls back to $fetch (irrelevant here since we stub useApi)
vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context — using $fetch fallback");
});

// Stub Vue's computed with the real implementation so computed props work
vi.stubGlobal("computed", computed);

// useApi: return mockFetch directly so the composable can make requests
vi.stubGlobal("useApi", () => mockFetch);

// useState: simulate Nuxt's shared state via a single ref per key
const stateStore: Record<string, ReturnType<typeof ref>> = {};
vi.stubGlobal("useState", <T>(key: string, init: () => T) => {
    if (!stateStore[key]) {
        stateStore[key] = ref<T>(init());
    }
    return stateStore[key];
});

// ---------------------------------------------------------------------------
// Test fixtures
// ---------------------------------------------------------------------------

const mockCustomer = { id: 1, name: "Alice Smith", email: "alice@example.com" };
const mockToken = "jwt-token-abc123";

// ---------------------------------------------------------------------------
// Tests for useAuth composable
// ---------------------------------------------------------------------------

describe("useAuth composable", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();

        // Reset shared state between tests
        for (const key of Object.keys(stateStore)) {
            delete stateStore[key];
        }

        vi.resetModules();
    });

    // ── login ──────────────────────────────────────────────────────────────────

    it("login: calls POST /auth/login with email and password", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockCustomer, token: mockToken });

        const { useAuth } = await import("../composables/useAuth");
        const { login, _initPromise } = useAuth();
        await _initPromise;

        await login("alice@example.com", "secret");

        expect(mockFetch).toHaveBeenCalledWith(
            "/auth/login",
            expect.objectContaining({
                method: "POST",
                body: { email: "alice@example.com", password: "secret" },
            })
        );
    });

    it("login: on success stores token in localStorage and updates user state", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockCustomer, token: mockToken });

        const { useAuth } = await import("../composables/useAuth");
        const { login, user, _initPromise } = useAuth();
        await _initPromise;

        const [result, error] = await login("alice@example.com", "secret");

        expect(error).toBeNull();
        expect(result).toMatchObject({ customer: mockCustomer, token: mockToken });
        expect(user.value).toEqual(mockCustomer);
        expect(localStorage.getItem("auth_token")).toBe(mockToken);
    });

    it("login: handles nested data.customer response shape", async () => {
        // Some API endpoints wrap customer inside data.customer
        mockFetch.mockResolvedValueOnce({
            data: { customer: mockCustomer },
            token: mockToken,
        });

        const { useAuth } = await import("../composables/useAuth");
        const { login, user, _initPromise } = useAuth();
        await _initPromise;

        const [result, error] = await login("alice@example.com", "secret");

        expect(error).toBeNull();
        expect(user.value).toEqual(mockCustomer);
        expect(result?.customer).toEqual(mockCustomer);
    });

    it("login: on failure returns [null, error] tuple and does not update state", async () => {
        const apiError = new Error("Invalid credentials");
        mockFetch.mockRejectedValueOnce(apiError);

        const { useAuth } = await import("../composables/useAuth");
        const { login, user, _initPromise } = useAuth();
        await _initPromise;

        const [result, error] = await login("alice@example.com", "wrong");

        expect(result).toBeNull();
        expect(error).toBe(apiError);
        expect(user.value).toBeNull();
        expect(localStorage.getItem("auth_token")).toBeNull();
    });

    // ── register ───────────────────────────────────────────────────────────────

    it("register: calls POST /auth/register with name, email, password, password_confirmation", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockCustomer, token: mockToken });

        const { useAuth } = await import("../composables/useAuth");
        const { register, _initPromise } = useAuth();
        await _initPromise;

        await register("Alice Smith", "alice@example.com", "secret", "secret");

        expect(mockFetch).toHaveBeenCalledWith(
            "/auth/register",
            expect.objectContaining({
                method: "POST",
                body: {
                    name: "Alice Smith",
                    email: "alice@example.com",
                    password: "secret",
                    password_confirmation: "secret",
                },
            })
        );
    });

    it("register: on success logs user in automatically (stores token + updates state)", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockCustomer, token: mockToken });

        const { useAuth } = await import("../composables/useAuth");
        const { register, user, isAuthenticated, _initPromise } = useAuth();
        await _initPromise;

        const [result, error] = await register(
            "Alice Smith",
            "alice@example.com",
            "secret",
            "secret"
        );

        expect(error).toBeNull();
        expect(result).toMatchObject({ customer: mockCustomer, token: mockToken });
        expect(user.value).toEqual(mockCustomer);
        expect(isAuthenticated.value).toBe(true);
        expect(localStorage.getItem("auth_token")).toBe(mockToken);
    });

    it("register: on failure returns [null, error] tuple and does not update state", async () => {
        const apiError = new Error("Email already taken");
        mockFetch.mockRejectedValueOnce(apiError);

        const { useAuth } = await import("../composables/useAuth");
        const { register, user, _initPromise } = useAuth();
        await _initPromise;

        const [result, error] = await register(
            "Alice Smith",
            "alice@example.com",
            "secret",
            "secret"
        );

        expect(result).toBeNull();
        expect(error).toBe(apiError);
        expect(user.value).toBeNull();
    });

    // ── logout ─────────────────────────────────────────────────────────────────

    it("logout: clears token from localStorage and resets user to null", async () => {
        // First log in
        mockFetch.mockResolvedValueOnce({ data: mockCustomer, token: mockToken });

        const { useAuth } = await import("../composables/useAuth");
        const { login, logout, user, isAuthenticated, _initPromise } = useAuth();
        await _initPromise;

        await login("alice@example.com", "secret");
        expect(user.value).toEqual(mockCustomer);
        expect(isAuthenticated.value).toBe(true);

        // Then log out
        logout();

        expect(user.value).toBeNull();
        expect(isAuthenticated.value).toBe(false);
        expect(localStorage.getItem("auth_token")).toBeNull();
    });

    // ── isAuthenticated ────────────────────────────────────────────────────────

    it("isAuthenticated: is false when user is null (initial state)", async () => {
        const { useAuth } = await import("../composables/useAuth");
        const { isAuthenticated, _initPromise } = useAuth();
        await _initPromise;

        expect(isAuthenticated.value).toBe(false);
    });

    it("isAuthenticated: is true after successful login", async () => {
        mockFetch.mockResolvedValueOnce({ data: mockCustomer, token: mockToken });

        const { useAuth } = await import("../composables/useAuth");
        const { login, isAuthenticated, _initPromise } = useAuth();
        await _initPromise;

        await login("alice@example.com", "secret");

        expect(isAuthenticated.value).toBe(true);
    });

    // ── session restore ────────────────────────────────────────────────────────

    it("session restore: calls GET /auth/me when auth_token exists in localStorage", async () => {
        localStorage.setItem("auth_token", mockToken);
        mockFetch.mockResolvedValueOnce(mockCustomer);

        const { useAuth } = await import("../composables/useAuth");
        const { _initPromise } = useAuth();
        await _initPromise;

        expect(mockFetch).toHaveBeenCalledWith("/auth/me");
    });

    it("session restore: sets user state from /auth/me response", async () => {
        localStorage.setItem("auth_token", mockToken);
        mockFetch.mockResolvedValueOnce(mockCustomer);

        const { useAuth } = await import("../composables/useAuth");
        const { user, isAuthenticated, _initPromise } = useAuth();
        await _initPromise;

        expect(user.value).toEqual(mockCustomer);
        expect(isAuthenticated.value).toBe(true);
    });

    it("session restore: does NOT call /auth/me when no token in localStorage", async () => {
        const { useAuth } = await import("../composables/useAuth");
        const { user, _initPromise } = useAuth();
        await _initPromise;

        expect(mockFetch).not.toHaveBeenCalledWith("/auth/me");
        expect(user.value).toBeNull();
    });

    it("session restore: clears token and user state on 401 response from /auth/me", async () => {
        localStorage.setItem("auth_token", "expired-token");
        mockFetch.mockRejectedValueOnce({ status: 401 });

        const { useAuth } = await import("../composables/useAuth");
        const { user, isAuthenticated, _initPromise } = useAuth();
        await _initPromise;

        expect(user.value).toBeNull();
        expect(isAuthenticated.value).toBe(false);
        expect(localStorage.getItem("auth_token")).toBeNull();
    });

    it("session restore: also handles statusCode 401 for graceful expiration", async () => {
        localStorage.setItem("auth_token", "expired-token");
        mockFetch.mockRejectedValueOnce({ statusCode: 401 });

        const { useAuth } = await import("../composables/useAuth");
        const { user, _initPromise } = useAuth();
        await _initPromise;

        expect(user.value).toBeNull();
        expect(localStorage.getItem("auth_token")).toBeNull();
    });
});
