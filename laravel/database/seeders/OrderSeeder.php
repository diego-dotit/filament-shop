<?php

namespace Database\Seeders;

use App\Domains\Customer\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderAddress;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Seed sample orders for the test customer.
     *
     * Idempotent: only seeds if no orders exist for the test customer.
     */
    public function run(): void
    {
        $customer = Customer::whereHas('user', fn ($q) => $q->where('email', 'test@example.com'))
            ->first();

        if (! $customer) {
            return;
        }

        if ($customer->orders()->count() > 0) {
            return;
        }

        $address = $customer->addresses()->first();

        $statuses = ['pending', 'processing', 'completed'];

        foreach ($statuses as $status) {
            $order = $customer->orders()->create([
                'status'        => $status,
                'total_amount'  => fake()->randomFloat(2, 20, 500),
                'currency_code' => 'USD',
                'exchange_rate' => 1.000000,
            ]);

            $addressData = $address ? [
                'country'       => $address->country,
                'city'          => $address->city,
                'address_line_1' => $address->address_line_1,
                'address_line_2' => $address->address_line_2,
                'postcode'      => $address->postcode,
            ] : [
                'country'       => 'United States',
                'city'          => 'New York',
                'address_line_1' => '123 Main Street',
                'postcode'      => '10001',
            ];

            $order->addresses()->create(array_merge($addressData, ['type' => 'shipping']));
            $order->addresses()->create(array_merge($addressData, ['type' => 'billing']));
        }
    }
}
