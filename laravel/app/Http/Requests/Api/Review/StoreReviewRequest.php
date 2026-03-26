<?php

namespace App\Http\Requests\Api\Review;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    /**
     * Merge the {productId} route parameter into the request as product_id
     * so it can be validated along with the body fields.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_id' => $this->route('productId'),
        ]);
    }

    /**
     * Only authenticated customers may submit reviews.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules for submitting a product review.
     *
     * Business rule: one review per customer per product is enforced via
     * the unique compound index on the reviews table and the Rule::unique()
     * below. The customer_id is resolved from the authenticated user.
     */
    public function rules(): array
    {
        $customerId = auth()->check()
            ? optional(auth()->user()->customer)->id
            : null;

        $productIdRules = ['required', 'integer', 'exists:products,id'];

        if ($customerId) {
            $productIdRules[] = Rule::unique('reviews', 'product_id')
                ->where(fn ($query) => $query->where('customer_id', $customerId));
        }

        return [
            'product_id' => $productIdRules,
            'rating'     => ['required', 'integer', 'between:1,5'],
            'comment'    => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'A product must be specified.',
            'product_id.exists'   => 'The specified product does not exist.',
            'product_id.unique'   => 'You have already reviewed this product.',
            'rating.required'     => 'A rating is required.',
            'rating.between'      => 'Rating must be between 1 and 5.',
            'comment.max'         => 'Comment may not exceed 2000 characters.',
        ];
    }
}
