<?php

namespace App\Http\Requests\Api\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRegisterRequest extends FormRequest
{
    /**
     * Public endpoint — customer registration does not require authentication.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for customer registration.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:customers,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'first_name.string' => 'First name must be a string.',
            'first_name.max' => 'First name may not exceed 100 characters.',
            'last_name.required' => 'Last name is required.',
            'last_name.string' => 'Last name must be a string.',
            'last_name.max' => 'Last name may not exceed 100 characters.',
            'email.required' => 'An email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'A password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
