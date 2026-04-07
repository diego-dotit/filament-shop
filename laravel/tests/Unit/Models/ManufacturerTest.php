<?php

namespace Tests\Unit\Models;

use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Product\Models\Product;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class ManufacturerTest extends TestCase
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

    public function test_manufacturer_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, new Manufacturer());
    }

    public function test_fillable_contains_expected_fields(): void
    {
        $manufacturer = new Manufacturer();

        $this->assertSame(
            ['name', 'slug', 'description', 'meta_title', 'meta_description', 'meta_keywords'],
            $manufacturer->getFillable()
        );
    }

    public function test_products_relationship_is_belongs_to_many_product(): void
    {
        $manufacturer = new Manufacturer();
        $relation = $manufacturer->products();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $relation);
        $this->assertInstanceOf(Product::class, $relation->getRelated());
    }

    public function test_products_relationship_uses_correct_pivot_table(): void
    {
        $manufacturer = new Manufacturer();
        $relation = $manufacturer->products();

        $this->assertSame('product_manufacturer', $relation->getTable());
    }
}
