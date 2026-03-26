<?php

namespace Database\Factories;

use App\Domains\Product\Models\ProductVariant;
use App\Domains\Product\Models\ProductVariantAttribute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariantAttribute>
 */
class ProductVariantAttributeFactory extends Factory
{
    protected $model = ProductVariantAttribute::class;

    public function definition(): array
    {
        return [
            'product_variant_id' => fn () => ProductVariant::factory(),
            'name'               => fake()->word(),
            'value'              => fake()->word(),
        ];
    }
}
