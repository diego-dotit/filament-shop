<?php

namespace App\Http\Controllers\Api\Review;

use App\Domains\Product\Models\Product;
use App\Domains\Review\Models\Review;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Review\StoreReviewRequest;
use App\Http\Resources\Api\Review\ReviewResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReviewController extends Controller
{
    /**
     * GET /products/{productId}/reviews
     *
     * Returns a paginated list of approved reviews for the given product.
     * Public endpoint — no authentication required.
     */
    public function index(Request $request, int $productId): AnonymousResourceCollection
    {
        $product = Product::findOrFail($productId);

        $perPage = (int) $request->input('per_page', 15);

        $reviews = Review::where('product_id', $product->id)
            ->where('status', 'approved')
            ->with('customer')
            ->latest()
            ->paginate($perPage);

        return ReviewResource::collection($reviews);
    }

    /**
     * POST /products/{productId}/reviews
     *
     * Submits a new review for the given product by the authenticated customer.
     * The product_id is injected from the route into the request before validation.
     */
    public function store(StoreReviewRequest $request, int $productId): \Illuminate\Http\JsonResponse
    {
        $customer = auth()->user()->customer;

        try {
            $review = Review::create([
                'product_id'  => $productId,
                'customer_id' => $customer->id,
                'rating'      => $request->validated()['rating'],
                'comment'     => $request->validated()['comment'] ?? null,
                'status'      => 'pending',
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'product_id' => ['You have already reviewed this product.'],
                ],
            ], 422);
        }

        $review->load('customer');

        return ApiResponse::success(new ReviewResource($review), 201);
    }
}
