<?php

namespace App\Http\Requests\Api\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    /**
     * Only authenticated users may update cart items.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for updating a cart item's quantity.
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity is required.',
            'quantity.integer'  => 'Quantity must be a whole number.',
            'quantity.min'      => 'Quantity must be at least 0.',
        ];
    }
}
