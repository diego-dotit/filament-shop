<?php

namespace App\Domains\OrderPlacement;

use App\Domains\Cart\Models\Cart;
use App\Domains\CartProduct\Exceptions\InsufficientStockException;
use App\Domains\Currency\Models\Currency;
use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use App\Domains\CustomerOrder\CustomerOrderConnectorService;
use App\Domains\Language\Models\Language;
use App\Domains\Order\Models\Order;
use App\Domains\OrderPlacement\Exceptions\EmptyCartException;
use App\Domains\ProductOrder\ProductOrderConnectorService;
use Illuminate\Support\Facades\DB;

class OrderPlacementService
{
    public function __construct(
        private readonly ProductOrderConnectorService  $productOrderConnector,
        private readonly CustomerOrderConnectorService $customerOrderConnector,
    ) {}

    /**
     * Place a new order for the given customer.
     *
     * Orchestrates the complete order placement workflow:
     * 1. Fetch cart (eager-load items with variant + product)
     * 2. Validate cart is non-empty
     * 3. Validate stock for all cart items
     * 4. Build unsaved OrderItem models (via ProductOrderConnectorService)
     * 5. Calculate order total from line totals
     * 6. Create and persist the Order record
     * 7. Attach customer + address snapshots (via CustomerOrderConnectorService)
     * 8. Save order items
     * 9. Clear the cart
     * 10. Return the order with all relationships loaded
     *
     * @throws EmptyCartException            if the customer has no cart or an empty cart
     * @throws \App\Domains\ProductOrder\Exceptions\InactiveVariantException
     * @throws \App\Domains\CartProduct\Exceptions\InsufficientStockException
     * @throws \App\Domains\CustomerOrder\Exceptions\UnauthorizedAddressException
     */
    public function placeOrder(
        Customer        $customer,
        CustomerAddress $billingAddress,
        CustomerAddress $shippingAddress,
        Currency        $currency,
    ): Order {
        // 1. Fetch cart — eager-load items with variant+product for downstream services
        $cart = Cart::where('customer_id', $customer->id)
            ->with('items.productVariant.product')
            ->first();

        // 2. Validate cart is non-empty
        if (! $cart || $cart->items->isEmpty()) {
            throw new EmptyCartException();
        }

        // Language is resolved here at the application-service boundary (HTTP context)
        /** @var Language|null $language */
        $language = request()->attributes->get('lang');

        return DB::transaction(function () use ($cart, $customer, $billingAddress, $shippingAddress, $currency, $language): Order {
            // 3. Validate stock for all cart items before proceeding
            foreach ($cart->items as $cartItem) {
                $variant = $cartItem->productVariant;
                if ($cartItem->quantity > $variant->stock_quantity) {
                    throw new InsufficientStockException(
                        $variant->sku,
                        $cartItem->quantity,
                        $variant->stock_quantity,
                    );
                }
            }

            // 4. Build unsaved OrderItem models (throws InactiveVariantException)
            $items = $this->productOrderConnector->buildOrderItemsFromCartItems($cart, $currency, $language);

            // 5. Calculate total from line totals
            $totalAmount = $items->sum(fn ($item) => (float) $item->line_total_snapshot);

            // 6. Create and persist the Order record
            $order = Order::create([
                'customer_id'   => $customer->id,
                'status'        => 'pending',
                'total_amount'  => round($totalAmount, 2),
                'currency_code' => $currency->code,
                'exchange_rate' => $currency->exchange_rate,
            ]);

            // 7. Attach customer + address snapshots (throws UnauthorizedAddressException)
            //    Order must be persisted (has ID) before this call
            $this->customerOrderConnector->attachCustomerAndAddressesToOrder(
                $order,
                $customer,
                $billingAddress,
                $shippingAddress,
            );

            // 8. Save order items
            $order->items()->saveMany($items);

            // 9. Clear the cart
            $cart->items()->delete();

            // 10. Return order with all relationships loaded
            return $order->load(['items', 'addresses', 'customer']);
        });
    }
}
