<?php

namespace Tests\Feature;

use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMetaFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_translatable_includes_meta_title(): void
    {
        $product = new Product();

        $this->assertContains('meta_title', $product->translatable);
    }

    public function test_product_translatable_includes_meta_description(): void
    {
        $product = new Product();

        $this->assertContains('meta_description', $product->translatable);
    }

    public function test_product_translatable_includes_meta_keywords(): void
    {
        $product = new Product();

        $this->assertContains('meta_keywords', $product->translatable);
    }

    public function test_product_translatable_still_includes_name_and_description(): void
    {
        $product = new Product();

        $this->assertContains('name', $product->translatable);
        $this->assertContains('description', $product->translatable);
    }

    public function test_product_fillable_includes_meta_title(): void
    {
        $product = new Product();

        $this->assertContains('meta_title', $product->getFillable());
    }

    public function test_product_fillable_includes_meta_description(): void
    {
        $product = new Product();

        $this->assertContains('meta_description', $product->getFillable());
    }

    public function test_product_fillable_includes_meta_keywords(): void
    {
        $product = new Product();

        $this->assertContains('meta_keywords', $product->getFillable());
    }

    public function test_product_can_store_and_retrieve_meta_title_translation(): void
    {
        $product = Product::create([
            'name'       => 'Test Product',
            'slug'       => 'test-product',
            'meta_title' => 'SEO Title EN',
            'is_active'  => true,
        ]);

        $product->setTranslation('meta_title', 'fr', 'Titre SEO FR');
        $product->save();

        $this->assertSame('SEO Title EN', $product->getTranslation('meta_title', 'en'));
        $this->assertSame('Titre SEO FR', $product->getTranslation('meta_title', 'fr'));
    }

    public function test_product_can_store_and_retrieve_meta_description_translation(): void
    {
        $product = Product::create([
            'name'             => 'Test Product 2',
            'slug'             => 'test-product-2',
            'meta_description' => 'SEO Description EN',
            'is_active'        => true,
        ]);

        $product->setTranslation('meta_description', 'fr', 'Description SEO FR');
        $product->save();

        $this->assertSame('SEO Description EN', $product->getTranslation('meta_description', 'en'));
        $this->assertSame('Description SEO FR', $product->getTranslation('meta_description', 'fr'));
    }
}
