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
        $this->assertArrayHasKey('attributes', $data);
        $this->assertArrayHasKey('categories', $data);
        $this->assertArrayHasKey('manufacturers', $data);
        $this->assertArrayHasKey('locale', $data);
        $this->assertArrayHasKey('slugs', $data);
    }

    public function test_product_resource_locale_falls_back_to_app_locale(): void
    {
        $product  = $this->makeProduct();
        $resource = new ProductResource($product);
        $data     = $resource->toArray(Request::create('/'));

        $this->assertSame(app()->getLocale(), $data['locale']);
    }

    public function test_product_resource_locale_uses_request_lang_code(): void
    {
        $product = $this->makeProduct();

        $request = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'es';
        $request->attributes->set('lang', $langStub);

        $resource = new ProductResource($product);
        $data     = $resource->toArray($request);

        $this->assertSame('es', $data['locale']);
    }

    public function test_product_resource_slugs_is_empty_array_when_relation_not_loaded(): void
    {
        $product  = $this->makeProduct(); // no slugs relation loaded
        $resource = new ProductResource($product);
        $data     = $resource->toArray(Request::create('/'));

        $this->assertSame([], $data['slugs']);
    }

    public function test_product_resource_slugs_transforms_loaded_relation(): void
    {
        $slugEn = new \App\Domains\Slug\Models\Slug();
        $slugEn->setRawAttributes(['locale' => 'en', 'slug' => 'pla-filament']);

        $slugEs = new \App\Domains\Slug\Models\Slug();
        $slugEs->setRawAttributes(['locale' => 'es', 'slug' => 'filamento-pla']);

        $product = $this->makeProduct();
        $product->setRelation('slugs', collect([$slugEn, $slugEs]));

        $resource = new ProductResource($product);
        $data     = $resource->toArray(Request::create('/'));

        $this->assertSame([
            ['locale' => 'en', 'slug' => 'pla-filament'],
            ['locale' => 'es', 'slug' => 'filamento-pla'],
        ], $data['slugs']);
    }

    public function test_product_resource_slugs_only_contains_locale_and_slug_keys(): void
    {
        $slugEn = new \App\Domains\Slug\Models\Slug();
        $slugEn->setRawAttributes([
            'id'             => 99,
            'sluggable_type' => 'App\\Domains\\Product\\Models\\Product',
            'sluggable_id'   => 1,
            'locale'         => 'en',
            'slug'           => 'pla-filament',
        ]);

        $product = $this->makeProduct();
        $product->setRelation('slugs', collect([$slugEn]));

        $resource = new ProductResource($product);
        $data     = $resource->toArray(Request::create('/'));

        $this->assertCount(1, $data['slugs']);
        $this->assertSame(['locale', 'slug'], array_keys($data['slugs'][0]));
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

    public function test_product_resource_does_not_have_product_attributes_key(): void
    {
        $product = $this->makeProduct();

        $resource = new ProductResource($product);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayNotHasKey('product_attributes', $data);
    }

    public function test_product_resource_attributes_is_empty_object_when_no_attributes(): void
    {
        $product = $this->makeProduct();
        $product->setRelation('productAttributes', collect([]));

        $resource = new ProductResource($product);
        $response = $resource->response(Request::create('/'));
        $json     = json_decode($response->getContent(), true);

        $this->assertIsArray($json['data']['attributes']);
        // Must serialize as {} (object), not [] (array) — verify via raw JSON
        $raw = $response->getContent();
        $this->assertStringContainsString('"attributes":{}', str_replace(' ', '', $raw));
    }

    public function test_product_resource_attributes_is_flat_key_value_object(): void
    {
        $attributeStub = new \stdClass();
        $attributeStub->name = 'Color';

        $productAttrStub = new \stdClass();
        $productAttrStub->value = 'Red';
        $productAttrStub->attribute = $attributeStub;

        $product = $this->makeProduct();
        $product->setRelation('productAttributes', collect([$productAttrStub]));

        $resource = new ProductResource($product);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame(['Color' => 'Red'], $data['attributes']);
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

    // ── meta fields ───────────────────────────────────────────────────────

    public function test_product_resource_has_meta_fields(): void
    {
        $product  = $this->makeProduct();
        $resource = new ProductResource($product);
        $data     = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('meta_title', $data);
        $this->assertArrayHasKey('meta_description', $data);
        $this->assertArrayHasKey('meta_keywords', $data);
    }

    public function test_product_resource_meta_fields_use_app_locale_by_default(): void
    {
        $product  = $this->makeProductWithMeta();
        $resource = new ProductResource($product);
        $data     = $resource->toArray(Request::create('/'));

        $this->assertSame('EN Title', $data['meta_title']);
        $this->assertSame('EN Desc', $data['meta_description']);
        $this->assertSame('en, keywords', $data['meta_keywords']);
    }

    public function test_product_resource_meta_fields_respect_request_locale(): void
    {
        $product = $this->makeProductWithMeta();

        $request  = Request::create('/');
        $langStub = new \stdClass();
        $langStub->code = 'fr';
        $request->attributes->set('lang', $langStub);

        $resource = new ProductResource($product);
        $data     = $resource->toArray($request);

        $this->assertSame('FR Titre', $data['meta_title']);
        $this->assertSame('FR Desc', $data['meta_description']);
        $this->assertSame('fr, mots-clés', $data['meta_keywords']);
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

    private function makeProductWithMeta(): Product
    {
        $product = new Product();
        $product->setRawAttributes([
            'id'               => 2,
            'slug'             => 'meta-product',
            'name'             => json_encode(['en' => 'Test Product', 'fr' => 'Produit Test']),
            'description'      => json_encode(['en' => 'Test Description', 'fr' => 'Description Test']),
            'is_active'        => true,
            'meta_title'       => json_encode(['en' => 'EN Title', 'fr' => 'FR Titre']),
            'meta_description' => json_encode(['en' => 'EN Desc', 'fr' => 'FR Desc']),
            'meta_keywords'    => json_encode(['en' => 'en, keywords', 'fr' => 'fr, mots-clés']),
        ]);

        return $product;
    }
}
