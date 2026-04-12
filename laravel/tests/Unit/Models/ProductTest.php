<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\Attribute;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductAttribute;
use App\Domains\Product\Models\ProductVariant;
use App\Domains\Product\Models\ProductVariantAttribute;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    // ── Product ──────────────────────────────────────────────────────────────

    public function test_product_fillable_contains_expected_fields(): void
    {
        $product = new Product();

        $this->assertSame(
            ['name', 'slug', 'description', 'is_active', 'meta_title', 'meta_description', 'meta_keywords'],
            $product->getFillable()
        );
    }

    public function test_product_is_active_is_cast_to_boolean(): void
    {
        $product = new Product();

        $this->assertArrayHasKey('is_active', $product->getCasts());
        $this->assertSame('boolean', $product->getCasts()['is_active']);
    }

    public function test_product_variants_relationship_is_has_many_product_variant(): void
    {
        $product = new Product();
        $relation = $product->variants();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(ProductVariant::class, $relation->getRelated());
    }

    public function test_product_categories_relationship_is_belongs_to_many(): void
    {
        $product = new Product();
        $relation = $product->categories();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertSame('category_product', $relation->getTable());
    }

    public function test_product_manufacturers_relationship_is_belongs_to_many(): void
    {
        $product = new Product();
        $relation = $product->manufacturers();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
        $this->assertSame('product_manufacturer', $relation->getTable());
    }

    public function test_product_product_attributes_relationship_is_has_many(): void
    {
        $product = new Product();
        $relation = $product->productAttributes();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(ProductAttribute::class, $relation->getRelated());
    }

    public function test_product_reviews_relationship_is_has_many(): void
    {
        $product = new Product();
        $relation = $product->reviews();

        $this->assertInstanceOf(HasMany::class, $relation);
    }

    public function test_product_scope_active_adds_where_is_active_clause(): void
    {
        $product = new Product();
        $query   = $product->newQuery();
        $scoped  = $product->scopeActive($query);

        $this->assertStringContainsString('is_active', $scoped->toSql());
    }

    // ── Translatable ─────────────────────────────────────────────────────────

    public function test_product_uses_has_translations_trait(): void
    {
        $traits = class_uses_recursive(Product::class);

        $this->assertContains(\Spatie\Translatable\HasTranslations::class, $traits);
    }

    public function test_product_translatable_fields_include_meta_fields(): void
    {
        $product = new Product();

        $this->assertSame(['name', 'description', 'meta_title', 'meta_description', 'meta_keywords'], $product->getTranslatableAttributes());
    }

    // ── Media Library ────────────────────────────────────────────────────────

    public function test_product_implements_has_media_interface(): void
    {
        $this->assertInstanceOf(\Spatie\MediaLibrary\HasMedia::class, new Product());
    }

    public function test_product_uses_interacts_with_media_trait(): void
    {
        $traits = class_uses_recursive(Product::class);

        $this->assertContains(\Spatie\MediaLibrary\InteractsWithMedia::class, $traits);
    }

    public function test_product_registers_images_media_collection(): void
    {
        $product = new Product();
        $product->registerMediaCollections();
        $collections = $product->getRegisteredMediaCollections();

        $names = $collections->pluck('name')->all();
        $this->assertContains('images', $names);
    }

    // ── ProductVariant ───────────────────────────────────────────────────────

    public function test_product_variant_fillable_contains_expected_fields(): void
    {
        $variant = new ProductVariant();

        $this->assertSame(
            ['product_id', 'sku', 'name', 'regular_price', 'special_price', 'stock_quantity', 'weight', 'is_active'],
            $variant->getFillable()
        );
    }

    public function test_product_variant_casts_are_correct(): void
    {
        $variant = new ProductVariant();
        $casts = $variant->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertSame('boolean', $casts['is_active']);

        $this->assertArrayHasKey('regular_price', $casts);
        $this->assertSame('decimal:2', $casts['regular_price']);

        $this->assertArrayHasKey('special_price', $casts);
        $this->assertSame('decimal:2', $casts['special_price']);
    }

    public function test_product_variant_product_relationship_is_belongs_to(): void
    {
        $variant = new ProductVariant();
        $relation = $variant->product();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Product::class, $relation->getRelated());
    }

    public function test_product_variant_attributes_relationship_is_has_many(): void
    {
        $variant = new ProductVariant();
        $relation = $variant->attributes();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(ProductVariantAttribute::class, $relation->getRelated());
    }

    // ── Attribute ────────────────────────────────────────────────────────────

    public function test_attribute_fillable_contains_expected_fields(): void
    {
        $attribute = new Attribute();

        $this->assertSame(['name'], $attribute->getFillable());
    }

    public function test_attribute_product_attributes_relationship_is_has_many(): void
    {
        $attribute = new Attribute();
        $relation = $attribute->productAttributes();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(ProductAttribute::class, $relation->getRelated());
    }

    // ── ProductAttribute ─────────────────────────────────────────────────────

    public function test_product_attribute_fillable_contains_expected_fields(): void
    {
        $productAttribute = new ProductAttribute();

        $this->assertSame(['product_id', 'attribute_id', 'value'], $productAttribute->getFillable());
    }

    public function test_product_attribute_product_relationship_is_belongs_to(): void
    {
        $productAttribute = new ProductAttribute();
        $relation = $productAttribute->product();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Product::class, $relation->getRelated());
    }

    public function test_product_attribute_attribute_relationship_is_belongs_to(): void
    {
        $productAttribute = new ProductAttribute();
        $relation = $productAttribute->attribute();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Attribute::class, $relation->getRelated());
    }

    // ── ProductVariantAttribute ──────────────────────────────────────────────

    public function test_product_variant_attribute_fillable_contains_expected_fields(): void
    {
        $pva = new ProductVariantAttribute();

        $this->assertSame(['product_variant_id', 'name', 'value'], $pva->getFillable());
    }

    public function test_product_variant_attribute_product_variant_relationship_is_belongs_to(): void
    {
        $pva = new ProductVariantAttribute();
        $relation = $pva->productVariant();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(ProductVariant::class, $relation->getRelated());
    }
}
