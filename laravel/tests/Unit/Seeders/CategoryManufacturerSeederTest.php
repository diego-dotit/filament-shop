<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

/**
 * Structural tests for CategorySeeder and ManufacturerSeeder.
 *
 * These tests validate class structure, constants, and idempotency patterns
 * without requiring a database connection.
 */
class CategoryManufacturerSeederTest extends TestCase
{
    public function test_category_seeder_file_exists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../database/seeders/CategorySeeder.php'
        );
    }

    public function test_manufacturer_seeder_file_exists(): void
    {
        $this->assertFileExists(
            __DIR__ . '/../../../database/seeders/ManufacturerSeeder.php'
        );
    }

    public function test_category_seeder_correct_namespace(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CategorySeeder.php'
        );

        $this->assertStringContainsString('namespace Database\Seeders;', $contents);
        $this->assertStringContainsString('class CategorySeeder', $contents);
        $this->assertStringContainsString('extends Seeder', $contents);
    }

    public function test_manufacturer_seeder_correct_namespace(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/ManufacturerSeeder.php'
        );

        $this->assertStringContainsString('namespace Database\Seeders;', $contents);
        $this->assertStringContainsString('class ManufacturerSeeder', $contents);
        $this->assertStringContainsString('extends Seeder', $contents);
    }

    public function test_category_seeder_uses_update_or_create(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CategorySeeder.php'
        );

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_category_seeder_contains_at_least_three_categories(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CategorySeeder.php'
        );

        $this->assertStringContainsString('Electronics', $contents);
        $this->assertStringContainsString('Clothing', $contents);
        $this->assertStringContainsString('Home', $contents);
    }

    public function test_category_seeder_contains_child_category(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CategorySeeder.php'
        );

        // Child category must reference parent_id
        $this->assertStringContainsString('parent_id', $contents);
        // There should be a child like 'Mobile Phones'
        $this->assertStringContainsString('Mobile', $contents);
    }

    public function test_category_seeder_sets_is_active(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CategorySeeder.php'
        );

        $this->assertStringContainsString('is_active', $contents);
        $this->assertStringContainsString('true', $contents);
    }

    public function test_category_seeder_uses_str_slug(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/CategorySeeder.php'
        );

        // Should use Str::slug or explicit slug values
        $this->assertStringContainsString('slug', $contents);
    }

    public function test_manufacturer_seeder_uses_update_or_create(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/ManufacturerSeeder.php'
        );

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_manufacturer_seeder_contains_at_least_two_manufacturers(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/ManufacturerSeeder.php'
        );

        $this->assertStringContainsString('TechBrand', $contents);
        $this->assertStringContainsString('FashionCo', $contents);
    }

    public function test_database_seeder_calls_category_and_manufacturer_seeders(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/DatabaseSeeder.php'
        );

        $this->assertStringContainsString('CategorySeeder', $contents);
        $this->assertStringContainsString('ManufacturerSeeder', $contents);
    }
}
