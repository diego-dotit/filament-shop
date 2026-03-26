<?php

namespace Tests\Unit\Requests;

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
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Form Request classes – verifies rules() and messages()
 * structure without requiring a database connection.
 */
class FormRequestRulesTest extends TestCase
{
    // -----------------------------------------------------------------------
    // RegisterRequest
    // -----------------------------------------------------------------------

    public function test_register_request_rules_contain_required_fields(): void
    {
        $request = new RegisterRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
    }

    public function test_register_request_name_is_required(): void
    {
        $rules = (new RegisterRequest())->rules();

        $this->assertContains('required', (array) $rules['name']);
    }

    public function test_register_request_email_rules_include_required_email_and_unique(): void
    {
        $rules = (new RegisterRequest())->rules();
        $emailRules = (array) $rules['email'];

        $this->assertContains('required', $emailRules);
        $this->assertContains('email', $emailRules);

        // unique rule is present (may be string or Rule object)
        $hasUnique = false;
        foreach ($emailRules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'unique:')) {
                $hasUnique = true;
                break;
            }
        }
        $this->assertTrue($hasUnique, 'RegisterRequest email must have unique rule');
    }

    public function test_register_request_password_rules_include_min_and_confirmed(): void
    {
        $rules = (new RegisterRequest())->rules();
        $passwordRules = (array) $rules['password'];

        $this->assertContains('required', $passwordRules);
        $this->assertContains('confirmed', $passwordRules);

        $hasMin = false;
        foreach ($passwordRules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'min:')) {
                $hasMin = true;
                break;
            }
        }
        $this->assertTrue($hasMin, 'RegisterRequest password must have min rule');
    }

    public function test_register_request_has_custom_messages(): void
    {
        $messages = (new RegisterRequest())->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }

    public function test_register_request_authorize_returns_true(): void
    {
        $this->assertTrue((new RegisterRequest())->authorize());
    }

    // -----------------------------------------------------------------------
    // LoginRequest
    // -----------------------------------------------------------------------

    public function test_login_request_rules_contain_email_and_password(): void
    {
        $rules = (new LoginRequest())->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
    }

    public function test_login_request_email_is_required_and_email_format(): void
    {
        $rules = (new LoginRequest())->rules();
        $emailRules = (array) $rules['email'];

        $this->assertContains('required', $emailRules);
        $this->assertContains('email', $emailRules);
    }

    public function test_login_request_password_is_required(): void
    {
        $rules = (new LoginRequest())->rules();

        $this->assertContains('required', (array) $rules['password']);
    }

    public function test_login_request_authorize_returns_true(): void
    {
        $this->assertTrue((new LoginRequest())->authorize());
    }

    public function test_login_request_has_custom_messages(): void
    {
        $messages = (new LoginRequest())->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }

    // -----------------------------------------------------------------------
    // StoreCustomerRequest
    // -----------------------------------------------------------------------

    public function test_store_customer_request_has_first_name_last_name_email(): void
    {
        $rules = (new StoreCustomerRequest())->rules();

        $this->assertArrayHasKey('first_name', $rules);
        $this->assertArrayHasKey('last_name', $rules);
        $this->assertArrayHasKey('email', $rules);
    }

    public function test_store_customer_request_authorize_returns_true(): void
    {
        $this->assertTrue((new StoreCustomerRequest())->authorize());
    }

    // -----------------------------------------------------------------------
    // UpdateCustomerRequest
    // -----------------------------------------------------------------------

    public function test_update_customer_request_has_optional_fields(): void
    {
        $rules = (new UpdateCustomerRequest())->rules();

        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
    }

    // -----------------------------------------------------------------------
    // StoreAddressRequest
    // -----------------------------------------------------------------------

    public function test_store_address_request_has_required_fields(): void
    {
        $rules = (new StoreAddressRequest())->rules();

        $this->assertArrayHasKey('country', $rules);
        $this->assertArrayHasKey('city', $rules);
        $this->assertArrayHasKey('address_line_1', $rules);
        $this->assertArrayHasKey('postcode', $rules);
    }

    public function test_store_address_request_country_is_required(): void
    {
        $rules = (new StoreAddressRequest())->rules();

        $this->assertContains('required', (array) $rules['country']);
    }

    public function test_store_address_request_city_is_required(): void
    {
        $rules = (new StoreAddressRequest())->rules();

        $this->assertContains('required', (array) $rules['city']);
    }

    public function test_store_address_request_address_line_1_is_required(): void
    {
        $rules = (new StoreAddressRequest())->rules();

        $this->assertContains('required', (array) $rules['address_line_1']);
    }

    public function test_store_address_request_postcode_is_required(): void
    {
        $rules = (new StoreAddressRequest())->rules();

        $this->assertContains('required', (array) $rules['postcode']);
    }

    public function test_store_address_request_has_custom_messages(): void
    {
        $messages = (new StoreAddressRequest())->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }

    // -----------------------------------------------------------------------
    // UpdateAddressRequest
    // -----------------------------------------------------------------------

    public function test_update_address_request_has_address_fields(): void
    {
        $rules = (new UpdateAddressRequest())->rules();

        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
        $this->assertArrayHasKey('country', $rules);
    }

    // -----------------------------------------------------------------------
    // StoreCartItemRequest
    // -----------------------------------------------------------------------

    public function test_store_cart_item_request_has_product_variant_id_and_quantity(): void
    {
        $rules = (new StoreCartItemRequest())->rules();

        $this->assertArrayHasKey('product_variant_id', $rules);
        $this->assertArrayHasKey('quantity', $rules);
    }

    public function test_store_cart_item_request_quantity_is_required_integer_min_1(): void
    {
        $rules = (new StoreCartItemRequest())->rules();
        $quantityRules = (array) $rules['quantity'];

        $this->assertContains('required', $quantityRules);
        $this->assertContains('integer', $quantityRules);

        $hasMin = false;
        foreach ($quantityRules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'min:')) {
                $hasMin = true;
                break;
            }
        }
        $this->assertTrue($hasMin, 'StoreCartItemRequest quantity must have min:1 rule');
    }

    public function test_store_cart_item_request_has_custom_messages(): void
    {
        $messages = (new StoreCartItemRequest())->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }

    // -----------------------------------------------------------------------
    // UpdateCartItemRequest
    // -----------------------------------------------------------------------

    public function test_update_cart_item_request_has_quantity(): void
    {
        $rules = (new UpdateCartItemRequest())->rules();

        $this->assertArrayHasKey('quantity', $rules);
    }

    public function test_update_cart_item_request_quantity_min_1(): void
    {
        $rules = (new UpdateCartItemRequest())->rules();
        $quantityRules = (array) $rules['quantity'];

        $hasMin = false;
        foreach ($quantityRules as $rule) {
            if (is_string($rule) && str_starts_with($rule, 'min:')) {
                $hasMin = true;
                break;
            }
        }
        $this->assertTrue($hasMin, 'UpdateCartItemRequest quantity must have min:1 rule');
    }

    // -----------------------------------------------------------------------
    // PlaceOrderRequest
    // -----------------------------------------------------------------------

    public function test_place_order_request_has_billing_and_shipping_address_ids(): void
    {
        $rules = (new PlaceOrderRequest())->rules();

        $this->assertArrayHasKey('billing_address_id', $rules);
        $this->assertArrayHasKey('shipping_address_id', $rules);
    }

    public function test_place_order_request_address_ids_are_required(): void
    {
        $rules = (new PlaceOrderRequest())->rules();

        $this->assertContains('required', (array) $rules['billing_address_id']);
        $this->assertContains('required', (array) $rules['shipping_address_id']);
    }

    public function test_place_order_request_has_custom_messages(): void
    {
        $messages = (new PlaceOrderRequest())->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }

    // -----------------------------------------------------------------------
    // StoreReviewRequest
    // NOTE: rules() on StoreReviewRequest calls auth() which requires the
    // Laravel application container. Those structural rule tests live in
    // FormRequestValidationTest (Feature) which boots the application.
    // Here we only test messages() which is container-free.
    // -----------------------------------------------------------------------

    public function test_store_review_request_has_custom_messages(): void
    {
        $messages = (new StoreReviewRequest())->messages();

        $this->assertIsArray($messages);
        $this->assertNotEmpty($messages);
    }
}
