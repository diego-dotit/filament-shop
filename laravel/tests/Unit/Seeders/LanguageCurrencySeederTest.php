<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

/**
 * Structural tests for LanguageSeeder and CurrencySeeder.
 *
 * These tests validate class structure, constants, and idempotency patterns
 * without requiring a database connection.
 */
class LanguageCurrencySeederTest extends TestCase
{
    public function test_language_seeder_file_exists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../database/seeders/LanguageSeeder.php'
        );
    }

    public function test_currency_seeder_file_exists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../database/seeders/CurrencySeeder.php'
        );
    }

    public function test_language_seeder_contains_update_or_create_for_english(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/LanguageSeeder.php'
        );

        $this->assertStringContainsString('updateOrCreate', $contents);
        $this->assertStringContainsString("'en'", $contents);
        $this->assertStringContainsString('is_default', $contents);
    }

    public function test_language_seeder_seeds_multiple_languages(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/LanguageSeeder.php'
        );

        $this->assertStringContainsString("'es'", $contents);
        $this->assertStringContainsString("'fr'", $contents);
    }

    public function test_currency_seeder_contains_update_or_create_for_usd(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CurrencySeeder.php'
        );

        $this->assertStringContainsString('updateOrCreate', $contents);
        $this->assertStringContainsString("'USD'", $contents);
        $this->assertStringContainsString('is_base', $contents);
    }

    public function test_currency_seeder_seeds_multiple_currencies(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CurrencySeeder.php'
        );

        $this->assertStringContainsString("'EUR'", $contents);
        $this->assertStringContainsString("'GBP'", $contents);
    }

    public function test_database_seeder_calls_language_and_currency_seeders(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/DatabaseSeeder.php'
        );

        $this->assertStringContainsString('LanguageSeeder', $contents);
        $this->assertStringContainsString('CurrencySeeder', $contents);
        $this->assertStringContainsString('$this->call', $contents);
    }

    public function test_language_seeder_correct_namespace(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/LanguageSeeder.php'
        );

        $this->assertStringContainsString('namespace Database\Seeders;', $contents);
        $this->assertStringContainsString('class LanguageSeeder', $contents);
        $this->assertStringContainsString('extends Seeder', $contents);
    }

    public function test_currency_seeder_correct_namespace(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CurrencySeeder.php'
        );

        $this->assertStringContainsString('namespace Database\Seeders;', $contents);
        $this->assertStringContainsString('class CurrencySeeder', $contents);
        $this->assertStringContainsString('extends Seeder', $contents);
    }
}
