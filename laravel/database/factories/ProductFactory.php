<?php

namespace Database\Factories;

use App\Domains\Product\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'name'        => ['en' => ucwords($name)],
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 99999),
            'description' => ['en' => fake()->paragraph()],
            'is_active'   => fake()->boolean(70),
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
