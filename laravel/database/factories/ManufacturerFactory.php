<?php

namespace Database\Factories;

use App\Domains\Manufacturer\Models\Manufacturer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Manufacturer>
 */
class ManufacturerFactory extends Factory
{
    protected $model = Manufacturer::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 99999),
        ];
    }
}
