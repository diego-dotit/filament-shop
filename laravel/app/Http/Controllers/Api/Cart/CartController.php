<?php

namespace App\Http\Controllers\Api\Cart;

use App\Domains\Cart\Models\CartItem;
use App\Domains\CartProduct\CartProductConnectorService;
use App\Domains\CartProduct\Exceptions\InactiveVariantException;
use App\Domains\CartProduct\Exceptions\InsufficientStockException;
use App\Domains\Product\Models\ProductVariant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Cart\StoreCartItemRequest;
use App\Http\Requests\Api\Cart\UpdateCartItemRequest;
use App\Http\Resources\Api\Cart\CartResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartController extends Controller
{
    public function __construct(
        private readonly CartProductConnectorService $cartProductConnector,
    ) {}

    // -----------------------------------------------------------------------
    // GET /cart
    // -----------------------------------------------------------------------

    public function show(Request $request): JsonResource|JsonResponse
    {
        $customer = $request->user();

        $cart = $customer->cart;

        if (! $cart) {
            return response()->json(['data' => ['items' => [], 'total' => '0.00']]);
        }

        $cart->load(['items.productVariant', 'items.product']);

        return new CartResource($cart);
    }

    // -----------------------------------------------------------------------
    // POST /cart/items
    // -----------------------------------------------------------------------

    public function addItem(StoreCartItemRequest $request): JsonResource|JsonResponse
    {
        $customer = $request->user();
        $variantId = (int) $request->validated('product_variant_id');
        $quantity = (int) $request->validated('quantity');

        /** @var ProductVariant $variant */
        $variant = ProductVariant::find($variantId);

        try {
            $cart = $this->cartProductConnector->addProductVariantToCart($customer, $variant, $quantity);
        } catch (InsufficientStockException) {
            return ApiResponse::validationError(
                'Insufficient stock for this variant',
                ['quantity' => ['Insufficient stock for this variant']],
            );
        } catch (InactiveVariantException) {
            return ApiResponse::validationError(
                'This variant is no longer available',
                ['product_variant_id' => ['This variant is no longer available']],
            );
        }

        $cart->load(['items.productVariant', 'items.product']);

        return new CartResource($cart);
    }

    // -----------------------------------------------------------------------
    // PUT /cart/items/{cartItemId}
    // -----------------------------------------------------------------------

    public function updateItem(UpdateCartItemRequest $request, int $cartItemId): JsonResource|JsonResponse
    {
        $cartItem = CartItem::with('cart')->findOrFail($cartItemId);
        $customer = $request->user();

        if ($cartItem->cart->customer_id !== $customer->id) {
            return ApiResponse::error('forbidden', 'Forbidden.', 403);
        }

        $quantity = (int) $request->validated('quantity');

        if ($quantity === 0) {
            $cart = $cartItem->cart;
            $cartItem->delete();
            $cart->load(['items.productVariant', 'items.product']);

            return new CartResource($cart);
        }

        $cartItem->update(['quantity' => $quantity]);

        $cart = $cartItem->cart;
        $cart->load(['items.productVariant', 'items.product']);

        return new CartResource($cart);
    }

    // -----------------------------------------------------------------------
    // DELETE /cart/items/{cartItemId}
    // -----------------------------------------------------------------------

    public function removeItem(Request $request, int $cartItemId): JsonResource|JsonResponse
    {
        $cartItem = CartItem::with('cart')->findOrFail($cartItemId);
        $customer = $request->user();

        if ($cartItem->cart->customer_id !== $customer->id) {
            return ApiResponse::error('forbidden', 'Forbidden.', 403);
        }

        $cart = $cartItem->cart;
        $cartItem->delete();

        $cart->load(['items.productVariant', 'items.product']);

        return new CartResource($cart);
    }
}
