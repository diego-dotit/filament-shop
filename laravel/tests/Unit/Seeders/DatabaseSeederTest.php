<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

/**
 * Structural tests for DatabaseSeeder.
 *
 * These tests validate class structure and separation of concerns
 * without requiring a database connection.
 */
class DatabaseSeederTest extends TestCase
{
    private string $seederPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seederPath = __DIR__ . '/../../../database/seeders/DatabaseSeeder.php';
    }

    public function test_database_seeder_file_exists(): void
    {
        $this->assertFileExists($this->seederPath);
    }

    public function test_database_seeder_creates_admin_user_with_correct_email(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('admin@admin.ro', $contents);
    }

    public function test_database_seeder_uses_first_or_create_for_idempotency(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('firstOrCreate', $contents);
    }

    public function test_database_seeder_does_not_use_factory_create_for_user(): void
    {
        $contents = file_get_contents($this->seederPath);

        // Should not use factory()->create() for user seeding (not idempotent)
        $this->assertStringNotContainsString('factory()->create', $contents);
    }

    public function test_database_seeder_creates_admin_user_with_correct_name(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('Admin User', $contents);
    }

    public function test_database_seeder_calls_customer_seeder(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('CustomerSeeder::class', $contents);
    }

    public function test_database_seeder_calls_language_seeder(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('LanguageSeeder::class', $contents);
    }

    public function test_database_seeder_calls_currency_seeder(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('CurrencySeeder::class', $contents);
    }

    public function test_database_seeder_calls_category_seeder(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('CategorySeeder::class', $contents);
    }

    public function test_database_seeder_calls_order_seeder(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('OrderSeeder::class', $contents);
    }

    public function test_database_seeder_does_not_inline_customer_data(): void
    {
        $contents = file_get_contents($this->seederPath);

        // Should not contain customer-specific fields directly in DatabaseSeeder
        $this->assertStringNotContainsString('Customer::create', $contents);
        $this->assertStringNotContainsString('first_name', $contents);
        $this->assertStringNotContainsString('last_name', $contents);
    }

    public function test_database_seeder_does_not_assign_unused_user_variable(): void
    {
        $contents = file_get_contents($this->seederPath);

        // Should not have "$user = " (unused variable) — use firstOrCreate without assignment
        $this->assertStringNotContainsString('$user = ', $contents);
    }
}
