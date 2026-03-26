<?php

namespace App\Http\Requests\Api\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    /**
     * Only authenticated customers may add addresses.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for creating an address.
     */
    public function rules(): array
    {
        return [
            'country'        => ['required', 'string', 'max:100'],
            'city'           => ['required', 'string', 'max:100'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'postcode'       => ['required', 'string', 'max:20'],
            'phone'          => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'country.required'        => 'Country is required.',
            'city.required'           => 'City is required.',
            'address_line_1.required' => 'Address line 1 is required.',
            'postcode.required'       => 'Postcode is required.',
        ];
    }
}
