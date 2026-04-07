<?php

namespace Tests\Feature\Api\General;

use App\Domains\Language\Models\Language;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear default Accept-Language header injected by Symfony test client
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createLanguage(string $code, bool $isDefault = false): Language
    {
        return Language::create([
            'code'       => $code,
            'name'       => strtoupper($code),
            'is_default' => $isDefault,
        ]);
    }

    private function configureSettings(array $overrides = []): void
    {
        tap(app(GeneralSettings::class), function (GeneralSettings $s) use ($overrides) {
            foreach ($overrides as $key => $value) {
                $s->{$key} = $value;
            }
        })->save();
    }

    // ── GET /api/settings — Basic ─────────────────────────────────────────────

    public function test_settings_endpoint_returns_200(): void
    {
        $this->createLanguage('en', true);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200);
    }

    public function test_settings_response_is_a_single_object_not_paginated(): void
    {
        $this->createLanguage('en', true);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200);

        $json = $response->json();

        // Must NOT have pagination wrapper keys
        $this->assertArrayNotHasKey('data', $json, 'Response must be a flat object, not paginated with data key');
        $this->assertArrayNotHasKey('meta', $json, 'Response must be a flat object, not paginated with meta key');
        $this->assertArrayNotHasKey('links', $json, 'Response must be a flat object, not paginated with links key');
    }

    public function test_settings_response_includes_all_required_fields(): void
    {
        $this->createLanguage('en', true);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'site_title',
                'meta_title',
                'meta_description',
                'meta_keywords',
                'is_open',
                'logo',
                'favicon',
            ]);
    }

    // ── is_open — boolean ─────────────────────────────────────────────────────

    public function test_is_open_is_boolean_true_by_default(): void
    {
        $this->createLanguage('en', true);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200);
        $this->assertTrue($response->json('is_open'), 'is_open must be boolean true by default');
    }

    public function test_is_open_is_boolean_false_when_set(): void
    {
        $this->createLanguage('en', true);
        $this->configureSettings(['is_open' => false]);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200);
        $value = $response->json('is_open');
        $this->assertFalse($value, 'is_open must be boolean false');
        $this->assertIsBool($value, 'is_open must be a boolean, not a string');
    }

    // ── logo and favicon — nullable ───────────────────────────────────────────

    public function test_logo_and_favicon_are_null_when_not_set(): void
    {
        $this->createLanguage('en', true);
        // Defaults: logo = null, favicon = null
        $this->configureSettings(['logo' => null, 'favicon' => null]);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJsonPath('logo', null)
            ->assertJsonPath('favicon', null);
    }

    public function test_logo_is_a_url_string_when_set(): void
    {
        $this->createLanguage('en', true);
        Storage::fake('public');

        $this->configureSettings(['logo' => 'logos/logo.png']);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200);
        $logo = $response->json('logo');
        $this->assertIsString($logo, 'logo must be a URL string when set');
        $this->assertNotEmpty($logo, 'logo must not be empty when set');
    }

    public function test_favicon_is_a_url_string_when_set(): void
    {
        $this->createLanguage('en', true);
        Storage::fake('public');

        $this->configureSettings(['favicon' => 'favicons/favicon.ico']);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200);
        $favicon = $response->json('favicon');
        $this->assertIsString($favicon, 'favicon must be a URL string when set');
        $this->assertNotEmpty($favicon, 'favicon must not be empty when set');
    }

    // ── Translatable fields — locale resolution ────────────────────────────────

    public function test_translatable_fields_are_strings_not_locale_arrays(): void
    {
        $this->createLanguage('en', true);
        $this->configureSettings([
            'site_title'       => ['en' => 'My Shop'],
            'meta_title'       => ['en' => 'Meta Title'],
            'meta_description' => ['en' => 'Meta Description'],
            'meta_keywords'    => ['en' => 'keyword1, keyword2'],
        ]);

        $response = $this->getJson('/api/settings');

        $response->assertStatus(200);

        foreach (['site_title', 'meta_title', 'meta_description', 'meta_keywords'] as $field) {
            $value = $response->json($field);
            $this->assertIsString($value, "{$field} must be a string, not an array");
        }
    }

    public function test_translatable_fields_use_default_locale_when_no_accept_language(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);

        $this->configureSettings([
            'site_title' => ['en' => 'My Shop', 'fr' => 'Ma Boutique'],
            'meta_title' => ['en' => 'English Meta', 'fr' => 'Méta Française'],
        ]);

        // No Accept-Language header — should use default language (en)
        $response = $this->getJson('/api/settings');

        $response->assertStatus(200)
            ->assertJsonPath('site_title', 'My Shop')
            ->assertJsonPath('meta_title', 'English Meta');
    }

    public function test_translatable_fields_resolve_via_accept_language_header(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);

        $this->configureSettings([
            'site_title'       => ['en' => 'My Shop', 'fr' => 'Ma Boutique'],
            'meta_title'       => ['en' => 'English Meta', 'fr' => 'Méta Française'],
            'meta_description' => ['en' => 'English Desc', 'fr' => 'Description Française'],
            'meta_keywords'    => ['en' => 'shop', 'fr' => 'boutique'],
        ]);

        $response = $this->getJson('/api/settings', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('site_title', 'Ma Boutique')
            ->assertJsonPath('meta_title', 'Méta Française')
            ->assertJsonPath('meta_description', 'Description Française')
            ->assertJsonPath('meta_keywords', 'boutique');
    }

    public function test_translatable_fields_fallback_to_default_locale_for_unknown_language(): void
    {
        $this->createLanguage('en', true);

        $this->configureSettings([
            'site_title' => ['en' => 'My Shop'],
        ]);

        // Request with unknown language — should fall back to default
        $response = $this->getJson('/api/settings', ['Accept-Language' => 'de']);

        $response->assertStatus(200)
            ->assertJsonPath('site_title', 'My Shop');
    }
}
