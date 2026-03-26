<?php

namespace Tests\Unit\Seeders;

use PHPUnit\Framework\TestCase;

/**
 * Structural tests for CustomerSeeder.
 *
 * These tests validate class structure and idempotency patterns
 * without requiring a database connection.
 */
class CustomerSeederTest extends TestCase
{
    private string $seederPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seederPath = __DIR__ . '/../../../database/seeders/CustomerSeeder.php';
    }

    public function test_customer_seeder_file_exists(): void
    {
        $this->assertFileExists($this->seederPath);
    }

    public function test_customer_seeder_correct_namespace_and_class(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('namespace Database\Seeders;', $contents);
        $this->assertStringContainsString('class CustomerSeeder', $contents);
        $this->assertStringContainsString('extends Seeder', $contents);
    }

    public function test_customer_seeder_has_run_method(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('public function run()', $contents);
    }

    public function test_customer_seeder_uses_update_or_create_for_idempotency(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('updateOrCreate', $contents);
    }

    public function test_customer_seeder_uses_test_email(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('test@example.com', $contents);
    }

    public function test_customer_seeder_hashes_password(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('Hash::make', $contents);
    }

    public function test_customer_seeder_creates_customer_record(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('first_name', $contents);
        $this->assertStringContainsString('last_name', $contents);
        $this->assertStringContainsString('phone', $contents);
    }

    public function test_customer_seeder_creates_address(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('addresses()', $contents);
        $this->assertStringContainsString('country', $contents);
        $this->assertStringContainsString('city', $contents);
        $this->assertStringContainsString('address_line_1', $contents);
        $this->assertStringContainsString('postcode', $contents);
    }

    public function test_customer_seeder_creates_empty_cart(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('cart()', $contents);
        $this->assertStringContainsString('firstOrCreate', $contents);
    }

    public function test_customer_seeder_imports_user_model(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('use App\Models\User;', $contents);
    }

    public function test_customer_seeder_imports_customer_model(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('use App\Domains\Customer\Models\Customer;', $contents);
    }

    public function test_database_seeder_calls_customer_seeder(): void
    {
        $contents = file_get_contents(
            __DIR__ . '/../../../database/seeders/DatabaseSeeder.php'
        );

        $this->assertStringContainsString('CustomerSeeder', $contents);
    }
}
