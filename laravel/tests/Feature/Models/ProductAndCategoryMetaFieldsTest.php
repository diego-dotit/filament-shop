<?php

namespace Tests\Feature\Models;

use App\Domains\Category\Models\Category;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Product and Category SEO meta field additions.
 * Product-only meta field tests also exist in ProductMetaFieldsTest.php.
 * This file adds the missing Category-specific coverage plus cross-model checks.
 */
class ProductAndCategoryMetaFieldsTest extends TestCase
{
    use RefreshDatabase;

    // ── Product meta fields (T1.9) ────────────────────────────────────────────

    public function test_product_translatable_includes_all_meta_fields(): void
    {
        $product = new Product();

        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $this->assertContains($field, $product->translatable);
        }
    }

    public function test_product_fillable_includes_all_meta_fields(): void
    {
        $product = new Product();

        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $this->assertContains($field, $product->getFillable());
        }
    }

    public function test_product_meta_keywords_can_be_stored_and_retrieved_per_locale(): void
    {
        $product = Product::create([
            'name'          => 'Widget Pro',
            'slug'          => 'widget-pro',
            'meta_keywords' => 'widget, pro, buy',
            'is_active'     => true,
        ]);

        $product->setTranslation('meta_keywords', 'fr', 'widget, pro, acheter');
        $product->save();

        $this->assertSame('widget, pro, buy', $product->getTranslation('meta_keywords', 'en'));
        $this->assertSame('widget, pro, acheter', $product->getTranslation('meta_keywords', 'fr'));
    }

    // ── Category meta fields (T1.10) ──────────────────────────────────────────

    public function test_category_translatable_includes_description(): void
    {
        $category = new Category();

        $this->assertContains('description', $category->translatable);
    }

    public function test_category_translatable_includes_all_meta_fields(): void
    {
        $category = new Category();

        foreach (['meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $this->assertContains($field, $category->translatable);
        }
    }

    public function test_category_fillable_includes_description_and_meta_fields(): void
    {
        $category = new Category();

        foreach (['description', 'meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $this->assertContains($field, $category->getFillable());
        }
    }

    public function test_category_description_can_be_stored_and_retrieved_per_locale(): void
    {
        $category = Category::create([
            'name'        => 'Electronics',
            'slug'        => 'electronics',
            'description' => 'English description of electronics',
            'is_active'   => true,
        ]);

        $category->setTranslation('description', 'fr', 'Description française de l\'électronique');
        $category->save();

        $this->assertSame(
            'English description of electronics',
            $category->getTranslation('description', 'en'),
        );
        $this->assertSame(
            'Description française de l\'électronique',
            $category->getTranslation('description', 'fr'),
        );
    }

    public function test_category_meta_title_can_be_stored_and_retrieved_per_locale(): void
    {
        $category = Category::create([
            'name'       => 'Books',
            'slug'       => 'books',
            'meta_title' => 'Buy Books Online',
            'is_active'  => true,
        ]);

        $category->setTranslation('meta_title', 'fr', 'Acheter des Livres en Ligne');
        $category->save();

        $this->assertSame('Buy Books Online', $category->getTranslation('meta_title', 'en'));
        $this->assertSame('Acheter des Livres en Ligne', $category->getTranslation('meta_title', 'fr'));
    }

    public function test_category_meta_description_can_be_stored_and_retrieved_per_locale(): void
    {
        $category = Category::create([
            'name'             => 'Clothing',
            'slug'             => 'clothing',
            'meta_description' => 'Shop the best clothing',
            'is_active'        => true,
        ]);

        $category->setTranslation('meta_description', 'es', 'Compre la mejor ropa');
        $category->save();

        $this->assertSame('Shop the best clothing', $category->getTranslation('meta_description', 'en'));
        $this->assertSame('Compre la mejor ropa', $category->getTranslation('meta_description', 'es'));
    }

    public function test_category_meta_keywords_can_be_stored_and_retrieved_per_locale(): void
    {
        $category = Category::create([
            'name'          => 'Toys',
            'slug'          => 'toys',
            'meta_keywords' => 'toys, games, kids',
            'is_active'     => true,
        ]);

        $category->setTranslation('meta_keywords', 'fr', 'jouets, jeux, enfants');
        $category->save();

        $this->assertSame('toys, games, kids', $category->getTranslation('meta_keywords', 'en'));
        $this->assertSame('jouets, jeux, enfants', $category->getTranslation('meta_keywords', 'fr'));
    }
}
