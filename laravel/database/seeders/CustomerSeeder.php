<?php

namespace Database\Seeders;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Seed 5 test customers with addresses.
     *
     * Idempotent: uses firstOrCreate on email for each customer.
     */
    public function run(): void
    {
        $customers = [
            [
                'email'      => 'customer1@example.com',
                'first_name' => 'Alice',
                'last_name'  => 'Smith',
                'phone'      => '+1234567001',
            ],
            [
                'email'      => 'customer2@example.com',
                'first_name' => 'Bob',
                'last_name'  => 'Johnson',
                'phone'      => '+1234567002',
            ],
            [
                'email'      => 'customer3@example.com',
                'first_name' => 'Carol',
                'last_name'  => 'Williams',
                'phone'      => '+1234567003',
            ],
            [
                'email'      => 'customer4@example.com',
                'first_name' => 'David',
                'last_name'  => 'Brown',
                'phone'      => '+1234567004',
            ],
            [
                'email'      => 'customer5@example.com',
                'first_name' => 'Eve',
                'last_name'  => 'Davis',
                'phone'      => '+1234567005',
            ],
        ];

        foreach ($customers as $data) {
            $customer = Customer::firstOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name'  => $data['last_name'],
                    'phone'      => $data['phone'],
                    'password'   => Hash::make('password'),
                ]
            );

            // Create 1–2 addresses per customer using the factory (idempotent)
            if ($customer->addresses()->count() === 0) {
                CustomerAddress::factory()->count(rand(1, 2))->create(['customer_id' => $customer->id]);
            }
        }
    }
}

