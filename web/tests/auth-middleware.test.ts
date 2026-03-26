import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref, computed } from 'vue'

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// ---------------------------------------------------------------------------

// Capture the inner handler passed to defineNuxtRouteMiddleware so we can
// invoke it directly in tests without a running Nuxt router.
let capturedHandler: ((to: { path: string; fullPath: string }, from: unknown) => unknown) | null =
  null

vi.stubGlobal('defineNuxtRouteMiddleware', (handler: typeof capturedHandler) => {
  capturedHandler = handler
  return handler
})

// navigateTo: track calls, return a redirect descriptor (like Nuxt does)
const mockNavigateTo = vi.fn((target: string) => ({ redirectTo: target }))
vi.stubGlobal('navigateTo', mockNavigateTo)

// computed: use real Vue implementation
vi.stubGlobal('computed', computed)

// useState: simulate Nuxt's shared state via a plain ref per key
vi.stubGlobal('useState', <T>(_key: string, init: () => T) => ref<T>(init()))

// useApi: return a no-op stub (useAuth internally calls useApi)
vi.stubGlobal('useApi', () => vi.fn())

// Default useAuth stub — not authenticated (overridden per test where needed)
vi.stubGlobal('useAuth', () => ({
  isAuthenticated: computed(() => false),
  user: ref(null),
}))

// ---------------------------------------------------------------------------
// Helper: import (or re-import) the middleware module.
// We reset modules between tests so that each test gets a fresh capturedHandler.
// ---------------------------------------------------------------------------

async function importMiddleware() {
  const mod = await import('../middleware/auth')
  return mod.default
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('auth middleware', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    capturedHandler = null
    vi.resetModules()
  })

  it('registers via defineNuxtRouteMiddleware', async () => {
    await importMiddleware()
    expect(capturedHandler).not.toBeNull()
  })

  it('redirects unauthenticated user to /login with redirect query param', async () => {
    vi.stubGlobal('useAuth', () => ({
      isAuthenticated: computed(() => false),
      user: ref(null),
    }))

    await importMiddleware()

    const to = { path: '/account/dashboard', fullPath: '/account/dashboard' }
    const result = capturedHandler!(to, {})

    expect(mockNavigateTo).toHaveBeenCalledWith('/login?redirect=/account/dashboard')
    expect(result).toEqual({ redirectTo: '/login?redirect=/account/dashboard' })
  })

  it('redirects unauthenticated user from /checkout with correct redirect path', async () => {
    vi.stubGlobal('useAuth', () => ({
      isAuthenticated: computed(() => false),
      user: ref(null),
    }))

    await importMiddleware()

    const to = { path: '/checkout', fullPath: '/checkout' }
    capturedHandler!(to, {})

    expect(mockNavigateTo).toHaveBeenCalledWith('/login?redirect=/checkout')
  })

  it('allows authenticated user to pass through without redirecting', async () => {
    vi.stubGlobal('useAuth', () => ({
      isAuthenticated: computed(() => true),
      user: ref({ id: 1, name: 'Alice', email: 'alice@example.com' }),
    }))

    await importMiddleware()

    const to = { path: '/account/orders', fullPath: '/account/orders' }
    const result = capturedHandler!(to, {})

    expect(mockNavigateTo).not.toHaveBeenCalled()
    expect(result).toBeUndefined()
  })

  it('allows authenticated user to access /cart without redirecting', async () => {
    vi.stubGlobal('useAuth', () => ({
      isAuthenticated: computed(() => true),
      user: ref({ id: 2, name: 'Bob', email: 'bob@example.com' }),
    }))

    await importMiddleware()

    const to = { path: '/cart', fullPath: '/cart' }
    const result = capturedHandler!(to, {})

    expect(mockNavigateTo).not.toHaveBeenCalled()
    expect(result).toBeUndefined()
  })

  it('includes the full path in the redirect query param', async () => {
    vi.stubGlobal('useAuth', () => ({
      isAuthenticated: computed(() => false),
      user: ref(null),
    }))

    await importMiddleware()

    const to = { path: '/account/orders/42', fullPath: '/account/orders/42' }
    capturedHandler!(to, {})

    expect(mockNavigateTo).toHaveBeenCalledWith('/login?redirect=/account/orders/42')
  })
})
