<?php

namespace App\Http\Controllers\Api\Product;

use App\Domains\Product\Models\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Product\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * GET /products
     *
     * Returns a paginated list of active products.
     */
    public function index(Request $request): AnonymousResourceCollection
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

        return ProductResource::collection($products);
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
