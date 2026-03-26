<?php

namespace App\Http\Controllers\Api\Order;

use App\Domains\CartProduct\Exceptions\InsufficientStockException;
use App\Domains\Customer\Models\CustomerAddress;
use App\Domains\CustomerOrder\Exceptions\UnauthorizedAddressException;
use App\Domains\Order\Models\Order;
use App\Domains\OrderPlacement\Exceptions\EmptyCartException;
use App\Domains\ProductOrder\Exceptions\InactiveVariantException;
use App\Domains\OrderPlacement\OrderPlacementService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Order\PlaceOrderRequest;
use App\Http\Resources\Api\Order\OrderResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderPlacementService $orderPlacementService,
    ) {}

    /**
     * POST /orders
     * Place a new order from the authenticated customer's cart.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $billingAddress  = CustomerAddress::findOrFail($request->billing_address_id);
        $shippingAddress = CustomerAddress::findOrFail($request->shipping_address_id);

        // Currency is resolved by ResolveLanguageAndCurrency middleware
        $currency = $request->attributes->get('currency');

        try {
            $order = $this->orderPlacementService->placeOrder(
                $customer,
                $billingAddress,
                $shippingAddress,
                $currency,
            );
        } catch (EmptyCartException) {
            return ApiResponse::validationError(
                'Your cart is empty; cannot place order',
                ['cart' => ['Your cart is empty; cannot place order']],
            );
        } catch (InsufficientStockException $e) {
            return ApiResponse::validationError(
                $e->getMessage(),
                ['stock' => [$e->getMessage()]],
            );
        } catch (UnauthorizedAddressException) {
            return ApiResponse::validationError(
                'Invalid address selection',
                ['address' => ['Invalid address selection']],
            );
        } catch (InactiveVariantException) {
            return ApiResponse::validationError(
                'One or more items in your cart are no longer available',
                ['cart' => ['One or more items in your cart are no longer available']],
            );
        }

        return ApiResponse::success((new OrderResource($order))->toArray($request), 201);
    }

    /**
     * GET /orders
     * Return paginated list of the authenticated customer's orders.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        $orders   = $customer->orders()
            ->orderByDesc('created_at')
            ->paginate();

        return response()->json(
            OrderResource::collection($orders)->response()->getData(true)
        );
    }

    /**
     * GET /orders/{id}
     * Return a single order belonging to the authenticated customer.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $customer = $request->user()->customer;

        if ($order->customer_id !== $customer->id) {
            abort(403, 'You do not have access to this order.');
        }

        $order->load(['items', 'addresses']);

        return ApiResponse::success((new OrderResource($order))->toArray($request));
    }
}
