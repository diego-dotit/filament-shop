<?php

namespace Database\Factories;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerAddress>
 */
class CustomerAddressFactory extends Factory
{
    protected $model = CustomerAddress::class;

    public function definition(): array
    {
        return [
            'customer_id'    => fn () => Customer::factory(),
            'shipping'       => 0,
            'business'       => 0,
            'firstname'      => fake()->firstName(),
            'lastname'       => fake()->lastName(),
            'country_id'     => null,
            'zone_id'        => null,
            'city_id'        => null,
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional(0.4)->secondaryAddress(),
            'postcode'       => fake()->postcode(),
        ];
    }
}
