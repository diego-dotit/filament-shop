<?php

namespace Tests\Unit\Models;

use App\Domains\Currency\Models\Currency;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function test_currency_extends_eloquent_model(): void
    {
        $currency = new Currency();

        $this->assertInstanceOf(Model::class, $currency);
    }

    public function test_currency_has_correct_fillable_fields(): void
    {
        $currency = new Currency();

        $this->assertSame(
            ['code', 'name', 'symbol', 'exchange_rate', 'is_base'],
            $currency->getFillable()
        );
    }

    public function test_currency_casts_exchange_rate_as_decimal(): void
    {
        $currency = new Currency();
        $casts = $currency->getCasts();

        $this->assertArrayHasKey('exchange_rate', $casts);
        $this->assertSame('decimal:6', $casts['exchange_rate']);
    }

    public function test_currency_casts_is_base_as_boolean(): void
    {
        $currency = new Currency();
        $casts = $currency->getCasts();

        $this->assertArrayHasKey('is_base', $casts);
        $this->assertSame('boolean', $casts['is_base']);
    }

    public function test_is_base_is_true_when_set_to_one(): void
    {
        $currency = new Currency(['is_base' => 1]);

        $this->assertTrue($currency->is_base);
    }

    public function test_is_base_is_false_when_set_to_zero(): void
    {
        $currency = new Currency(['is_base' => 0]);

        $this->assertFalse($currency->is_base);
    }

    public function test_exchange_rate_is_cast_to_decimal_string(): void
    {
        $currency = new Currency(['exchange_rate' => 1.5]);

        // decimal:6 cast returns a string formatted to 6 decimal places
        $this->assertSame('1.500000', $currency->exchange_rate);
    }

    public function test_currency_has_no_relationships(): void
    {
        $reflection = new \ReflectionClass(Currency::class);

        // Collect public instance (non-static) methods declared on Currency itself
        $instanceMethods = array_map(
            fn(\ReflectionMethod $m) => $m->getName(),
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                fn(\ReflectionMethod $m) => $m->getDeclaringClass()->getName() === Currency::class
                    && ! $m->isStatic()
            )
        );

        $this->assertEmpty(
            $instanceMethods,
            'Currency model should define no custom public instance methods (no relationships)'
        );
    }

    public function test_currency_namespace_is_correct(): void
    {
        $this->assertSame(
            'App\Domains\Currency\Models\Currency',
            Currency::class
        );
    }
}
