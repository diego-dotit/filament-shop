<?php

namespace App\Http\Requests\Api\Order;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Only authenticated customers may place orders.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for placing an order.
     *
     * Both address IDs must reference rows in customer_addresses.
     * Deep ownership validation (does this address belong to this customer?)
     * is enforced in the controller/service layer where the authenticated
     * customer context is available.
     */
    public function rules(): array
    {
        return [
            'billing_address_id'  => ['required', 'integer', 'exists:customer_addresses,id'],
            'shipping_address_id' => ['required', 'integer', 'exists:customer_addresses,id'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'billing_address_id.required'  => 'A billing address is required.',
            'billing_address_id.exists'    => 'The selected billing address does not exist.',
            'shipping_address_id.required' => 'A shipping address is required.',
            'shipping_address_id.exists'   => 'The selected shipping address does not exist.',
        ];
    }
}
