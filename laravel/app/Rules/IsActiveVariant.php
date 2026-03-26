<?php

namespace App\Rules;

use App\Domains\Product\Models\ProductVariant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class IsActiveVariant implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Fails when the given product_variant_id exists but is not active.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $variant = ProductVariant::find($value);

        if ($variant && ! $variant->is_active) {
            $fail('The selected product variant is not available.');
        }
    }
}
