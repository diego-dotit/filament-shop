// composables/useCart.ts
// Manages shopping cart state: items, totals, and CRUD operations.
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

interface CartItemResponse {
  data: CartItem
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

export function useCart() {
  // Shared reactive state across all composable instances (Nuxt useState)
  const cart = useState<CartData | null>('cart.data', () => null)

  // ── Computed shortcuts ─────────────────────────────────────────────────────

  const items = computed<CartItem[]>(() => cart.value?.items ?? [])

  const itemCount = computed<number>(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0),
  )

  // ── API helpers ────────────────────────────────────────────────────────────

  const api = useApi()

  // ── Actions ────────────────────────────────────────────────────────────────

  /**
   * Fetch the current cart from the API and hydrate local state.
   */
  async function fetchCart(): Promise<void> {
    const response = await api<CartResponse>('/cart')
    cart.value = response.data
  }

  /**
   * Add a product variant to the cart.
   */
  async function addItem(productVariantId: number, quantity: number): Promise<void> {
    const response = await api<CartResponse>('/cart/items', {
      method: 'POST',
      body: { product_variant_id: productVariantId, quantity },
    })
    cart.value = response.data
  }

  /**
   * Update the quantity of an existing cart item.
   * Patches only the changed item in local state to avoid a full re-fetch.
   */
  async function updateItemQuantity(cartItemId: number, quantity: number): Promise<void> {
    const response = await api<CartItemResponse>(`/cart/items/${cartItemId}`, {
      method: 'PUT',
      body: { quantity },
    })

    if (cart.value) {
      cart.value = {
        ...cart.value,
        items: cart.value.items.map((item) =>
          item.id === cartItemId ? response.data : item,
        ),
      }
    }
  }

  /**
   * Remove an item from the cart.
   * Filters out the deleted item from local state.
   */
  async function removeItem(cartItemId: number): Promise<void> {
    await api(`/cart/items/${cartItemId}`, { method: 'DELETE' })

    if (cart.value) {
      cart.value = {
        ...cart.value,
        items: cart.value.items.filter((item) => item.id !== cartItemId),
      }
    }
  }

  /**
   * Clear cart state locally (e.g. after a successful order placement).
   * Does NOT call the API.
   */
  function clearCart(): void {
    cart.value = null
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
