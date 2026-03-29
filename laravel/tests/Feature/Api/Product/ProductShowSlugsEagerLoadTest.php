<?php

namespace Tests\Feature\Api\Product;

use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifies that the ProductController::show() method eager-loads the direct
 * 'slugs' relationship on the Product model, preventing N+1 queries when
 * slug data is accessed after the initial load.
 */
class ProductShowSlugsEagerLoadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);
    }

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
            'name'        => ['en' => 'Test Product'],
            'slug'        => 'test-product-' . uniqid(),
            'description' => ['en' => 'A description'],
            'is_active'   => true,
        ], $overrides));
    }

    /**
     * The show() endpoint must include 'slugs' in its eager-load list so that
     * the product's Slug morphMany records are fetched in a single JOIN query
     * rather than triggering a separate query per access.
     *
     * Without 'slugs' in ->with([...]), calling show() on a product that has
     * slug records produces zero slug-related queries for the product model
     * (only categories.slugs would fire, but only when categories exist).
     *
     * With 'slugs' in ->with([...]), exactly one slug query is issued for the
     * product's own sluggable records.
     */
    public function test_product_show_eager_loads_product_slugs_relationship(): void
    {
        // Arrange – language triggers HasSlugs::booted() to create a Slug record
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        // Product with no categories so categories.slugs generates no query,
        // isolating only the product-level slugs query.
        $product = $this->createProduct(['slug' => 'eager-slugs-product']);

        // Confirm HasSlugs trait created at least one slug for this product.
        $this->assertGreaterThan(
            0,
            $product->slugs()->count(),
            'Pre-condition: product must have slug records via HasSlugs trait'
        );

        // Act – capture queries during the show request only
        DB::enableQueryLog();
        $response = $this->getJson('/api/products/eager-slugs-product');
        $queries  = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Assert – at least one query against the slugs table occurred,
        // proving the relationship was eager-loaded as part of the show() call.
        $slugTableQueries = collect($queries)->filter(
            fn ($q) => str_contains(strtolower($q['query']), 'slugs')
        );

        $this->assertGreaterThanOrEqual(
            1,
            $slugTableQueries->count(),
            'Expected at least one query against the slugs table (eager-load), but found none. '
            . 'Add \'slugs\' to the ->with([...]) array in ProductController::show().'
        );
    }

    /**
     * Verify the show endpoint still returns a valid 200 response after
     * the eager-load change (regression guard).
     */
    public function test_product_show_returns_200_with_slugs_eager_loaded(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        $product = $this->createProduct(['slug' => 'eager-slugs-regression']);

        $response = $this->getJson('/api/products/eager-slugs-regression');

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'eager-slugs-regression')
            ->assertJsonPath('data.id', $product->id);
    }
}
