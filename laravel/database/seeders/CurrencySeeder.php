<?php

namespace Database\Seeders;

use App\Domains\Currency\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Seed the currencies table.
     *
     * Idempotent: uses updateOrCreate so re-running produces no duplicates.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code'          => 'USD',
                'name'          => 'US Dollar',
                'symbol'        => '$',
                'exchange_rate' => 1.0,
                'is_base'       => true,
            ],
            [
                'code'          => 'EUR',
                'name'          => 'Euro',
                'symbol'        => '€',
                'exchange_rate' => 0.85,
                'is_base'       => false,
            ],
            [
                'code'          => 'GBP',
                'name'          => 'British Pound',
                'symbol'        => '£',
                'exchange_rate' => 0.73,
                'is_base'       => false,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                [
                    'name'          => $currency['name'],
                    'symbol'        => $currency['symbol'],
                    'exchange_rate' => $currency['exchange_rate'],
                    'is_base'       => $currency['is_base'],
                ]
            );
        }
    }
}
