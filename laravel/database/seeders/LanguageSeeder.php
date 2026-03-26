<?php

namespace Database\Seeders;

use App\Domains\Language\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Seed the languages table.
     *
     * Idempotent: uses updateOrCreate so re-running produces no duplicates.
     */
    public function run(): void
    {
        $languages = [
            [
                'code'       => 'en',
                'name'       => 'English',
                'is_default' => true,
            ],
            [
                'code'       => 'es',
                'name'       => 'Spanish',
                'is_default' => false,
            ],
            [
                'code'       => 'fr',
                'name'       => 'French',
                'is_default' => false,
            ],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['code' => $language['code']],
                [
                    'name'       => $language['name'],
                    'is_default' => $language['is_default'],
                ]
            );
        }
    }
}
