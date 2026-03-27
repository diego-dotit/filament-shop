// composables/useOrders.ts
// Fetches the authenticated customer's orders from the API.
//
// Usage:
//   const { orders, currentOrder, loading, error, fetchOrders, fetchOrder } = useOrders()

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface OrderAddress {
    name: string;
    line1: string;
    city: string;
    country: string;
    [key: string]: unknown;
}

export interface OrderItem {
    id: number;
    product_name: string;
    variant_name: string;
    quantity: number;
    price: string;
    line_total: string;
    [key: string]: unknown;
}

export interface Order {
    id: number;
    status: string;
    total_amount: string;
    subtotal?: string;
    created_at: string;
    items: OrderItem[];
    billing_address: OrderAddress;
    shipping_address: OrderAddress;
    [key: string]: unknown;
}

export interface OrderSummary {
    id: number;
    status: string;
    total_amount: string;
    created_at: string;
    [key: string]: unknown;
}

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

export function useOrders() {
    const orders = useState<OrderSummary[]>("orders.list", () => []);
    const currentOrder = useState<Order | null>("orders.current", () => null);
    const loading = useState<boolean>("orders.loading", () => false);
    const error = useState<string | null>("orders.error", () => null);

    const api = useApi();

    /**
     * Fetch the authenticated customer's order list.
     * Calls GET /customers/me/orders.
     */
    async function fetchOrders(): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api<{ data: OrderSummary[] }>("/orders");
            orders.value = response.data;
        } catch {
            error.value = "Failed to load orders";
        } finally {
            loading.value = false;
        }
    }

    /**
     * Fetch a single order by ID.
     * Calls GET /customers/me/orders/{id}.
     */
    async function fetchOrder(id: number | string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api<{ data: Order }>(`/orders/${id}`);
            currentOrder.value = response.data;
        } catch {
            error.value = "Failed to load order";
        } finally {
            loading.value = false;
        }
    }

    return {
        orders,
        currentOrder,
        loading,
        error,
        fetchOrders,
        fetchOrder,
    };
}
