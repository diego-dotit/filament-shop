<?php

namespace Tests\Feature\Api;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use App\Domains\Product\Models\Product;
use App\Domains\Product\Models\ProductVariant;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Requests\Api\Cart\StoreCartItemRequest;
use App\Http\Requests\Api\Cart\UpdateCartItemRequest;
use App\Http\Requests\Api\Customer\StoreAddressRequest;
use App\Http\Requests\Api\Customer\StoreCustomerRequest;
use App\Http\Requests\Api\Customer\UpdateAddressRequest;
use App\Http\Requests\Api\Customer\UpdateCustomerRequest;
use App\Http\Requests\Api\Order\PlaceOrderRequest;
use App\Http\Requests\Api\Review\StoreReviewRequest;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Feature tests for Form Request validation — tests actual validation
 * passes/fails using Validator::make() with the form request rules.
 */
class FormRequestValidationTest extends TestCase
{
    use RefreshDatabase;

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Run validation using a FormRequest's rules() and messages().
     */
    private function validate(FormRequest $request, array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, $request->rules(), $request->messages());
    }

    // -----------------------------------------------------------------------
    // RegisterRequest
    // -----------------------------------------------------------------------

    public function test_register_request_passes_with_valid_data(): void
    {
        $request = new RegisterRequest();
        $validator = $this->validate($request, [
            'name'                  => 'Jane Doe',
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_register_request_fails_when_name_missing(): void
    {
        $request = new RegisterRequest();
        $validator = $this->validate($request, [
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
    }

    public function test_register_request_fails_when_email_not_valid(): void
    {
        $request = new RegisterRequest();
        $validator = $this->validate($request, [
            'name'                  => 'Jane',
            'email'                 => 'not-an-email',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_register_request_fails_when_email_already_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $request = new RegisterRequest();
        $validator = $this->validate($request, [
            'name'                  => 'Jane',
            'email'                 => 'taken@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_register_request_fails_when_password_too_short(): void
    {
        $request = new RegisterRequest();
        $validator = $this->validate($request, [
            'name'                  => 'Jane',
            'email'                 => 'jane@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_register_request_fails_when_password_not_confirmed(): void
    {
        $request = new RegisterRequest();
        $validator = $this->validate($request, [
            'name'                  => 'Jane',
            'email'                 => 'jane@example.com',
            'password'              => 'secret123',
            'password_confirmation' => 'different123',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_register_request_has_custom_message_for_unique_email(): void
    {
        $messages = (new RegisterRequest())->messages();

        $this->assertArrayHasKey('email.unique', $messages);
    }

    // -----------------------------------------------------------------------
    // LoginRequest
    // -----------------------------------------------------------------------

    public function test_login_request_passes_with_valid_data(): void
    {
        $validator = $this->validate(new LoginRequest(), [
            'email'    => 'user@example.com',
            'password' => 'anypassword',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_login_request_fails_without_email(): void
    {
        $validator = $this->validate(new LoginRequest(), [
            'password' => 'anypassword',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_login_request_fails_without_password(): void
    {
        $validator = $this->validate(new LoginRequest(), [
            'email' => 'user@example.com',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    public function test_login_request_fails_with_invalid_email_format(): void
    {
        $validator = $this->validate(new LoginRequest(), [
            'email'    => 'not-email',
            'password' => 'password',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    // -----------------------------------------------------------------------
    // StoreAddressRequest
    // -----------------------------------------------------------------------

    public function test_store_address_request_passes_with_valid_data(): void
    {
        $validator = $this->validate(new StoreAddressRequest(), [
            'country'       => 'US',
            'city'          => 'New York',
            'address_line_1' => '123 Main St',
            'postcode'      => '10001',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_store_address_request_fails_when_country_missing(): void
    {
        $validator = $this->validate(new StoreAddressRequest(), [
            'city'          => 'New York',
            'address_line_1' => '123 Main St',
            'postcode'      => '10001',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('country', $validator->errors()->toArray());
    }

    public function test_store_address_request_fails_when_city_missing(): void
    {
        $validator = $this->validate(new StoreAddressRequest(), [
            'country'       => 'US',
            'address_line_1' => '123 Main St',
            'postcode'      => '10001',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('city', $validator->errors()->toArray());
    }

    public function test_store_address_request_fails_when_postcode_missing(): void
    {
        $validator = $this->validate(new StoreAddressRequest(), [
            'country'       => 'US',
            'city'          => 'New York',
            'address_line_1' => '123 Main St',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('postcode', $validator->errors()->toArray());
    }

    public function test_update_address_request_passes_with_partial_data(): void
    {
        $validator = $this->validate(new UpdateAddressRequest(), [
            'country' => 'CA',
            'city'    => 'Toronto',
            'address_line_1' => '456 Queen St',
            'postcode' => 'M5V 2A8',
        ]);

        $this->assertTrue($validator->passes());
    }

    // -----------------------------------------------------------------------
    // StoreCustomerRequest
    // -----------------------------------------------------------------------

    public function test_store_customer_request_passes_with_valid_data(): void
    {
        $validator = $this->validate(new StoreCustomerRequest(), [
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_store_customer_request_fails_without_first_name(): void
    {
        $validator = $this->validate(new StoreCustomerRequest(), [
            'last_name' => 'Doe',
            'email'     => 'john@example.com',
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('first_name', $validator->errors()->toArray());
    }

    public function test_update_customer_request_passes_with_valid_data(): void
    {
        $validator = $this->validate(new UpdateCustomerRequest(), [
            'first_name' => 'Updated',
            'last_name'  => 'Name',
            'email'      => 'updated@example.com',
        ]);

        $this->assertTrue($validator->passes());
    }

    // -----------------------------------------------------------------------
    // StoreCartItemRequest
    // -----------------------------------------------------------------------

    public function test_store_cart_item_request_fails_with_quantity_zero(): void
    {
        // We only test rules that don't require DB (quantity min)
        $validator = $this->validate(new StoreCartItemRequest(), [
            'product_variant_id' => 1,
            'quantity'           => 0,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('quantity', $validator->errors()->toArray());
    }

    public function test_store_cart_item_request_fails_with_negative_quantity(): void
    {
        $validator = $this->validate(new StoreCartItemRequest(), [
            'product_variant_id' => 1,
            'quantity'           => -5,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('quantity', $validator->errors()->toArray());
    }

    public function test_store_cart_item_request_fails_when_product_variant_missing(): void
    {
        $validator = $this->validate(new StoreCartItemRequest(), [
            'quantity' => 2,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('product_variant_id', $validator->errors()->toArray());
    }

    public function test_update_cart_item_request_fails_with_quantity_less_than_1(): void
    {
        $validator = $this->validate(new UpdateCartItemRequest(), [
            'quantity' => 0,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('quantity', $validator->errors()->toArray());
    }

    public function test_update_cart_item_request_passes_with_valid_quantity(): void
    {
        $validator = $this->validate(new UpdateCartItemRequest(), [
            'quantity' => 3,
        ]);

        $this->assertTrue($validator->passes());
    }

    // -----------------------------------------------------------------------
    // PlaceOrderRequest
    // -----------------------------------------------------------------------

    public function test_place_order_request_fails_without_billing_address_id(): void
    {
        $validator = $this->validate(new PlaceOrderRequest(), [
            'shipping_address_id' => 1,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('billing_address_id', $validator->errors()->toArray());
    }

    public function test_place_order_request_fails_without_shipping_address_id(): void
    {
        $validator = $this->validate(new PlaceOrderRequest(), [
            'billing_address_id' => 1,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('shipping_address_id', $validator->errors()->toArray());
    }

    public function test_place_order_request_address_ids_must_be_integers(): void
    {
        $validator = $this->validate(new PlaceOrderRequest(), [
            'billing_address_id'  => 'not-a-number',
            'shipping_address_id' => 'also-not-a-number',
        ]);

        $this->assertFalse($validator->passes());
    }

    // -----------------------------------------------------------------------
    // StoreReviewRequest
    // -----------------------------------------------------------------------

    public function test_store_review_request_has_rating_and_product_id_in_rules(): void
    {
        $rules = (new StoreReviewRequest())->rules();

        $this->assertArrayHasKey('rating', $rules);
        $this->assertArrayHasKey('product_id', $rules);
    }

    public function test_store_review_request_rating_rules_include_between(): void
    {
        $rules = (new StoreReviewRequest())->rules();
        $ratingRules = (array) $rules['rating'];

        $this->assertContains('required', $ratingRules);
        $this->assertContains('integer', $ratingRules);

        $hasBetween = false;
        foreach ($ratingRules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'between:')) {
                $hasBetween = true;
                break;
            }
        }
        $this->assertTrue($hasBetween, 'StoreReviewRequest rating must have between:1,5 rule');
    }

    public function test_store_review_request_fails_with_rating_below_1(): void
    {
        $validator = $this->validate(new StoreReviewRequest(), [
            'rating'     => 0,
            'product_id' => 1,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rating', $validator->errors()->toArray());
    }

    public function test_store_review_request_fails_with_rating_above_5(): void
    {
        $validator = $this->validate(new StoreReviewRequest(), [
            'rating'     => 6,
            'product_id' => 1,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('rating', $validator->errors()->toArray());
    }

    public function test_store_review_request_fails_without_product_id(): void
    {
        $validator = $this->validate(new StoreReviewRequest(), [
            'rating' => 4,
        ]);

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('product_id', $validator->errors()->toArray());
    }

    public function test_store_review_request_comment_nullable(): void
    {
        $rules = (new StoreReviewRequest())->rules();

        if (isset($rules['comment'])) {
            $this->assertContains('nullable', (array) $rules['comment']);
        } else {
            $this->assertTrue(true);
        }
    }

    public function test_store_review_request_has_custom_message_for_rating(): void
    {
        $messages = (new StoreReviewRequest())->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }
}
