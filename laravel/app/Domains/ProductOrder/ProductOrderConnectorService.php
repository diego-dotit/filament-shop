<?php

namespace App\Domains\ProductOrder;

use App\Domains\Cart\Models\Cart;
use App\Domains\Cart\Models\CartItem;
use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use App\Domains\Order\Models\OrderItem;
use App\Domains\ProductOrder\Exceptions\InactiveVariantException;
use App\Services\CurrencyService;
use Illuminate\Support\Collection;

class ProductOrderConnectorService
{
    public function __construct(private readonly CurrencyService $currencyService) {}

    /**
     * Build a collection of unsaved OrderItem models from a Cart's items.
     *
     * Iterates each CartItem, validates the variant is still active, captures
     * a point-in-time snapshot of product/variant data, and applies currency
     * conversion using the provided Currency. Language translation uses the
     * explicitly provided Language (or falls back to 'en').
     *
     * @param  Cart          $cart      The customer's cart (with items loaded or loadable)
     * @param  Currency      $currency  Target currency for price conversion
     * @param  Language|null $language  Language for product name translation
     * @return Collection<int, OrderItem>  Unsaved OrderItem instances ready to attach to an Order
     *
     * @throws InactiveVariantException  If any variant in the cart has been deactivated
     */
    public function buildOrderItemsFromCartItems(Cart $cart, Currency $currency, ?Language $language = null): Collection
    {
        $langCode = $language?->code ?? 'en';

        return $cart->items->map(function (CartItem $cartItem) use ($currency, $langCode): OrderItem {
            $variant = $cartItem->productVariant;

            if (! $variant->is_active) {
                throw new InactiveVariantException(
                    "Product variant [{$variant->id}] is no longer active and cannot be ordered."
                );
            }

            $product = $variant->product;

            // Use special_price when available, fall back to regular_price
            $basePrice = $variant->special_price ?? $variant->regular_price;
            $unitPrice = $this->currencyService->convertPrice($basePrice, $currency);

            $quantity  = $cartItem->quantity;
            $lineTotal = round((float) $unitPrice * $quantity, 2);

            return new OrderItem([
                'product_id'            => $variant->product_id,
                'product_variant_id'    => $variant->id,
                'quantity'              => $quantity,
                'product_name_snapshot' => $product->getTranslation('name', $langCode),
                'variant_sku_snapshot'  => $variant->sku,
                'unit_price_snapshot'   => $unitPrice,
                'line_total_snapshot'   => $lineTotal,
            ]);
        });
    }
}
