<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\ProductVariant;
use App\Domains\Product\Models\ProductVariantAttribute;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\TestCase;

class ProductVariantAttributeTest extends TestCase
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

    public function test_product_variant_attribute_class_exists(): void
    {
        $this->assertTrue(class_exists(ProductVariantAttribute::class));
    }

    public function test_product_variant_attribute_can_be_instantiated(): void
    {
        $pva = new ProductVariantAttribute();

        $this->assertInstanceOf(ProductVariantAttribute::class, $pva);
    }

    public function test_product_variant_attribute_fillable_contains_expected_fields(): void
    {
        $pva = new ProductVariantAttribute();

        $this->assertSame(['product_variant_id', 'name', 'value'], $pva->getFillable());
    }

    public function test_product_variant_attribute_attribute_access(): void
    {
        $pva        = new ProductVariantAttribute();
        $pva->name  = 'Size';
        $pva->value = 'XL';

        $this->assertSame('Size', $pva->name);
        $this->assertSame('XL', $pva->value);
    }

    public function test_product_variant_attribute_product_variant_relationship_is_belongs_to(): void
    {
        $pva      = new ProductVariantAttribute();
        $relation = $pva->productVariant();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(ProductVariant::class, $relation->getRelated());
    }

    public function test_product_variant_attribute_has_product_variant_relationship_method(): void
    {
        $pva = new ProductVariantAttribute();

        $this->assertTrue(method_exists($pva, 'productVariant'));
    }
}
