<?php

namespace App\Http\Requests\Api\Cart;

use App\Rules\IsActiveVariant;
use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    /**
     * Only authenticated users may add items to their cart.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for adding an item to the cart.
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id', new IsActiveVariant()],
            'quantity'           => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'product_variant_id.required' => 'A product variant must be selected.',
            'product_variant_id.exists'   => 'The selected product variant does not exist.',
            'quantity.required'           => 'Quantity is required.',
            'quantity.integer'            => 'Quantity must be a whole number.',
            'quantity.min'                => 'Quantity must be at least 1.',
        ];
    }
}
