<?php

namespace Database\Factories;

use App\Domains\Customer\Models\Customer;
use App\Domains\Product\Models\Product;
use App\Domains\Review\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'product_id'  => fn () => Product::factory(),
            'customer_id' => fn () => Customer::factory(),
            'rating'      => fake()->numberBetween(1, 5),
            'comment'     => fake()->optional(0.8)->paragraph(),
            'status'      => fake()->randomElement(['pending', 'approved', 'rejected']),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'approved']);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'rejected']);
    }
}
