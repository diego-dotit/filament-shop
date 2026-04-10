<?php

namespace Database\Factories;

use App\Domains\Localisation\Models\City;
use App\Domains\Localisation\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'name'       => fake()->unique()->city(),
            'zone_id'    => Zone::factory(),
            'status'     => true,
            'sort_order' => 0,
            'postcode'   => fake()->postcode(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => false]);
    }
}
