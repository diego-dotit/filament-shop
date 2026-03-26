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
            'customer_id'   => fn () => Customer::factory(),
            'country'       => fake()->country(),
            'city'          => fake()->city(),
            'address_line_1'=> fake()->streetAddress(),
            'address_line_2'=> fake()->optional(0.4)->secondaryAddress(),
            'postcode'      => fake()->postcode(),
        ];
    }
}
