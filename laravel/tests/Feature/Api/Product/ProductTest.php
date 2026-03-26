<?php

namespace Tests\Feature\Api\Product;

use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear default Accept-Language header injected by Symfony test client
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createLanguage(string $code, bool $isDefault = false): Language
    {
        return Language::create([
            'code'       => $code,
            'name'       => strtoupper($code),
            'is_default' => $isDefault,
        ]);
    }

    private function createCurrency(string $code, float $rate = 1.0, bool $isBase = false): Currency
    {
        return Currency::create([
            'code'          => $code,
            'name'          => $code,
            'symbol'        => $code,
            'exchange_rate' => $rate,
            'is_base'       => $isBase,
        ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name'        => ['en' => 'Test Product', 'fr' => 'Produit Test'],
            'slug'        => 'test-product-' . uniqid(),
            'description' => ['en' => 'A description', 'fr' => 'Une description'],
            'is_active'   => true,
        ], $overrides));
    }

    private function createVariant(Product $product, array $overrides = []): ProductVariant
    {
        return $product->variants()->create(array_merge([
            'sku'            => 'SKU-' . uniqid(),
            'regular_price'  => '10.00',
            'special_price'  => null,
            'stock_quantity' => 5,
            'weight'         => 1.0,
            'is_active'      => true,
        ], $overrides));
    }

    // ── GET /products — Listing ───────────────────────────────────────────────

    public function test_product_listing_returns_paginated_active_products(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        $this->createProduct(['is_active' => true]);
        $this->createProduct(['is_active' => true]);
        $this->createProduct(['is_active' => false]); // should be hidden

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'slug', 'name', 'description', 'is_active', 'variants', 'categories', 'manufacturers']],
                'links',
                'meta',
            ]);
    }

    public function test_product_listing_hides_inactive_products(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        $this->createProduct(['is_active' => true]);
        $this->createProduct(['is_active' => false]);
        $this->createProduct(['is_active' => false]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_product_listing_defaults_to_15_per_page(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        for ($i = 0; $i < 20; $i++) {
            $this->createProduct();
        }

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 20);
    }

    public function test_product_listing_accepts_per_page_param(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        for ($i = 0; $i < 10; $i++) {
            $this->createProduct();
        }

        $response = $this->getJson('/api/products?per_page=5');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);
    }

    public function test_product_listing_accepts_page_param(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        for ($i = 0; $i < 10; $i++) {
            $this->createProduct();
        }

        $response = $this->getJson('/api/products?per_page=5&page=2');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }

    // ── GET /products/{slug} — Detail ────────────────────────────────────────

    public function test_product_detail_returns_product_by_slug(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        $product = $this->createProduct(['slug' => 'my-product']);

        $response = $this->getJson('/api/products/my-product');

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'my-product')
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_product_detail_returns_404_for_invalid_slug(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        $response = $this->getJson('/api/products/non-existent-slug');

        $response->assertStatus(404);
    }

    public function test_product_detail_returns_404_for_inactive_product(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        $this->createProduct(['slug' => 'inactive-product', 'is_active' => false]);

        $response = $this->getJson('/api/products/inactive-product');

        $response->assertStatus(404);
    }

    public function test_product_detail_includes_variants_with_attributes(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        $product = $this->createProduct(['slug' => 'product-with-variants']);
        $this->createVariant($product, ['sku' => 'VAR-001']);
        $this->createVariant($product, ['sku' => 'VAR-002', 'is_active' => false]);

        $response = $this->getJson('/api/products/product-with-variants');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'variants' => [
                        ['id', 'sku', 'regular_price', 'special_price', 'stock_quantity', 'is_active', 'attributes'],
                    ],
                ],
            ]);

        // Only active variants should be included
        $variants = $response->json('data.variants');
        $this->assertCount(1, $variants);
        $this->assertEquals('VAR-001', $variants[0]['sku']);
    }

    // ── Accept-Language header ────────────────────────────────────────────────

    public function test_product_listing_respects_accept_language_header(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);
        $this->createCurrency('USD', 1.0, true);

        $this->createProduct([
            'name' => ['en' => 'English Name', 'fr' => 'Nom Français'],
            'slug' => 'lang-test-product',
        ]);

        $response = $this->getJson('/api/products', ['Accept-Language' => 'fr']);

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('Nom Français'));
    }

    public function test_product_detail_respects_accept_language_header(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);
        $this->createCurrency('USD', 1.0, true);

        $this->createProduct([
            'name'        => ['en' => 'English Name', 'fr' => 'Nom Français'],
            'description' => ['en' => 'English Desc', 'fr' => 'Description Française'],
            'slug'        => 'lang-detail-product',
        ]);

        $response = $this->getJson('/api/products/lang-detail-product', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Nom Français')
            ->assertJsonPath('data.description', 'Description Française');
    }

    // ── Accept-Currency header ────────────────────────────────────────────────

    public function test_product_detail_converts_prices_via_accept_currency_header(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);
        $this->createCurrency('EUR', 0.9, false);

        $product = $this->createProduct(['slug' => 'currency-product']);
        $this->createVariant($product, ['regular_price' => '100.00', 'special_price' => null]);

        $response = $this->getJson('/api/products/currency-product', ['Accept-Currency' => 'EUR']);

        $response->assertStatus(200);
        $variant = $response->json('data.variants.0');
        $this->assertEquals('90.00', $variant['regular_price']);
    }

    public function test_product_listing_converts_prices_via_accept_currency_header(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);
        $this->createCurrency('EUR', 0.9, false);

        $product = $this->createProduct();
        $this->createVariant($product, ['regular_price' => '200.00']);

        $response = $this->getJson('/api/products', ['Accept-Currency' => 'EUR']);

        $response->assertStatus(200);
        $variant = $response->json('data.0.variants.0');
        $this->assertEquals('180.00', $variant['regular_price']);
    }
}
