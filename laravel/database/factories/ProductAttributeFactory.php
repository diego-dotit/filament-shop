<?php

namespace Database\Factories;

use App\Domains\Product\Models\Attribute;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductAttribute>
 */
class ProductAttributeFactory extends Factory
{
    protected $model = ProductAttribute::class;

    public function definition(): array
    {
        return [
            'product_id'   => fn () => Product::factory(),
            'attribute_id' => fn () => Attribute::factory(),
            'value'        => fake()->word(),
        ];
    }
}
