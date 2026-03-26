<?php

namespace Database\Factories;

use App\Domains\Customer\Models\Customer;
use App\Domains\Order\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id'   => fn () => Customer::factory(),
            'status'        => fake()->randomElement(['pending', 'processing', 'shipped', 'completed', 'cancelled']),
            'total_amount'  => fake()->randomFloat(2, 10, 2000),
            'currency_code' => fake()->currencyCode(),
            'exchange_rate' => fake()->randomFloat(6, 0.5, 2.0),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'processing']);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'completed']);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'cancelled']);
    }
}
