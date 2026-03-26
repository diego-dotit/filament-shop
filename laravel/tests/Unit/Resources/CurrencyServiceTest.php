<?php

namespace Tests\Unit\Resources;

use App\Domains\Currency\Models\Currency;
use App\Services\CurrencyService;
use PHPUnit\Framework\TestCase;

class CurrencyServiceTest extends TestCase
{
    private CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyService();
    }

    public function test_convert_price_returns_same_when_no_currency(): void
    {
        $result = $this->service->convertPrice('10.00', null);

        // Returns float (decimal number), not a string
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(10.0, $result, 0.001);
    }

    public function test_convert_price_applies_exchange_rate(): void
    {
        $currency = new Currency();
        $currency->setRawAttributes(['exchange_rate' => '2.50']);

        $result = $this->service->convertPrice('10.00', $currency);

        // 10.00 * 2.50 = 25.00 returned as float
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(25.0, $result, 0.001);
    }

    public function test_convert_price_handles_null_price(): void
    {
        $result = $this->service->convertPrice(null, null);

        $this->assertNull($result);
    }

    public function test_convert_price_decimal_precision_maintained(): void
    {
        // Precision is maintained to 2 decimal places
        $currency = new Currency();
        $currency->setRawAttributes(['exchange_rate' => '1.123456']);

        $result = $this->service->convertPrice('10.00', $currency);

        // 10.00 * 1.123456 = 11.23456 → rounded to 11.23
        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(11.23, $result, 0.001);
    }

    public function test_convert_price_with_rate_one_is_unchanged(): void
    {
        $currency = new Currency();
        $currency->setRawAttributes(['exchange_rate' => '1.000000']);

        $result = $this->service->convertPrice('99.99', $currency);

        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(99.99, $result, 0.001);
    }

    public function test_convert_price_base_currency_returns_original_price_unchanged(): void
    {
        // When is_base = true, price must be returned unchanged regardless of exchange_rate
        $currency = new Currency();
        $currency->setRawAttributes(['exchange_rate' => '1.500000', 'is_base' => true]);

        $result = $this->service->convertPrice('50.00', $currency);

        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(50.0, $result, 0.001);
    }

    public function test_convert_price_fallback_with_null_currency_uses_rate_one(): void
    {
        // No currency provided → behaves as base (rate 1.0) so price is unchanged
        $result = $this->service->convertPrice('99.00', null);

        $this->assertIsFloat($result);
        $this->assertEqualsWithDelta(99.0, $result, 0.001);
    }
}
