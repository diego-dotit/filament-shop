<?php

namespace App\Http\Controllers\Api\Product;

use App\Domains\Product\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Product\ProductResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /products
     *
     * Returns a paginated list of active products.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);

        $products = Product::where('is_active', true)
            ->with([
                'variants'         => fn ($q) => $q->where('is_active', true),
                'variants.attributes',
                'categories',
                'manufacturers',
                'productAttributes',
            ])
            ->paginate($perPage);

        $data = ProductResource::collection($products)->response()->getData(true);

        return ApiResponse::success($data['data'], extra: ['links' => $data['links'], 'meta' => $data['meta']]);
    }

    /**
     * GET /products/{slug}
     *
     * Returns a single active product by slug with full relations.
     */
    public function show(string $slug): ProductResource
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'variants'         => fn ($q) => $q->where('is_active', true),
                'variants.attributes',
                'categories',
                'manufacturers',
                'productAttributes',
            ])
            ->firstOrFail();

        return new ProductResource($product);
    }
}
