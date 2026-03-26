<?php

namespace App\Domains\CartProduct;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\CartProduct\Exceptions\InactiveVariantException;
use App\Domains\CartProduct\Exceptions\InsufficientStockException;
use App\Domains\Customer\Models\Customer;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class CartProductConnectorService
{
    /**
     * Add a product variant to the customer's cart, creating the cart if needed
     * and merging quantities if the variant is already present.
     *
     * Stock is validated but NOT decremented here — decrement happens at order placement.
     *
     * @throws InactiveVariantException    if the variant is not active
     * @throws InsufficientStockException  if requested quantity exceeds available stock
     */
    public function addProductVariantToCart(
        Customer $customer,
        ProductVariant $productVariant,
        int $quantity,
    ): Cart {
        if (! $productVariant->is_active) {
            throw new InactiveVariantException($productVariant->sku);
        }

        return DB::transaction(function () use ($customer, $productVariant, $quantity): Cart {
            // Get or create the customer's active cart
            $cart = Cart::firstOrCreate(['customer_id' => $customer->id]);

            // Check if this variant is already in the cart
            /** @var CartItem|null $existingItem */
            $existingItem     = CartItem::where('cart_id', $cart->id)
                ->where('product_variant_id', $productVariant->id)
                ->first();

            $existingQuantity = $existingItem ? $existingItem->quantity : 0;
            $totalQuantity    = $existingQuantity + $quantity;

            // Validate stock — combined quantity must not exceed available stock
            if ($totalQuantity > $productVariant->stock_quantity) {
                throw new InsufficientStockException(
                    $productVariant->sku,
                    $totalQuantity,
                    $productVariant->stock_quantity,
                );
            }

            // Merge or create cart item
            if ($existingItem) {
                $existingItem->update(['quantity' => $totalQuantity]);
            } else {
                $cart->items()->create([
                    'product_id'         => $productVariant->product_id,
                    'product_variant_id' => $productVariant->id,
                    'quantity'           => $quantity,
                ]);
            }

            return $cart->load('items');
        });
    }
}
