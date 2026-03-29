<?php

namespace Tests\Feature\Api\Product;

use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use App\Domains\Product\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * T3.8 — Confirms GET /api/products/electronics returns 200
 * with `locale` (string) and `slugs` (array of {locale, slug}) fields.
 */
class ProductEndpointLocaleSlugTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
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

    private function createCurrency(string $code, float $rate = 1.0, bool $isBase = false): Currency
    {
        return Currency::create([
            'code'          => $code,
            'name'          => $code,
            'symbol'        => $code,
            'exchange_rate' => $rate,
            'is_base'       => $isBase,
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_electronics_endpoint_returns_200(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        Product::create([
            'name'        => ['en' => 'Electronics'],
            'slug'        => 'electronics',
            'description' => ['en' => 'Electronics category hub'],
            'is_active'   => true,
        ]);

        $response = $this->getJson('/api/products/electronics');

        $response->assertStatus(200);
    }

    public function test_electronics_endpoint_response_contains_locale_field(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        Product::create([
            'name'        => ['en' => 'Electronics'],
            'slug'        => 'electronics',
            'description' => ['en' => 'Electronics category hub'],
            'is_active'   => true,
        ]);

        $response = $this->getJson('/api/products/electronics');

        $response->assertStatus(200);
        $locale = $response->json('data.locale');
        $this->assertIsString($locale, 'data.locale must be a string');
        $this->assertNotEmpty($locale, 'data.locale must not be empty');
    }

    public function test_electronics_endpoint_response_contains_slugs_field(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        Product::create([
            'name'        => ['en' => 'Electronics'],
            'slug'        => 'electronics',
            'description' => ['en' => 'Electronics category hub'],
            'is_active'   => true,
        ]);

        $response = $this->getJson('/api/products/electronics');

        $response->assertStatus(200);
        $slugs = $response->json('data.slugs');
        $this->assertIsArray($slugs, 'data.slugs must be an array');
    }

    public function test_electronics_endpoint_slugs_entries_have_locale_and_slug_keys(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('es', false);
        $this->createCurrency('USD', 1.0, true);

        Product::create([
            'name'        => ['en' => 'Electronics', 'es' => 'Electrónica'],
            'slug'        => 'electronics',
            'description' => ['en' => 'Electronics hub', 'es' => 'Centro electrónica'],
            'is_active'   => true,
        ]);

        $response = $this->getJson('/api/products/electronics');

        $response->assertStatus(200);
        $slugs = $response->json('data.slugs');
        $this->assertIsArray($slugs, 'data.slugs must be an array');
        $this->assertNotEmpty($slugs, 'data.slugs must contain at least one entry');

        foreach ($slugs as $entry) {
            $this->assertArrayHasKey('locale', $entry, 'each slug entry must have a locale key');
            $this->assertArrayHasKey('slug', $entry, 'each slug entry must have a slug key');
            $this->assertIsString($entry['locale'], 'slug locale must be a string');
            $this->assertIsString($entry['slug'], 'slug value must be a string');
        }
    }

    public function test_electronics_endpoint_response_json_is_valid(): void
    {
        $this->createLanguage('en', true);
        $this->createCurrency('USD', 1.0, true);

        Product::create([
            'name'        => ['en' => 'Electronics'],
            'slug'        => 'electronics',
            'description' => ['en' => 'Electronics category hub'],
            'is_active'   => true,
        ]);

        $response = $this->getJson('/api/products/electronics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'slug',
                'name',
                'description',
                'is_active',
                'locale',
                'slugs',
            ],
        ]);
    }
}
