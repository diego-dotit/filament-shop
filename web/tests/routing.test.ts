import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref, computed, reactive, watch } from 'vue'

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// ---------------------------------------------------------------------------

// Capture definePageMeta calls to verify middleware configuration
const mockDefinePageMeta = vi.fn()
vi.stubGlobal('definePageMeta', mockDefinePageMeta)

// navigateTo: no-op stub
vi.stubGlobal('navigateTo', vi.fn())

// Vue composables
vi.stubGlobal('computed', computed)
vi.stubGlobal('ref', ref)
vi.stubGlobal('reactive', reactive)
vi.stubGlobal('watch', watch)

// Nuxt app context
vi.stubGlobal('useNuxtApp', () => { throw new Error('outside Nuxt context') })
vi.stubGlobal('useRuntimeConfig', () => ({
  public: { apiBaseUrl: 'http://localhost:8000' },
}))

// useState: simulate Nuxt shared state
vi.stubGlobal('useState', <T>(_key: string, init: () => T) => ref<T>(init()))

// useApi: no-op
vi.stubGlobal('useApi', () => vi.fn())

// $fetch: no-op
const mockFetch = vi.fn()
vi.stubGlobal('$fetch', Object.assign(mockFetch, { create: vi.fn(() => mockFetch) }))
vi.stubGlobal('defineNuxtPlugin', (fn: (app: unknown) => unknown) => fn({}))

// useAuth: default unauthenticated
vi.stubGlobal('useAuth', () => ({
  user: ref(null),
  isAuthenticated: computed(() => false),
  logout: vi.fn(),
}))

// useCart: stub
vi.stubGlobal('useCart', () => ({
  cart: ref(null),
  items: ref([]),
  fetchCart: vi.fn(),
  addItem: vi.fn(),
  removeItem: vi.fn(),
  clearCart: vi.fn(),
}))

// useCheckout: stub
vi.stubGlobal('useCheckout', () => ({
  addresses: ref([]),
  billingAddressId: ref(null),
  shippingAddressId: ref(null),
  orderConfirmation: ref(null),
  error: ref(null),
  isSubmitting: ref(false),
  fetchAddresses: vi.fn(),
  selectBillingAddress: vi.fn(),
  selectShippingAddress: vi.fn(),
  submitOrder: vi.fn(),
}))

// useOrders: stub
vi.stubGlobal('useOrders', () => ({
  orders: ref([]),
  currentOrder: ref(null),
  loading: ref(false),
  error: ref(null),
  fetchOrders: vi.fn(),
  fetchOrder: vi.fn(),
}))

// useRoute: stub
vi.stubGlobal('useRoute', () => ({
  params: { id: '1', slug: 'test-slug' },
  query: {},
}))

// useHead: no-op
vi.stubGlobal('useHead', vi.fn())

// onMounted: no-op (don't run lifecycle hooks)
vi.stubGlobal('onMounted', vi.fn())

// ---------------------------------------------------------------------------
// Tests: Protected routes use definePageMeta({ middleware: 'auth' })
// ---------------------------------------------------------------------------

describe('Route middleware configuration', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.resetModules()

    // Re-stub definePageMeta after resetModules clears stubs
    vi.stubGlobal('definePageMeta', mockDefinePageMeta)
    vi.stubGlobal('navigateTo', vi.fn())
    vi.stubGlobal('onMounted', vi.fn())
  })

  // ── Protected pages ────────────────────────────────────────────────────────

  it('cart.vue registers auth middleware via definePageMeta', async () => {
    await import('../pages/cart.vue')
    expect(mockDefinePageMeta).toHaveBeenCalledWith(
      expect.objectContaining({ middleware: 'auth' }),
    )
  })

  it('checkout.vue registers auth middleware via definePageMeta', async () => {
    await import('../pages/checkout.vue')
    expect(mockDefinePageMeta).toHaveBeenCalledWith(
      expect.objectContaining({ middleware: 'auth' }),
    )
  })

  it('account/dashboard.vue registers auth middleware via definePageMeta', async () => {
    await import('../pages/account/dashboard.vue')
    expect(mockDefinePageMeta).toHaveBeenCalledWith(
      expect.objectContaining({ middleware: 'auth' }),
    )
  })

  it('account/orders.vue registers auth middleware via definePageMeta', async () => {
    await import('../pages/account/orders.vue')
    expect(mockDefinePageMeta).toHaveBeenCalledWith(
      expect.objectContaining({ middleware: 'auth' }),
    )
  })

  it('account/orders/[id].vue registers auth middleware via definePageMeta', async () => {
    await import('../pages/account/orders/[id].vue')
    expect(mockDefinePageMeta).toHaveBeenCalledWith(
      expect.objectContaining({ middleware: 'auth' }),
    )
  })

  // ── Public pages should NOT require auth middleware ────────────────────────

  it('login.vue does not apply auth middleware', async () => {
    await import('../pages/login.vue')
    const authCalls = mockDefinePageMeta.mock.calls.filter(
      (call) => call[0]?.middleware === 'auth',
    )
    expect(authCalls).toHaveLength(0)
  })

  it('register.vue does not apply auth middleware', async () => {
    await import('../pages/register.vue')
    const authCalls = mockDefinePageMeta.mock.calls.filter(
      (call) => call[0]?.middleware === 'auth',
    )
    expect(authCalls).toHaveLength(0)
  })
})

// ---------------------------------------------------------------------------
// Tests: Scroll behaviour in nuxt.config.ts
// ---------------------------------------------------------------------------

type ScrollBehaviorFn = (
  to: unknown,
  from: unknown,
  savedPosition: { top: number; left: number } | null,
) => { top: number; left: number }

describe('Nuxt router scroll behaviour', () => {
  it('nuxt.config.ts exports a router with scrollBehavior defined', async () => {
    let capturedConfig: Record<string, unknown> = {}
    vi.stubGlobal('defineNuxtConfig', (cfg: Record<string, unknown>) => {
      capturedConfig = cfg
      return cfg
    })
    vi.resetModules()

    await import('../nuxt.config')

    expect(capturedConfig).toHaveProperty('router')
    const router = capturedConfig.router as Record<string, unknown>
    expect(router).toHaveProperty('scrollBehavior')
    expect(typeof router.scrollBehavior).toBe('function')
  })

  it('scrollBehavior resets to top when no saved position exists', async () => {
    let capturedConfig: Record<string, unknown> = {}
    vi.stubGlobal('defineNuxtConfig', (cfg: Record<string, unknown>) => {
      capturedConfig = cfg
      return cfg
    })
    vi.resetModules()

    await import('../nuxt.config')

    const router = capturedConfig.router as Record<string, unknown>
    const scrollBehavior = router.scrollBehavior as ScrollBehaviorFn
    const result = scrollBehavior({}, {}, null)
    expect(result).toEqual({ top: 0, left: 0 })
  })

  it('scrollBehavior restores saved position when navigating back/forward', async () => {
    let capturedConfig: Record<string, unknown> = {}
    vi.stubGlobal('defineNuxtConfig', (cfg: Record<string, unknown>) => {
      capturedConfig = cfg
      return cfg
    })
    vi.resetModules()

    await import('../nuxt.config')

    const router = capturedConfig.router as Record<string, unknown>
    const scrollBehavior = router.scrollBehavior as ScrollBehaviorFn
    const saved = { top: 200, left: 0 }
    const result = scrollBehavior({}, {}, saved)
    expect(result).toEqual(saved)
  })
})
