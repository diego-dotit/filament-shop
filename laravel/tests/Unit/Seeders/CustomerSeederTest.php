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

    public function test_customer_seeder_uses_first_or_create_for_idempotency(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('firstOrCreate', $contents);
    }

    public function test_customer_seeder_defines_five_customers(): void
    {
        $contents = file_get_contents($this->seederPath);

        // All 5 known test emails must be present
        $this->assertStringContainsString('customer1@example.com', $contents);
        $this->assertStringContainsString('customer2@example.com', $contents);
        $this->assertStringContainsString('customer3@example.com', $contents);
        $this->assertStringContainsString('customer4@example.com', $contents);
        $this->assertStringContainsString('customer5@example.com', $contents);
    }

    public function test_customer_seeder_hashes_password(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('Hash::make', $contents);
    }

    public function test_customer_seeder_sets_required_fields(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('first_name', $contents);
        $this->assertStringContainsString('last_name', $contents);
        $this->assertStringContainsString('phone', $contents);
        $this->assertStringContainsString('password', $contents);
    }

    public function test_customer_seeder_imports_hash_facade(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('use Illuminate\Support\Facades\Hash;', $contents);
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

    // T4.3: CustomerAddressFactory-based address seeding

    public function test_customer_seeder_imports_customer_address_model(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString(
            'use App\Domains\Customer\Models\CustomerAddress;',
            $contents
        );
    }

    public function test_customer_seeder_uses_customer_address_factory(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('CustomerAddress::factory()', $contents);
    }

    public function test_customer_seeder_checks_address_count_before_creating(): void
    {
        $contents = file_get_contents($this->seederPath);

        $this->assertStringContainsString('addresses()->count()', $contents);
    }

    public function test_customer_seeder_creates_multiple_addresses_per_customer(): void
    {
        $contents = file_get_contents($this->seederPath);

        // rand(1, 2) or count(rand(1, 2)) indicates 1-2 addresses are created
        $this->assertMatchesRegularExpression('/rand\s*\(\s*1\s*,\s*2\s*\)/', $contents);
    }
}
