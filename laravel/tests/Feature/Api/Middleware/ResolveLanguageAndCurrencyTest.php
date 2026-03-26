<?php

namespace Tests\Feature\Api\Middleware;

use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ResolveLanguageAndCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Symfony's Request::create() injects a default `Accept-Language: en-us,en;q=0.5`
        // when no server variables override it. Clear it so tests are not polluted and each
        // test explicitly controls which header (if any) is present.
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);

        // Register a test route that returns resolved lang and currency from request attributes
        Route::middleware(['api'])->get('/test-middleware', function () {
            $lang     = request()->attributes->get('lang');
            $currency = request()->attributes->get('currency');

            return response()->json([
                'lang'     => $lang ? ['id' => $lang->id, 'code' => $lang->code] : null,
                'currency' => $currency ? ['id' => $currency->id, 'code' => $currency->code] : null,
            ]);
        });
    }

    // ── Fallback to database defaults ────────────────────────────────────────

    public function test_falls_back_to_default_language_and_base_currency_when_no_headers_or_params(): void
    {
        $language = Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $currency = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);

        $response = $this->getJson('/test-middleware');

        $response->assertStatus(200)
            ->assertJson([
                'lang'     => ['id' => $language->id, 'code' => 'en'],
                'currency' => ['id' => $currency->id, 'code' => 'USD'],
            ]);
    }

    // ── Accept-Language header resolution ────────────────────────────────────

    public function test_resolves_language_from_accept_language_header(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $french   = Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);

        $response = $this->getJson('/test-middleware', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJson([
                'lang' => ['id' => $french->id, 'code' => 'fr'],
            ]);
    }

    public function test_resolves_language_from_accept_language_header_with_quality_value(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $german  = Language::create(['code' => 'de', 'name' => 'German', 'is_default' => false]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);

        $response = $this->getJson('/test-middleware', ['Accept-Language' => 'de;q=0.9,en;q=0.8']);

        $response->assertStatus(200)
            ->assertJson([
                'lang' => ['id' => $german->id, 'code' => 'de'],
            ]);
    }

    public function test_resolves_language_from_accept_language_header_with_region_code(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $spanish = Language::create(['code' => 'es', 'name' => 'Spanish', 'is_default' => false]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);

        // "es-MX" should resolve to "es"
        $response = $this->getJson('/test-middleware', ['Accept-Language' => 'es-MX']);

        $response->assertStatus(200)
            ->assertJson([
                'lang' => ['id' => $spanish->id, 'code' => 'es'],
            ]);
    }

    public function test_falls_back_to_default_language_when_accept_language_not_found_in_db(): void
    {
        $english  = Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);

        $response = $this->getJson('/test-middleware', ['Accept-Language' => 'xx']);

        $response->assertStatus(200)
            ->assertJson([
                'lang' => ['id' => $english->id, 'code' => 'en'],
            ]);
    }

    // ── ?lang= query param resolution ────────────────────────────────────────

    public function test_resolves_language_from_query_param(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $french   = Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);

        $response = $this->getJson('/test-middleware?lang=fr');

        $response->assertStatus(200)
            ->assertJson([
                'lang' => ['id' => $french->id, 'code' => 'fr'],
            ]);
    }

    public function test_accept_language_header_takes_priority_over_query_param(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $french  = Language::create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);
        Language::create(['code' => 'de', 'name' => 'German', 'is_default' => false]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);

        // Header = fr, query param = de → header wins
        $response = $this->getJson('/test-middleware?lang=de', ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJson([
                'lang' => ['id' => $french->id, 'code' => 'fr'],
            ]);
    }

    // ── Accept-Currency header resolution ────────────────────────────────────

    public function test_resolves_currency_from_accept_currency_header(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);
        $eur = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.920000', 'is_base' => false]);

        $response = $this->getJson('/test-middleware', ['Accept-Currency' => 'EUR']);

        $response->assertStatus(200)
            ->assertJson([
                'currency' => ['id' => $eur->id, 'code' => 'EUR'],
            ]);
    }

    public function test_falls_back_to_base_currency_when_accept_currency_not_found_in_db(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        $usd = Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);

        $response = $this->getJson('/test-middleware', ['Accept-Currency' => 'XYZ']);

        $response->assertStatus(200)
            ->assertJson([
                'currency' => ['id' => $usd->id, 'code' => 'USD'],
            ]);
    }

    // ── ?currency= query param resolution ────────────────────────────────────

    public function test_resolves_currency_from_query_param(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);
        $gbp = Currency::create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => '0.790000', 'is_base' => false]);

        $response = $this->getJson('/test-middleware?currency=GBP');

        $response->assertStatus(200)
            ->assertJson([
                'currency' => ['id' => $gbp->id, 'code' => 'GBP'],
            ]);
    }

    public function test_accept_currency_header_takes_priority_over_query_param(): void
    {
        Language::create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => '1.000000', 'is_base' => true]);
        $eur = Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'exchange_rate' => '0.920000', 'is_base' => false]);
        Currency::create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'exchange_rate' => '0.790000', 'is_base' => false]);

        // Header = EUR, query param = GBP → header wins
        $response = $this->getJson('/test-middleware?currency=GBP', ['Accept-Currency' => 'EUR']);

        $response->assertStatus(200)
            ->assertJson([
                'currency' => ['id' => $eur->id, 'code' => 'EUR'],
            ]);
    }
}
