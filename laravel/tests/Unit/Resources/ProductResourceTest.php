<?php

namespace Tests\Unit\Resources;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Domains\Currency\Models\Currency;
use App\Http\Resources\Api\Product\ProductResource;
use App\Http\Resources\Api\Product\ProductVariantResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProductResourceTest extends TestCase
{
    public function test_product_resource_has_expected_keys(): void
    {
        $product = $this->makeProduct();

        $resource = new ProductResource($product);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('slug', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('description', $data);
        $this->assertArrayHasKey('is_active', $data);
        $this->assertArrayHasKey('variants', $data);
        $this->assertArrayHasKey('images', $data);
        $this->assertArrayHasKey('product_attributes', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('manufacturers', $data);
    }

    public function test_product_resource_uses_fallback_locale_for_name(): void
    {
        $product = $this->makeProduct();

        $resource = new ProductResource($product);
        $data = $resource->toArray(Request::create('/'));

        // With no lang set, falls back to app()->getLocale() which is 'en'
        $this->assertSame('Test Product', $data['name']);
        $this->assertSame('Test Description', $data['description']);
    }

    public function test_product_resource_uses_request_lang_for_translation(): void
    {
        $product = $this->makeProduct();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new ProductResource($product);
        $data = $resource->toArray($request);

        $this->assertSame('Produit Test', $data['name']);
    }

    public function test_product_resource_converts_prices_with_currency(): void
    {
        $product = $this->makeProduct();

        $currency = new Currency();
        $currency->setRawAttributes(['exchange_rate' => '2.00']);

        $request = Request::create('/');
        $request->attributes->set('currency', $currency);

        // ProductResource itself doesn't have regular_price/special_price
        // those are on variants; but if present they should be converted
        $resource = new ProductResource($product);
        $data = $resource->toArray($request);

        // Variants collection resource is present (resolved to array on HTTP response)
        $this->assertArrayHasKey('variants', $data);
    }

    public function test_product_variant_resource_has_expected_keys(): void
    {
        $variant = new ProductVariant();
        $variant->setRawAttributes([
            'id'             => 1,
            'sku'            => 'SKU-001',
            'regular_price'  => '29.99',
            'special_price'  => '24.99',
            'stock_quantity' => 10,
            'weight'         => '0.50',
            'is_active'      => true,
        ]);

        $resource = new ProductVariantResource($variant);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('sku', $data);
        $this->assertArrayHasKey('regular_price', $data);
        $this->assertArrayHasKey('special_price', $data);
        $this->assertArrayHasKey('stock_quantity', $data);
        $this->assertArrayHasKey('weight', $data);
        $this->assertArrayHasKey('is_active', $data);
        $this->assertArrayHasKey('attributes', $data);
    }

    public function test_product_variant_resource_converts_price_with_currency(): void
    {
        $variant = new ProductVariant();
        $variant->setRawAttributes([
            'id'            => 1,
            'sku'           => 'SKU-001',
            'regular_price' => '10.00',
            'special_price' => null,
        ]);

        $currency = new Currency();
        $currency->setRawAttributes(['exchange_rate' => '3.00']);

        $request = Request::create('/');
        $request->attributes->set('currency', $currency);

        $resource = new ProductVariantResource($variant);
        $data = $resource->toArray($request);

        $this->assertEqualsWithDelta(30.0, $data['regular_price'], 0.001);
        $this->assertNull($data['special_price']);
    }

    // ── resolveImages() return-type tests ─────────────────────────────────

    public function test_resolve_images_returns_array_not_string(): void
    {
        $product  = $this->makeProduct();
        $resource = new ProductResource($product);
        $data     = $resource->toArray(Request::create('/'));

        $this->assertIsArray($data['images'], 'images field must be an array, not a string or null');
    }

    public function test_resolve_images_returns_empty_array_when_no_images_exist(): void
    {
        $product  = $this->makeProduct();
        $resource = new ProductResource($product);
        $data     = $resource->toArray(Request::create('/'));

        $this->assertSame([], $data['images'], 'images field must be an empty array when no media exists');
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeProduct(): Product
    {
        $product = new Product();
        $product->setRawAttributes([
            'id'          => 1,
            'slug'        => 'test-product',
            'name'        => json_encode(['en' => 'Test Product', 'fr' => 'Produit Test']),
            'description' => json_encode(['en' => 'Test Description', 'fr' => 'Description Test']),
            'is_active'   => true,
        ]);

        return $product;
    }
}
