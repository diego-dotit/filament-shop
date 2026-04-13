<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ReservedOrderTotalCode implements ValidationRule
{
    /**
     * Fails if the value is the reserved code "total" (case-insensitive).
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strtolower((string) $value) === 'total') {
            $fail("The code 'total' is reserved and cannot be used manually.");
        }
    }
}
