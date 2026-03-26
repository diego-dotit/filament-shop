<?php

namespace App\Http\Requests\Api\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Only authenticated customers may update their own profile.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for updating a customer profile.
     * All fields are optional on update (PATCH semantics).
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name'  => ['sometimes', 'string', 'max:100'],
            'email'      => ['sometimes', 'email', 'max:255'],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'first_name.string' => 'First name must be a string.',
            'last_name.string'  => 'Last name must be a string.',
            'email.email'       => 'Please provide a valid email address.',
        ];
    }
}
