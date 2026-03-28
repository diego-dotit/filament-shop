<?php

namespace Tests\Feature\Api\Product;

use App\Domains\Category\Models\Category;
use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);

        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createCategory(string $slug): Category
    {
        return Category::create([
            'name'      => ['en' => ucfirst($slug)],
            'slug'      => $slug,
            'is_active' => true,
        ]);
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name'        => ['en' => 'Test Product'],
            'slug'        => 'test-product-' . uniqid(),
            'description' => ['en' => 'A description'],
            'is_active'   => true,
        ], $overrides));
    }

    // ── GET /api/products?category_slug= ─────────────────────────────────────

    public function test_category_slug_filter_returns_only_products_in_that_category(): void
    {
        $electronics = $this->createCategory('electronics');
        $clothing    = $this->createCategory('clothing');

        $phone  = $this->createProduct(['slug' => 'phone']);
        $laptop = $this->createProduct(['slug' => 'laptop']);
        $shirt  = $this->createProduct(['slug' => 'shirt']);

        $electronics->products()->attach([$phone->id, $laptop->id]);
        $clothing->products()->attach($shirt->id);

        $response = $this->getJson('/api/products?category_slug=electronics');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $slugs = collect($response->json('data'))->pluck('slug')->sort()->values()->toArray();
        $this->assertContains('phone', $slugs);
        $this->assertContains('laptop', $slugs);
        $this->assertNotContains('shirt', $slugs);
    }

    public function test_category_slug_filter_excludes_products_not_in_category(): void
    {
        $electronics = $this->createCategory('electronics');

        $phone  = $this->createProduct(['slug' => 'phone']);
        $unrelated = $this->createProduct(['slug' => 'unrelated-product']);

        $electronics->products()->attach($phone->id);
        // $unrelated is NOT attached to any category

        $response = $this->getJson('/api/products?category_slug=electronics');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'phone');
    }

    public function test_category_slug_filter_with_nonexistent_slug_returns_empty_results(): void
    {
        $this->createProduct(['slug' => 'some-product']);

        $response = $this->getJson('/api/products?category_slug=nonexistent-category');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    public function test_without_category_slug_returns_all_active_products(): void
    {
        $electronics = $this->createCategory('electronics');

        $phone  = $this->createProduct(['slug' => 'phone']);
        $laptop = $this->createProduct(['slug' => 'laptop']);
        $shirt  = $this->createProduct(['slug' => 'shirt']);

        $electronics->products()->attach($phone->id);
        // laptop and shirt not attached to any category

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_category_slug_filter_only_includes_active_products(): void
    {
        $electronics = $this->createCategory('electronics');

        $active   = $this->createProduct(['slug' => 'active-phone',   'is_active' => true]);
        $inactive = $this->createProduct(['slug' => 'inactive-phone',  'is_active' => false]);

        $electronics->products()->attach([$active->id, $inactive->id]);

        $response = $this->getJson('/api/products?category_slug=electronics');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'active-phone');
    }
}
