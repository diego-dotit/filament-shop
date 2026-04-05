<?php

namespace Database\Seeders;

use App\Domains\Customer\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Seed a test customer, address, and cart.
     *
     * Idempotent: uses updateOrCreate on email for the Customer directly.
     */
    public function run(): void
    {
        // 1. Create or update the test Customer account
        $customer = Customer::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name'  => 'Customer',
                'phone'      => '+1234567890',
                'password'   => 'password',
            ]
        );

        // 3. Create a default address if none exists
        if ($customer->addresses()->count() === 0) {
            $customer->addresses()->create([
                'country'       => 'United States',
                'city'          => 'New York',
                'address_line_1' => '123 Main Street',
                'postcode'      => '10001',
            ]);
        }

        // 4. Create an empty Cart for the customer (idempotent via firstOrCreate)
        $customer->cart()->firstOrCreate([]);
    }
}
