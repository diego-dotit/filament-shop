<?php

namespace Tests\Unit\Models;

use App\Domains\Product\Models\Attribute;
use App\Domains\Product\Models\ProductAttribute;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
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

    public function test_attribute_class_exists(): void
    {
        $this->assertTrue(class_exists(Attribute::class));
    }

    public function test_attribute_can_be_instantiated(): void
    {
        $attribute = new Attribute();

        $this->assertInstanceOf(Attribute::class, $attribute);
    }

    public function test_attribute_fillable_contains_expected_fields(): void
    {
        $attribute = new Attribute();

        $this->assertSame(['name'], $attribute->getFillable());
    }

    public function test_attribute_attribute_access(): void
    {
        $attribute       = new Attribute();
        $attribute->name = 'Color';

        $this->assertSame('Color', $attribute->name);
    }

    public function test_attribute_product_attributes_relationship_is_has_many(): void
    {
        $attribute = new Attribute();
        $relation  = $attribute->productAttributes();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(ProductAttribute::class, $relation->getRelated());
    }

    public function test_attribute_has_product_attributes_relationship_method(): void
    {
        $attribute = new Attribute();

        $this->assertTrue(method_exists($attribute, 'productAttributes'));
    }
}
