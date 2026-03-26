<?php

namespace App\Services;

use App\Domains\Currency\Models\Currency;

class CurrencyService
{
    /**
     * Convert a base price to the target currency.
     *
     * @param  float|string|null  $basePrice
     * @param  Currency|null  $currency  Currency model with exchange_rate attribute
     * @return float|null  Rounded to two decimal places, or null if price is null
     */
    public function convertPrice(float|string|null $basePrice, ?Currency $currency): ?float
    {
        if ($basePrice === null) {
            return null;
        }

        // Base currency: always return the original price unchanged
        if ($currency instanceof Currency && $currency->is_base) {
            return round((float) $basePrice, 2);
        }

        $rate = 1.0;
        if ($currency instanceof Currency) {
            $rate = (float) $currency->exchange_rate;
        }

        return round((float) $basePrice * $rate, 2);
    }
}
