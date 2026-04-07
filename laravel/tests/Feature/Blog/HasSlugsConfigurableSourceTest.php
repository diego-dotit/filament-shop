<?php

namespace Tests\Feature\Blog;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasSlugsConfigurableSourceTest extends TestCase
{
    use RefreshDatabase;

    // ── Backward compatibility ─────────────────────────────────────────────────

    public function test_existing_product_model_still_generates_slug_from_name(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $product = Product::create([
            'name'      => ['en' => 'Classic Widget'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $slug = $product->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('classic-widget', $slug->slug);
    }

    public function test_existing_category_model_still_generates_slug_from_name(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = Category::create([
            'name'      => ['en' => 'Electronics'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $slug = $category->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('electronics', $slug->slug);
    }

    // ── Configurable source field ──────────────────────────────────────────────

    public function test_model_with_slug_source_field_uses_configured_field_for_slug(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        // BlogCategory uses $slugSourceField = 'title'
        $category = \App\Domains\Blog\Models\BlogCategory::create([
            'title'  => ['en' => 'Tech News'],
            'status' => 'active',
        ]);

        $slug = $category->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('tech-news', $slug->slug);
    }

    public function test_model_without_slug_source_field_defaults_to_name(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        // Product doesn't declare $slugSourceField, defaults to 'name'
        $product = Product::create([
            'name'      => ['en' => 'Default Field Product'],
            'slug'      => 'placeholder',
            'is_active' => true,
        ]);

        $slug = $product->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('default-field-product', $slug->slug);
    }
}
