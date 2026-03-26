<?php

namespace Database\Seeders;

use App\Domains\Customer\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Seed a test customer, address, and cart.
     *
     * Idempotent: uses updateOrCreate on email for the User, then
     * firstOrCreate for the related Customer, Address, and Cart records.
     */
    public function run(): void
    {
        // 1. Create or update the test User account
        $user = User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'     => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Create or retrieve the linked Customer record via User relationship
        // (user_id is set automatically by the hasOne relationship)
        $customer = $user->customer()->firstOrCreate(
            [],
            [
                'first_name' => 'Test',
                'last_name'  => 'Customer',
                'email'      => 'test@example.com',
                'phone'      => '+1234567890',
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
