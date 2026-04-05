<?php

namespace Tests\Unit\Models;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

    // -----------------------------------------------------------------------
    // Customer model
    // -----------------------------------------------------------------------

    public function test_customer_fillable_attributes(): void
    {
        $model = new Customer();

        $this->assertSame(
            ['first_name', 'last_name', 'email', 'phone', 'password'],
            $model->getFillable(),
        );
    }

    public function test_customer_extends_authenticatable(): void
    {
        $customer = new Customer();

        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, $customer);
    }

    public function test_customer_uses_has_api_tokens_trait(): void
    {
        $this->assertContains(
            \Laravel\Sanctum\HasApiTokens::class,
            class_uses_recursive(Customer::class),
        );
    }

    public function test_customer_password_is_hidden(): void
    {
        $model = new Customer();

        $this->assertContains('password', $model->getHidden());
        $this->assertContains('remember_token', $model->getHidden());
    }

    public function test_customer_has_addresses_relationship_method(): void
    {
        $model = new Customer();

        $this->assertTrue(method_exists($model, 'addresses'));
    }

    public function test_customer_has_cart_relationship_method(): void
    {
        $model = new Customer();

        $this->assertTrue(method_exists($model, 'cart'));
    }

    public function test_customer_has_orders_relationship_method(): void
    {
        $model = new Customer();

        $this->assertTrue(method_exists($model, 'orders'));
    }

    public function test_customer_has_reviews_relationship_method(): void
    {
        $model = new Customer();

        $this->assertTrue(method_exists($model, 'reviews'));
    }

    public function test_customer_casts_returns_array(): void
    {
        $model = new Customer();

        // The protected casts() method is called internally by Eloquent.
        // We verify it via the public getCasts() proxy which merges all casts.
        $this->assertIsArray($model->getCasts());
    }

    // -----------------------------------------------------------------------
    // CustomerAddress model
    // -----------------------------------------------------------------------

    public function test_customer_address_fillable_attributes(): void
    {
        $model = new CustomerAddress();

        $this->assertSame(
            ['customer_id', 'country', 'city', 'address_line_1', 'address_line_2', 'postcode'],
            $model->getFillable(),
        );
    }

    public function test_customer_address_has_customer_relationship_method(): void
    {
        $model = new CustomerAddress();

        $this->assertTrue(method_exists($model, 'customer'));
    }

    public function test_customer_address_casts_returns_array(): void
    {
        $model = new CustomerAddress();

        $this->assertIsArray($model->getCasts());
    }

    // -----------------------------------------------------------------------
    // Relationship type assertions
    // -----------------------------------------------------------------------

    public function test_customer_addresses_relationship_is_has_many(): void
    {
        $customer = new Customer();
        $relation  = $customer->addresses();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(CustomerAddress::class, $relation->getRelated());
    }

    public function test_customer_orders_relationship_is_has_many(): void
    {
        $customer = new Customer();
        $relation  = $customer->orders();

        $this->assertInstanceOf(HasMany::class, $relation);
    }

    public function test_customer_reviews_relationship_is_has_many(): void
    {
        $customer = new Customer();
        $relation  = $customer->reviews();

        $this->assertInstanceOf(HasMany::class, $relation);
    }

    public function test_customer_cart_relationship_is_has_one(): void
    {
        $customer = new Customer();
        $relation  = $customer->cart();

        $this->assertInstanceOf(HasOne::class, $relation);
    }

    public function test_customer_instantiation_and_attribute_access(): void
    {
        $customer             = new Customer();
        $customer->first_name = 'Jane';
        $customer->last_name  = 'Doe';
        $customer->email      = 'jane@example.com';
        $customer->phone      = '+1234567890';

        $this->assertSame('Jane', $customer->first_name);
        $this->assertSame('Doe', $customer->last_name);
        $this->assertSame('jane@example.com', $customer->email);
        $this->assertSame('+1234567890', $customer->phone);
    }
}
