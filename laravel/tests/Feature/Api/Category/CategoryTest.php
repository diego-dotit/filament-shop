<?php

namespace Tests\Feature\Api\Category;

use App\Domains\Category\Models\Category;
use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private Language $defaultLang;
    private Currency $defaultCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);

        $this->defaultLang     = Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $this->defaultCurrency = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);
    }

    // ── GET /api/categories (index) ──────────────────────────────────────────

    public function test_index_returns_only_root_active_categories(): void
    {
        Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics', 'is_active' => true]);
        Category::create(['name' => ['en' => 'Clothing'],    'slug' => 'clothing',     'is_active' => true]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.slug', 'electronics')
            ->assertJsonPath('data.1.slug', 'clothing');
    }

    public function test_index_excludes_inactive_categories(): void
    {
        Category::create(['name' => ['en' => 'Active'],   'slug' => 'active',   'is_active' => true]);
        Category::create(['name' => ['en' => 'Inactive'], 'slug' => 'inactive', 'is_active' => false]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'active');
    }

    public function test_index_excludes_child_categories_from_root_list(): void
    {
        $parent = Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics', 'is_active' => true]);
        Category::create(['name' => ['en' => 'Phones'], 'slug' => 'phones', 'is_active' => true, 'parent_id' => $parent->id]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'electronics');
    }

    public function test_index_returns_category_resource_structure(): void
    {
        Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics', 'is_active' => true]);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'slug', 'name', 'is_active', 'children'],
                ],
            ]);
    }

    // ── GET /api/categories/{slug} (show) ────────────────────────────────────

    public function test_show_returns_category_by_slug(): void
    {
        Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics', 'is_active' => true]);

        $response = $this->getJson('/api/categories/electronics');

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'electronics')
            ->assertJsonPath('data.name', 'Electronics');
    }

    public function test_show_returns_child_categories_nested_one_level_deep(): void
    {
        $parent = Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics', 'is_active' => true]);
        Category::create(['name' => ['en' => 'Phones'],   'slug' => 'phones',   'is_active' => true,  'parent_id' => $parent->id]);
        Category::create(['name' => ['en' => 'Laptops'],  'slug' => 'laptops',  'is_active' => true,  'parent_id' => $parent->id]);
        Category::create(['name' => ['en' => 'Inactive'], 'slug' => 'inactive', 'is_active' => false, 'parent_id' => $parent->id]);

        $response = $this->getJson('/api/categories/electronics');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.children')
            ->assertJsonPath('data.children.0.slug', 'phones')
            ->assertJsonPath('data.children.1.slug', 'laptops');
    }

    public function test_show_returns_404_for_invalid_slug(): void
    {
        $response = $this->getJson('/api/categories/nonexistent-slug');

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_inactive_category(): void
    {
        Category::create(['name' => ['en' => 'Hidden'], 'slug' => 'hidden', 'is_active' => false]);

        $response = $this->getJson('/api/categories/hidden');

        $response->assertStatus(404);
    }

    public function test_show_does_not_include_category_products(): void
    {
        Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics', 'is_active' => true]);

        $response = $this->getJson('/api/categories/electronics');

        $response->assertStatus(200);
        $this->assertArrayNotHasKey('products', $response->json('data'));
    }

    // ── Language resolution ───────────────────────────────────────────────────

    public function test_index_returns_name_in_language_from_accept_language_header(): void
    {
        Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);
        Category::create(['name' => ['en' => 'Electronics', 'fr' => 'Électronique'], 'slug' => 'electronics', 'is_active' => true]);

        $response = $this->getJson('/api/categories', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Électronique');
    }

    public function test_show_returns_name_in_language_from_accept_language_header(): void
    {
        Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);
        Category::create(['name' => ['en' => 'Electronics', 'fr' => 'Électronique'], 'slug' => 'electronics', 'is_active' => true]);

        $response = $this->getJson('/api/categories/electronics', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Électronique');
    }

    public function test_index_falls_back_to_default_locale_when_language_not_found(): void
    {
        Category::create(['name' => ['en' => 'Electronics'], 'slug' => 'electronics', 'is_active' => true]);

        $response = $this->getJson('/api/categories', ['Accept-Language' => 'xx']);

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Electronics');
    }
}
