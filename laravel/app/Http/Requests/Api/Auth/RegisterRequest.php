<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Public endpoint — registration does not require authentication.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for user registration.
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required'     => 'Your full name is required.',
            'email.required'    => 'An email address is required.',
            'email.email'       => 'Please provide a valid email address.',
            'email.unique'      => 'This email is already registered.',
            'password.required' => 'A password is required.',
            'password.min'      => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
