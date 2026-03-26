<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Domains\Product\Models\ProductVariantAttribute;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

class ProductVariantTest extends TestCase
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

    public function test_product_variant_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductVariant::class));
    }

    public function test_product_variant_can_be_instantiated(): void
    {
        $variant = new ProductVariant();

        $this->assertInstanceOf(ProductVariant::class, $variant);
    }

    public function test_product_variant_fillable_contains_expected_fields(): void
    {
        $variant = new ProductVariant();

        $this->assertSame(
            ['product_id', 'sku', 'regular_price', 'special_price', 'stock_quantity', 'weight', 'is_active'],
            $variant->getFillable()
        );
    }

    public function test_product_variant_attribute_access(): void
    {
        $variant       = new ProductVariant();
        $variant->sku  = 'SKU-001';

        $this->assertSame('SKU-001', $variant->sku);
    }

    public function test_product_variant_is_active_is_cast_to_boolean(): void
    {
        $variant = new ProductVariant();
        $casts   = $variant->getCasts();

        $this->assertArrayHasKey('is_active', $casts);
        $this->assertSame('boolean', $casts['is_active']);
    }

    public function test_product_variant_regular_price_is_cast_to_decimal(): void
    {
        $variant = new ProductVariant();
        $casts   = $variant->getCasts();

        $this->assertArrayHasKey('regular_price', $casts);
        $this->assertSame('decimal:2', $casts['regular_price']);
    }

    public function test_product_variant_special_price_is_cast_to_decimal(): void
    {
        $variant = new ProductVariant();
        $casts   = $variant->getCasts();

        $this->assertArrayHasKey('special_price', $casts);
        $this->assertSame('decimal:2', $casts['special_price']);
    }

    public function test_product_variant_product_relationship_is_belongs_to(): void
    {
        $variant  = new ProductVariant();
        $relation = $variant->product();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Product::class, $relation->getRelated());
    }

    public function test_product_variant_attributes_relationship_is_has_many(): void
    {
        $variant  = new ProductVariant();
        $relation = $variant->attributes();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(ProductVariantAttribute::class, $relation->getRelated());
    }

    public function test_product_variant_scope_active_adds_where_is_active_clause(): void
    {
        $variant = new ProductVariant();
        $query   = $variant->newQuery();
        $scoped  = $variant->scopeActive($query);

        $this->assertStringContainsString('is_active', $scoped->toSql());
    }
}
