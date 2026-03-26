// composables/useCart.ts
// Manages shopping cart state: items, totals, and CRUD operations.
// Authenticated users use the API; guests use a localStorage-backed cart.
// Uses useState for shared state that persists across page navigation.
//
// Usage:
//   const { cart, items, itemCount, fetchCart, addItem, updateItemQuantity, removeItem, clearCart } = useCart()

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface CartProduct {
  id: number
  name: string
  slug: string
}

export interface CartVariant {
  id: number
  sku: string
  regular_price?: string
  special_price?: string
}

export interface CartItem {
  id: number
  product: CartProduct
  variant: CartVariant
  quantity: number
  line_total: number
}

export interface CartData {
  id: string
  items: CartItem[]
  total: number
}

interface CartResponse {
  data: CartData
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------

const GUEST_CART_KEY = 'guest_cart'

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

export function useCart() {
  // Shared reactive state across all composable instances (Nuxt useState)
  const cart = useState<CartData | null>('cart.data', () => null)
  const { isAuthenticated } = useAuth()

  // ── Computed shortcuts ─────────────────────────────────────────────────────

  const items = computed<CartItem[]>(() => cart.value?.items ?? [])

  const itemCount = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0),
  )

  // ── API helpers ────────────────────────────────────────────────────────────

  const api = useApi()

  // ── Guest cart helpers ─────────────────────────────────────────────────────

  function _loadGuestCart(): void {
    if (typeof window === 'undefined') return
    const stored = localStorage.getItem(GUEST_CART_KEY)
    if (stored) {
      try {
        cart.value = JSON.parse(stored)
      } catch {
        localStorage.removeItem(GUEST_CART_KEY)
      }
    } else {
      cart.value = { id: 'guest', items: [], total: 0 }
    }
  }

  function _saveGuestCart(): void {
    if (typeof window === 'undefined') return
    if (cart.value) {
      localStorage.setItem(GUEST_CART_KEY, JSON.stringify(cart.value))
    }
  }

  // ── Actions ────────────────────────────────────────────────────────────────

  /**
   * Fetch the current cart from the API (authenticated) or localStorage (guest).
   */
  async function fetchCart(): Promise<void> {
    if (!isAuthenticated.value) {
      _loadGuestCart()
      return
    }
    const response = await api<CartResponse>('/cart')
    cart.value = response.data
  }

  /**
   * Add a product variant to the cart.
   * Authenticated: POST to API. Guest: update localStorage cart.
   */
  async function addItem(
    productVariantId: number,
    quantity: number,
    productInfo?: { product: CartProduct; variant: CartVariant; price: number },
  ): Promise<void> {
    if (!isAuthenticated.value) {
      if (!cart.value) {
        cart.value = { id: 'guest', items: [], total: 0 }
      }
      const existing = cart.value.items.find((i) => i.variant.id === productVariantId)
      if (existing) {
        const unitPrice = existing.quantity > 0 ? existing.line_total / existing.quantity : 0
        existing.quantity += quantity
        existing.line_total = existing.quantity * unitPrice
      } else if (productInfo) {
        const newItem: CartItem = {
          id: Date.now(),
          product: productInfo.product,
          variant: productInfo.variant,
          quantity,
          line_total: productInfo.price * quantity,
        }
        cart.value.items.push(newItem)
      }
      cart.value.total = cart.value.items.reduce((sum, i) => sum + i.line_total, 0)
      _saveGuestCart()
      return
    }
    const response = await api<CartResponse>('/cart/items', {
      method: 'POST',
      body: { product_variant_id: productVariantId, quantity },
    })
    cart.value = response.data
  }

  /**
   * Update the quantity of an existing cart item.
   * The API returns a full CartResource on PUT, so we replace the whole cart.
   */
  async function updateItemQuantity(cartItemId: number, quantity: number): Promise<void> {
    if (!isAuthenticated.value) {
      if (cart.value) {
        const item = cart.value.items.find((i) => i.id === cartItemId)
        if (item) {
          const unitPrice = item.quantity > 0 ? item.line_total / item.quantity : 0
          item.quantity = quantity
          item.line_total = unitPrice * quantity
        }
        cart.value.total = cart.value.items.reduce((sum, i) => sum + i.line_total, 0)
        _saveGuestCart()
      }
      return
    }
    const response = await api<CartResponse>(`/cart/items/${cartItemId}`, {
      method: 'PUT',
      body: { quantity },
    })
    cart.value = response.data
  }

  /**
   * Remove an item from the cart.
   * Filters out the deleted item from local state.
   */
  async function removeItem(cartItemId: number): Promise<void> {
    if (!isAuthenticated.value) {
      if (cart.value) {
        cart.value.items = cart.value.items.filter((i) => i.id !== cartItemId)
        cart.value.total = cart.value.items.reduce((sum, i) => sum + i.line_total, 0)
        _saveGuestCart()
      }
      return
    }
    await api(`/cart/items/${cartItemId}`, { method: 'DELETE' })

    if (cart.value) {
      cart.value = {
        ...cart.value,
        items: cart.value.items.filter((item) => item.id !== cartItemId),
      }
    }
  }

  /**
   * Clear cart state locally (e.g. after logout or a successful order placement).
   * Does NOT call the API.
   */
  function clearCart(): void {
    cart.value = null
    if (typeof window !== 'undefined') {
      localStorage.removeItem(GUEST_CART_KEY)
    }
  }

  return {
    cart,
    items,
    itemCount,
    fetchCart,
    addItem,
    updateItemQuantity,
    removeItem,
    clearCart,
  }
}
