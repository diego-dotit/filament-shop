<?php

namespace Database\Seeders;

use App\Domains\Manufacturer\Models\Manufacturer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ManufacturerSeeder extends Seeder
{
    /**
     * Seed the manufacturers table.
     *
     * Idempotent: uses updateOrCreate keyed on slug so re-running produces no duplicates.
     */
    public function run(): void
    {
        $manufacturers = [
            [
                'name' => 'TechBrand',
                'slug' => Str::slug('TechBrand'),
            ],
            [
                'name' => 'FashionCo',
                'slug' => Str::slug('FashionCo'),
            ],
            [
                'name' => 'HomeEssentials',
                'slug' => Str::slug('HomeEssentials'),
            ],
        ];

        foreach ($manufacturers as $manufacturer) {
            Manufacturer::updateOrCreate(
                ['slug' => $manufacturer['slug']],
                ['name' => $manufacturer['name']]
            );
        }
    }
}
