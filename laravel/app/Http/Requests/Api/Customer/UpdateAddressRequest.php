<?php

namespace App\Http\Requests\Api\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Only authenticated customers may update their addresses.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for updating an address (PATCH semantics).
     */
    public function rules(): array
    {
        return [
            'country'        => ['sometimes', 'string', 'max:100'],
            'city'           => ['sometimes', 'string', 'max:100'],
            'address_line_1' => ['sometimes', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'postcode'       => ['sometimes', 'string', 'max:20'],
            'phone'          => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'country.string'        => 'Country must be a string.',
            'city.string'           => 'City must be a string.',
            'address_line_1.string' => 'Address line 1 must be a string.',
            'postcode.string'       => 'Postcode must be a string.',
        ];
    }
}
