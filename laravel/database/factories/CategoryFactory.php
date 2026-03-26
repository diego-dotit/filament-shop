<?php

namespace Database\Factories;

use App\Domains\Category\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'parent_id' => null,
            'name'      => ['en' => ucwords($name)],
            'slug'      => Str::slug($name) . '-' . fake()->unique()->numberBetween(1, 99999),
            'is_active' => fake()->boolean(80),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }

    public function withParent(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Category::factory(),
        ]);
    }
}
