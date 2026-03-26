<?php

namespace App\Http\Requests\Api\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    /**
     * Customer profile creation is performed immediately after registration —
     * allow authenticated and unauthenticated contexts (registration flow).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a customer profile.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:255'],
            'phone'      => ['nullable', 'string', 'max:30'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required'  => 'Last name is required.',
            'email.required'      => 'Email address is required.',
            'email.email'         => 'Please provide a valid email address.',
        ];
    }
}
