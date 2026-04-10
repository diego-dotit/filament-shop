<?php

namespace Database\Factories;

use App\Domains\Localisation\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name'       => fake()->unique()->word(),
            'code'       => strtoupper(fake()->unique()->lexify('??')),
            'status'     => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => false]);
    }
}
