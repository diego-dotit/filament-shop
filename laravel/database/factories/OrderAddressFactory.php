<?php

namespace Database\Factories;

use App\Domains\Localisation\Models\Country;
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
            'order_id'       => fn () => Order::factory(),
            'shipping'       => false,
            'business'       => false,
            'firstname'      => fake()->firstName(),
            'lastname'       => fake()->lastName(),
            'company'        => null,
            'company_id'     => null,
            'tax_id'         => null,
            'country_id'     => fn () => Country::factory(),
            'zone_id'        => null,
            'city_id'        => null,
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional(0.4)->secondaryAddress(),
            'postcode'       => fake()->postcode(),
        ];
    }

    public function billing(): static
    {
        return $this->state(fn (array $attributes) => ['shipping' => 0]);
    }

    public function shipping(): static
    {
        return $this->state(fn (array $attributes) => ['shipping' => 1]);
    }
}
