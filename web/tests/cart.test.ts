import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref, computed } from 'vue'

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any module under test is imported.
// ---------------------------------------------------------------------------

const mockFetch = vi.fn()

vi.stubGlobal(
  '$fetch',
  Object.assign(mockFetch, { create: vi.fn(() => mockFetch) }),
)

vi.stubGlobal('defineNuxtPlugin', (fn: (app: unknown) => unknown) => fn({}))

vi.stubGlobal('useRuntimeConfig', () => ({
  public: { apiBaseUrl: 'http://localhost:8000' },
}))

// useNuxtApp throws → useApi falls back to $fetch
vi.stubGlobal('useNuxtApp', () => {
  throw new Error('outside Nuxt context — using $fetch fallback')
})

// Stub Vue's computed with the real implementation so computed props work
vi.stubGlobal('computed', computed)

// useApi: return a wrapper around $fetch so the composable can make requests
vi.stubGlobal('useApi', () => mockFetch)

// useState: simulate Nuxt's shared state via a single ref per key
const stateStore: Record<string, ReturnType<typeof ref>> = {}
vi.stubGlobal('useState', <T>(key: string, init: () => T) => {
  if (!stateStore[key]) {
    stateStore[key] = ref<T>(init())
  }
  return stateStore[key]
})

// ---------------------------------------------------------------------------
// Test helpers / fixtures
// ---------------------------------------------------------------------------

const makeCartItem = (id: number, quantity: number) => ({
  id,
  product: { id: 1, name: 'PLA Filament', slug: 'pla' },
  variant: { id: id * 10, sku: `SKU-${id}` },
  quantity,
  line_total: quantity * 19.99,
})

const makeCart = (items: ReturnType<typeof makeCartItem>[] = []) => ({
  id: 'cart-uuid-1',
  items,
  total: items.reduce((sum, i) => sum + i.line_total, 0),
})

// ---------------------------------------------------------------------------
// Tests for useCart composable
// ---------------------------------------------------------------------------

describe('useCart composable', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.clear()

    // Reset shared state between tests
    for (const key of Object.keys(stateStore)) {
      delete stateStore[key]
    }

    vi.resetModules()
  })

  // ── fetchCart ──────────────────────────────────────────────────────────────

  it('fetchCart: populates cart state from GET /cart', async () => {
    const cartData = makeCart([makeCartItem(1, 2)])
    mockFetch.mockResolvedValueOnce({ data: cartData })

    const { useCart } = await import('../composables/useCart')
    const { cart, fetchCart } = useCart()

    await fetchCart()

    expect(mockFetch).toHaveBeenCalledWith('/cart')
    expect(cart.value).toEqual(cartData)
  })

  it('fetchCart: cart starts as null before first fetch', async () => {
    const { useCart } = await import('../composables/useCart')
    const { cart } = useCart()

    expect(cart.value).toBeNull()
  })

  // ── addItem ────────────────────────────────────────────────────────────────

  it('addItem: calls POST /cart/items with productVariantId and quantity', async () => {
    const updatedCart = makeCart([makeCartItem(1, 3)])
    mockFetch.mockResolvedValueOnce({ data: updatedCart })

    const { useCart } = await import('../composables/useCart')
    const { addItem } = useCart()

    await addItem(42, 3)

    expect(mockFetch).toHaveBeenCalledWith(
      '/cart/items',
      expect.objectContaining({
        method: 'POST',
        body: { product_variant_id: 42, quantity: 3 },
      }),
    )
  })

  it('addItem: updates cart state with response data', async () => {
    const updatedCart = makeCart([makeCartItem(1, 3)])
    mockFetch.mockResolvedValueOnce({ data: updatedCart })

    const { useCart } = await import('../composables/useCart')
    const { cart, addItem } = useCart()

    await addItem(42, 3)

    expect(cart.value).toEqual(updatedCart)
  })

  // ── updateItemQuantity ─────────────────────────────────────────────────────

  it('updateItemQuantity: calls PUT /cart/items/{id} with new quantity', async () => {
    const cartWithItem = makeCart([makeCartItem(5, 1)])
    const updatedItem = { ...makeCartItem(5, 4), line_total: 4 * 19.99 }
    const updatedCart = { ...cartWithItem, items: [updatedItem] }

    // First set up initial cart state
    mockFetch.mockResolvedValueOnce({ data: cartWithItem })
    const { useCart } = await import('../composables/useCart')
    const { cart, fetchCart, updateItemQuantity } = useCart()
    await fetchCart()

    // Now update quantity
    mockFetch.mockResolvedValueOnce({ data: updatedItem })
    await updateItemQuantity(5, 4)

    expect(mockFetch).toHaveBeenLastCalledWith(
      '/cart/items/5',
      expect.objectContaining({
        method: 'PUT',
        body: { quantity: 4 },
      }),
    )
  })

  it('updateItemQuantity: patches the updated item in cart items array', async () => {
    const item1 = makeCartItem(5, 1)
    const item2 = makeCartItem(6, 2)
    const cartWithItems = makeCart([item1, item2])

    mockFetch.mockResolvedValueOnce({ data: cartWithItems })
    const { useCart } = await import('../composables/useCart')
    const { cart, fetchCart, updateItemQuantity } = useCart()
    await fetchCart()

    const updatedItem = { ...item1, quantity: 10, line_total: 199.9 }
    mockFetch.mockResolvedValueOnce({ data: updatedItem })
    await updateItemQuantity(5, 10)

    const updatedItems = cart.value?.items ?? []
    const found = updatedItems.find((i) => i.id === 5)
    expect(found?.quantity).toBe(10)
    // Other items are untouched
    expect(updatedItems.find((i) => i.id === 6)?.quantity).toBe(2)
  })

  // ── removeItem ─────────────────────────────────────────────────────────────

  it('removeItem: calls DELETE /cart/items/{id}', async () => {
    const cartWithItem = makeCart([makeCartItem(7, 1)])
    mockFetch.mockResolvedValueOnce({ data: cartWithItem })

    const { useCart } = await import('../composables/useCart')
    const { fetchCart, removeItem } = useCart()
    await fetchCart()

    mockFetch.mockResolvedValueOnce(null) // 204 No Content
    await removeItem(7)

    expect(mockFetch).toHaveBeenLastCalledWith(
      '/cart/items/7',
      expect.objectContaining({ method: 'DELETE' }),
    )
  })

  it('removeItem: removes the item from local cart state', async () => {
    const item1 = makeCartItem(7, 1)
    const item2 = makeCartItem(8, 2)
    const cartWithItems = makeCart([item1, item2])

    mockFetch.mockResolvedValueOnce({ data: cartWithItems })
    const { useCart } = await import('../composables/useCart')
    const { cart, fetchCart, removeItem } = useCart()
    await fetchCart()

    mockFetch.mockResolvedValueOnce(null)
    await removeItem(7)

    expect(cart.value?.items.find((i) => i.id === 7)).toBeUndefined()
    expect(cart.value?.items.find((i) => i.id === 8)).toBeDefined()
  })

  // ── clearCart ──────────────────────────────────────────────────────────────

  it('clearCart: sets cart to null', async () => {
    const cartData = makeCart([makeCartItem(1, 2)])
    mockFetch.mockResolvedValueOnce({ data: cartData })

    const { useCart } = await import('../composables/useCart')
    const { cart, fetchCart, clearCart } = useCart()

    await fetchCart()
    expect(cart.value).not.toBeNull()

    clearCart()
    expect(cart.value).toBeNull()
  })

  // ── items computed ─────────────────────────────────────────────────────────

  it('items: returns empty array when cart is null', async () => {
    const { useCart } = await import('../composables/useCart')
    const { items } = useCart()

    expect(items.value).toEqual([])
  })

  it('items: returns cart items array when cart is set', async () => {
    const cartItems = [makeCartItem(1, 2), makeCartItem(2, 1)]
    const cartData = makeCart(cartItems)
    mockFetch.mockResolvedValueOnce({ data: cartData })

    const { useCart } = await import('../composables/useCart')
    const { items, fetchCart } = useCart()

    await fetchCart()

    expect(items.value).toEqual(cartItems)
  })

  // ── itemCount computed ─────────────────────────────────────────────────────

  it('itemCount: returns 0 when cart is null', async () => {
    const { useCart } = await import('../composables/useCart')
    const { itemCount } = useCart()

    expect(itemCount.value).toBe(0)
  })

  it('itemCount: sums all item quantities', async () => {
    const cartData = makeCart([makeCartItem(1, 3), makeCartItem(2, 2), makeCartItem(3, 5)])
    mockFetch.mockResolvedValueOnce({ data: cartData })

    const { useCart } = await import('../composables/useCart')
    const { itemCount, fetchCart } = useCart()

    await fetchCart()

    expect(itemCount.value).toBe(10) // 3 + 2 + 5
  })

  it('itemCount: updates reactively after addItem', async () => {
    const { useCart } = await import('../composables/useCart')
    const { itemCount, addItem } = useCart()

    expect(itemCount.value).toBe(0)

    const updatedCart = makeCart([makeCartItem(1, 4)])
    mockFetch.mockResolvedValueOnce({ data: updatedCart })
    await addItem(10, 4)

    expect(itemCount.value).toBe(4)
  })
})
