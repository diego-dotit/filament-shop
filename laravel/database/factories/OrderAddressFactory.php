<?php

namespace Database\Factories;

use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderAddress>
 */
class OrderAddressFactory extends Factory
{
    protected $model = OrderAddress::class;

    public function definition(): array
    {
        return [
            'order_id'      => fn () => Order::factory(),
            'type'          => fake()->randomElement(['billing', 'shipping']),
            'country'       => fake()->country(),
            'city'          => fake()->city(),
            'address_line_1'=> fake()->streetAddress(),
            'address_line_2'=> fake()->optional(0.4)->secondaryAddress(),
            'postcode'      => fake()->postcode(),
        ];
    }

    public function billing(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'billing']);
    }

    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'shipping']);
    }
}
