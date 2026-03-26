<?php

namespace Database\Factories;

use App\Domains\Currency\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
class CurrencyFactory extends Factory
{
    protected $model = Currency::class;

    public function definition(): array
    {
        return [
            'code'          => fake()->unique()->currencyCode(),
            'name'          => fake()->word(),
            'symbol'        => fake()->randomElement(['$', '€', '£', '¥', '₹', 'Fr', 'kr']),
            'exchange_rate' => fake()->randomFloat(6, 0.5, 2.0),
            'is_base'       => false,
        ];
    }

    public function base(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_base'       => true,
            'exchange_rate' => '1.000000',
        ]);
    }
}
