<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\Attribute;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductAttribute;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\TestCase;

class ProductAttributeTest extends TestCase
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

    public function test_product_attribute_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductAttribute::class));
    }

    public function test_product_attribute_can_be_instantiated(): void
    {
        $productAttribute = new ProductAttribute();

        $this->assertInstanceOf(ProductAttribute::class, $productAttribute);
    }

    public function test_product_attribute_fillable_contains_expected_fields(): void
    {
        $productAttribute = new ProductAttribute();

        $this->assertSame(['product_id', 'attribute_id', 'value'], $productAttribute->getFillable());
    }

    public function test_product_attribute_attribute_access(): void
    {
        $productAttribute        = new ProductAttribute();
        $productAttribute->value = 'Red';

        $this->assertSame('Red', $productAttribute->value);
    }

    public function test_product_attribute_product_relationship_is_belongs_to(): void
    {
        $productAttribute = new ProductAttribute();
        $relation         = $productAttribute->product();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Product::class, $relation->getRelated());
    }

    public function test_product_attribute_attribute_relationship_is_belongs_to(): void
    {
        $productAttribute = new ProductAttribute();
        $relation         = $productAttribute->attribute();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Attribute::class, $relation->getRelated());
    }

    public function test_product_attribute_has_product_relationship_method(): void
    {
        $productAttribute = new ProductAttribute();

        $this->assertTrue(method_exists($productAttribute, 'product'));
    }

    public function test_product_attribute_has_attribute_relationship_method(): void
    {
        $productAttribute = new ProductAttribute();

        $this->assertTrue(method_exists($productAttribute, 'attribute'));
    }
}
