<?php

namespace Database\Seeders;

use App\Domains\Category\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed the categories table.
     *
     * Idempotent: uses updateOrCreate so re-running produces no duplicates.
     * Creates root categories first, then child categories referencing parent IDs.
     */
    public function run(): void
    {
        // Root categories
        $rootCategories = [
            [
                'name'      => 'Electronics',
                'slug'      => Str::slug('Electronics'),
                'is_active' => true,
            ],
            [
                'name'      => 'Clothing',
                'slug'      => Str::slug('Clothing'),
                'is_active' => true,
            ],
            [
                'name'      => 'Home & Garden',
                'slug'      => Str::slug('Home & Garden'),
                'is_active' => true,
            ],
        ];

        foreach ($rootCategories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'parent_id' => null,
                    'name'      => $category['name'],
                    'is_active' => $category['is_active'],
                ]
            );
        }

        // Child categories — created after parents exist
        $electronics = Category::where('slug', Str::slug('Electronics'))->first();

        $childCategories = [
            [
                'parent_id' => $electronics?->id,
                'name'      => 'Mobile Phones',
                'slug'      => Str::slug('Mobile Phones'),
                'is_active' => true,
            ],
            [
                'parent_id' => $electronics?->id,
                'name'      => 'Laptops',
                'slug'      => Str::slug('Laptops'),
                'is_active' => true,
            ],
        ];

        foreach ($childCategories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'parent_id' => $category['parent_id'],
                    'name'      => $category['name'],
                    'is_active' => $category['is_active'],
                ]
            );
        }
    }
}
