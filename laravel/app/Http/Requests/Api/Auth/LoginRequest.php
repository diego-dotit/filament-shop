<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Public endpoint — login does not require prior authentication.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for user login.
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'An email address is required.',
            'email.email'       => 'Please provide a valid email address.',
            'password.required' => 'A password is required.',
        ];
    }
}
