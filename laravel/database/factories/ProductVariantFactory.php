<?php

namespace Database\Factories;

use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        $regularPrice = fake()->randomFloat(2, 5, 500);

        return [
            'product_id'     => fn () => Product::factory(),
            'sku'            => strtoupper(fake()->unique()->bothify('SKU-####-???')),
            'regular_price'  => $regularPrice,
            'special_price'  => fake()->optional(0.5)->randomFloat(2, 1, $regularPrice - 0.01),
            'stock_quantity' => fake()->numberBetween(0, 200),
            'weight'         => fake()->randomFloat(3, 0.1, 20),
            'is_active'      => fake()->boolean(70),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
