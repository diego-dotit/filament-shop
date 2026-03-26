import { describe, it, expect, vi, beforeEach } from "vitest";
import { readFileSync } from "fs";
import { resolve } from "path";

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// The composable and plugin use auto-imported globals, so we must expose
// them on `globalThis` before the modules are loaded.
// ---------------------------------------------------------------------------

const mockFetch = vi.fn();

// $fetch.create is used by the plugin; in composable tests we rely on the
// global $fetch fallback (useNuxtApp throws ⟹ falls back to $fetch).
vi.stubGlobal("$fetch", Object.assign(mockFetch, { create: vi.fn(() => mockFetch) }));

// defineNuxtPlugin: just call the factory and expose its return value.
vi.stubGlobal("defineNuxtPlugin", (fn: (app: unknown) => unknown) => fn({}));

// useRuntimeConfig used by the plugin.
vi.stubGlobal("useRuntimeConfig", () => ({
    public: { apiBaseUrl: "http://localhost:8000" },
}));

// useNuxtApp: throw so composable falls back to global $fetch.
vi.stubGlobal("useNuxtApp", () => {
    throw new Error("outside Nuxt context — using $fetch fallback");
});

// ---------------------------------------------------------------------------
// Tests for useApi composable
// ---------------------------------------------------------------------------

describe("useApi composable", () => {
    beforeEach(() => {
        vi.clearAllMocks();
        localStorage.clear();
        // Force a fresh module import so localStorage state is picked up correctly.
        vi.resetModules();
    });

    it("calls $fetch with the given path", async () => {
        mockFetch.mockResolvedValueOnce({ data: "ok" });
        const { useApi } = await import("../composables/useApi");
        const api = useApi();

        await api("/products");

        expect(mockFetch).toHaveBeenCalledWith("/products", expect.any(Object));
    });

    it("injects Authorization header when token exists in localStorage", async () => {
        localStorage.setItem("auth_token", "test-jwt-token");
        mockFetch.mockResolvedValueOnce({ data: "ok" });

        const { useApi } = await import("../composables/useApi");
        const api = useApi();
        await api("/products");

        const callArgs = mockFetch.mock.calls[0];
        expect(callArgs[1]).toMatchObject({
            headers: expect.objectContaining({
                Authorization: "Bearer test-jwt-token",
            }),
        });
    });

    it("does NOT inject Authorization header when no token in localStorage", async () => {
        // Ensure localStorage is empty (no token)
        mockFetch.mockResolvedValueOnce({ data: "ok" });

        const { useApi } = await import("../composables/useApi");
        const api = useApi();
        await api("/products");

        const callArgs = mockFetch.mock.calls[0];
        const headers = (callArgs[1] as Record<string, unknown>)?.headers ?? {};
        expect(headers).not.toHaveProperty("Authorization");
    });

    it("injects Accept-Language header from localStorage app_language", async () => {
        localStorage.setItem("app_language", "fr");
        mockFetch.mockResolvedValueOnce({ data: "ok" });

        const { useApi } = await import("../composables/useApi");
        const api = useApi();
        await api("/products");

        const callArgs = mockFetch.mock.calls[0];
        expect(callArgs[1]).toMatchObject({
            headers: expect.objectContaining({
                "Accept-Language": "fr",
            }),
        });
    });

    it("injects Accept-Currency header from localStorage app_currency", async () => {
        localStorage.setItem("app_currency", "EUR");
        mockFetch.mockResolvedValueOnce({ data: "ok" });

        const { useApi } = await import("../composables/useApi");
        const api = useApi();
        await api("/products");

        const callArgs = mockFetch.mock.calls[0];
        expect(callArgs[1]).toMatchObject({
            headers: expect.objectContaining({
                "Accept-Currency": "EUR",
            }),
        });
    });

    it('defaults Accept-Language to "en" when app_language not in localStorage', async () => {
        mockFetch.mockResolvedValueOnce({ data: "ok" });

        const { useApi } = await import("../composables/useApi");
        const api = useApi();
        await api("/products");

        const callArgs = mockFetch.mock.calls[0];
        expect(callArgs[1]).toMatchObject({
            headers: expect.objectContaining({
                "Accept-Language": "en",
            }),
        });
    });

    it('defaults Accept-Currency to "USD" when app_currency not in localStorage', async () => {
        mockFetch.mockResolvedValueOnce({ data: "ok" });

        const { useApi } = await import("../composables/useApi");
        const api = useApi();
        await api("/products");

        const callArgs = mockFetch.mock.calls[0];
        expect(callArgs[1]).toMatchObject({
            headers: expect.objectContaining({
                "Accept-Currency": "USD",
            }),
        });
    });

    it("injects all three headers together when token, language, and currency are set", async () => {
        localStorage.setItem("auth_token", "my-token");
        localStorage.setItem("app_language", "es");
        localStorage.setItem("app_currency", "MXN");
        mockFetch.mockResolvedValueOnce({ data: "ok" });

        const { useApi } = await import("../composables/useApi");
        const api = useApi();
        await api("/products");

        const callArgs = mockFetch.mock.calls[0];
        expect(callArgs[1]).toMatchObject({
            headers: expect.objectContaining({
                Authorization: "Bearer my-token",
                "Accept-Language": "es",
                "Accept-Currency": "MXN",
            }),
        });
    });

    it("passes through additional fetch options", async () => {
        mockFetch.mockResolvedValueOnce({});

        const { useApi } = await import("../composables/useApi");
        const api = useApi();
        await api("/products", { method: "POST", body: { name: "PLA" } });

        const callArgs = mockFetch.mock.calls[0];
        expect(callArgs[1]).toMatchObject({ method: "POST", body: { name: "PLA" } });
    });
});

// ---------------------------------------------------------------------------
// Tests for api.ts plugin structure
// ---------------------------------------------------------------------------

describe("api plugin", () => {
    it("plugin file exports a defined default export", async () => {
        vi.resetModules();
        const plugin = await import("../plugins/api");
        expect(plugin.default).toBeDefined();
    });

    it("plugin injects Accept-Language and Accept-Currency headers via onRequest hook", async () => {
        vi.resetModules();

        // Capture the onRequest callback passed to $fetch.create
        let capturedOnRequest:
            | ((ctx: { options: { headers?: Record<string, string> } }) => void)
            | undefined;

        const createMock = vi.fn(
            (opts: {
                onRequest?: (ctx: { options: { headers?: Record<string, string> } }) => void;
            }) => {
                capturedOnRequest = opts.onRequest;
                return mockFetch;
            }
        );

        vi.stubGlobal("$fetch", Object.assign(mockFetch, { create: createMock }));

        localStorage.setItem("app_language", "de");
        localStorage.setItem("app_currency", "EUR");

        await import("../plugins/api");

        expect(capturedOnRequest).toBeDefined();

        const ctx = { options: { headers: {} as Record<string, string> } };
        capturedOnRequest!(ctx);

        expect(ctx.options.headers).toMatchObject({
            "Accept-Language": "de",
            "Accept-Currency": "EUR",
        });
    });
});

// ---------------------------------------------------------------------------
// Tests for nuxt.config.ts runtimeConfig
// ---------------------------------------------------------------------------

describe("nuxt.config runtimeConfig", () => {
    it("nuxt.config.ts contains runtimeConfig with apiBaseUrl pointing to localhost:8000", () => {
        const configPath = resolve(__dirname, "../nuxt.config.ts");
        const content = readFileSync(configPath, "utf8");

        expect(content).toContain("runtimeConfig");
        expect(content).toContain("apiBaseUrl");
        expect(content).toContain("localhost:8000");
    });
});
