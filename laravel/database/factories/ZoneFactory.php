<?php

namespace Database\Factories;

use App\Domains\Localisation\Models\Country;
use App\Domains\Localisation\Models\Zone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Zone>
 */
class ZoneFactory extends Factory
{
    protected $model = Zone::class;

    public function definition(): array
    {
        return [
            'name'       => fake()->unique()->word(),
            'code'       => strtoupper(fake()->unique()->lexify('???')),
            'country_id' => Country::factory(),
            'status'     => true,
            'sort_order' => 0,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => false]);
    }
}
