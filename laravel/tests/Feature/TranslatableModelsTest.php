<?php

namespace Tests\Feature;

use App\Domains\Category\Models\Category;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslatableModelsTest extends TestCase
{
    use RefreshDatabase;

    // ── Product ───────────────────────────────────────────────────────────────

    public function test_product_can_set_and_get_translation_for_name(): void
    {
        $product = Product::create([
            'name'      => 'English Name',
            'slug'      => 'english-name',
            'is_active' => true,
        ]);

        $product->setTranslation('name', 'fr', 'Nom Français');
        $product->save();

        $this->assertSame('English Name', $product->getTranslation('name', 'en'));
        $this->assertSame('Nom Français', $product->getTranslation('name', 'fr'));
    }

    public function test_product_can_set_and_get_translation_for_description(): void
    {
        $product = Product::create([
            'name'        => 'Test Product',
            'slug'        => 'test-product',
            'description' => 'English description',
            'is_active'   => true,
        ]);

        $product->setTranslation('description', 'fr', 'Description française');
        $product->save();

        $this->assertSame('English description', $product->getTranslation('description', 'en'));
        $this->assertSame('Description française', $product->getTranslation('description', 'fr'));
    }

    public function test_product_falls_back_to_default_locale_when_translation_missing(): void
    {
        $product = Product::create([
            'name'      => 'Fallback Name',
            'slug'      => 'fallback-name',
            'is_active' => true,
        ]);

        // 'de' translation is not set; should fall back to 'en' per config
        $result = $product->getTranslation('name', 'de', true);

        $this->assertSame('Fallback Name', $result);
    }

    public function test_product_name_returns_null_when_translation_missing_and_no_fallback(): void
    {
        $product = Product::create([
            'name'      => 'Some Name',
            'slug'      => 'some-name',
            'is_active' => true,
        ]);

        $result = $product->getTranslation('name', 'de', false);

        $this->assertSame('', $result);
    }

    // ── Category ──────────────────────────────────────────────────────────────

    public function test_category_can_set_and_get_translation_for_name(): void
    {
        $category = Category::create([
            'name'      => 'Electronics',
            'slug'      => 'electronics',
            'is_active' => true,
        ]);

        $category->setTranslation('name', 'fr', 'Électronique');
        $category->save();

        $this->assertSame('Electronics', $category->getTranslation('name', 'en'));
        $this->assertSame('Électronique', $category->getTranslation('name', 'fr'));
    }

    public function test_category_falls_back_to_default_locale_when_translation_missing(): void
    {
        $category = Category::create([
            'name'      => 'Books',
            'slug'      => 'books',
            'is_active' => true,
        ]);

        $result = $category->getTranslation('name', 'es', true);

        $this->assertSame('Books', $result);
    }
}
