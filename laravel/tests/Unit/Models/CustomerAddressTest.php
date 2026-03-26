<?php

namespace Tests\Unit\Models;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PHPUnit\Framework\TestCase;

class CustomerAddressTest extends TestCase
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

    public function test_customer_address_class_exists(): void
    {
        $this->assertTrue(class_exists(CustomerAddress::class));
    }

    public function test_customer_address_can_be_instantiated(): void
    {
        $address = new CustomerAddress();

        $this->assertInstanceOf(CustomerAddress::class, $address);
    }

    public function test_customer_address_fillable_attributes(): void
    {
        $address = new CustomerAddress();

        $this->assertSame(
            ['customer_id', 'country', 'city', 'address_line_1', 'address_line_2', 'postcode'],
            $address->getFillable(),
        );
    }

    public function test_customer_address_attribute_access(): void
    {
        $address               = new CustomerAddress();
        $address->country      = 'US';
        $address->city         = 'New York';
        $address->address_line_1 = '123 Main St';
        $address->postcode     = '10001';

        $this->assertSame('US', $address->country);
        $this->assertSame('New York', $address->city);
        $this->assertSame('123 Main St', $address->address_line_1);
        $this->assertSame('10001', $address->postcode);
    }

    public function test_customer_address_customer_relationship_is_belongs_to(): void
    {
        $address  = new CustomerAddress();
        $relation = $address->customer();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Customer::class, $relation->getRelated());
    }

    public function test_customer_address_casts_returns_array(): void
    {
        $address = new CustomerAddress();

        $this->assertIsArray($address->getCasts());
    }

    public function test_customer_address_has_customer_relationship_method(): void
    {
        $address = new CustomerAddress();

        $this->assertTrue(method_exists($address, 'customer'));
    }
}
