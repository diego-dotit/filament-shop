// composables/useCheckout.ts
// Manages checkout flow: address fetching, address selection, order submission,
// confirmation display, and cart clearing after a successful order.
//
// Usage:
//   const {
//     addresses, billingAddressId, shippingAddressId,
//     fetchAddresses, selectBillingAddress, selectShippingAddress,
//     submitOrder, orderConfirmation, error, isSubmitting,
//   } = useCheckout()

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface CustomerAddress {
  id: number
  country: string
  city: string
  address_line_1: string
  address_line_2?: string
  postcode: string
}

export interface OrderConfirmation {
  id: number
  total_amount: string
  status: string
  created_at: string
}

interface AddressesResponse {
  data: CustomerAddress[]
}

interface OrderResponse {
  data: OrderConfirmation
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

export function useCheckout() {
  const api = useApi()

  // ── Reactive state ─────────────────────────────────────────────────────────

  const addresses = useState<CustomerAddress[]>('checkout.addresses', () => [])
  const billingAddressId = useState<number | null>('checkout.billingAddressId', () => null)
  const shippingAddressId = useState<number | null>('checkout.shippingAddressId', () => null)
  const orderConfirmation = useState<OrderConfirmation | null>('checkout.orderConfirmation', () => null)
  const error = useState<string | null>('checkout.error', () => null)
  const isSubmitting = useState<boolean>('checkout.isSubmitting', () => false)

  // ── Actions ────────────────────────────────────────────────────────────────

  /**
   * Fetch the authenticated customer's saved addresses.
   */
  async function fetchAddresses(): Promise<void> {
    const response = await api<AddressesResponse>('/customers/me/addresses')
    addresses.value = response.data
  }

  /**
   * Set the selected billing address ID.
   */
  function selectBillingAddress(id: number): void {
    billingAddressId.value = id
  }

  /**
   * Set the selected shipping address ID.
   */
  function selectShippingAddress(id: number): void {
    shippingAddressId.value = id
  }

  /**
   * Submit the order with the selected billing and shipping address IDs.
   * Clears the cart on success; sets an error message on failure.
   */
  async function submitOrder(): Promise<void> {
    error.value = null
    isSubmitting.value = true

    try {
      const response = await api<OrderResponse>('/orders', {
        method: 'POST',
        body: {
          billing_address_id: billingAddressId.value,
          shipping_address_id: shippingAddressId.value,
        },
      })

      orderConfirmation.value = response.data

      // Clear the cart after a successful order placement
      const { clearCart } = useCart()
      clearCart()
    } catch (err: unknown) {
      const apiError = err as {
        data?: { message?: string }
        message?: string
      }

      error.value =
        apiError?.data?.message ??
        apiError?.message ??
        'An unexpected error occurred. Please try again.'

      orderConfirmation.value = null
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    addresses,
    billingAddressId,
    shippingAddressId,
    orderConfirmation,
    error,
    isSubmitting,
    fetchAddresses,
    selectBillingAddress,
    selectShippingAddress,
    submitOrder,
  }
}
