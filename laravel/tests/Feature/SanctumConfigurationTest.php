<?php

namespace Tests\Feature;

use Tests\TestCase;

class SanctumConfigurationTest extends TestCase
{
    /** @test */
    public function sanctum_config_file_exists(): void
    {
        $this->assertFileExists(config_path('sanctum.php'));
    }

    /** @test */
    public function sanctum_config_has_stateful_domains(): void
    {
        $stateful = config('sanctum.stateful');
        $this->assertIsArray($stateful);
        $this->assertNotEmpty($stateful);
    }

    /** @test */
    public function sanctum_config_has_expiration_setting(): void
    {
        // expiration key must exist (can be null for no expiry)
        $this->assertTrue(array_key_exists('expiration', config('sanctum')));
    }

    /** @test */
    public function sanctum_config_has_middleware_settings(): void
    {
        $middleware = config('sanctum.middleware');
        $this->assertIsArray($middleware);
        $this->assertArrayHasKey('authenticate_session', $middleware);
    }

    /** @test */
    public function personal_access_tokens_migration_exists(): void
    {
        $migrations = glob(database_path('migrations/*_create_personal_access_tokens_table.php'));
        $this->assertNotEmpty($migrations, 'Personal access tokens migration should exist');
    }

    /** @test */
    public function api_routes_file_exists(): void
    {
        $this->assertFileExists(base_path('routes/api.php'));
    }

    /** @test */
    public function bootstrap_app_registers_api_routes(): void
    {
        $bootstrapContent = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringContainsString('api.php', $bootstrapContent);
    }

    /** @test */
    public function env_example_has_sanctum_stateful_domains(): void
    {
        $envContent = file_get_contents(base_path('.env'));
        $this->assertStringContainsString('SANCTUM_STATEFUL_DOMAINS', $envContent);
    }
}
