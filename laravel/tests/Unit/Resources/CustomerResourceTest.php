<?php

namespace Tests\Unit\Resources;

use App\Domains\Customer\Models\Customer;
use App\Http\Resources\Api\Customer\CustomerResource;
use Illuminate\Http\Request;
use Tests\TestCase;

class CustomerResourceTest extends TestCase
{
    public function test_customer_resource_has_expected_keys(): void
    {
        $customer = new Customer();
        $customer->setRawAttributes([
            'id'         => 1,
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane@example.com',
            'phone'      => '+1234567890',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-02 00:00:00',
        ]);

        $resource = new CustomerResource($customer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('first_name', $data);
        $this->assertArrayHasKey('last_name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('phone', $data);
        $this->assertArrayHasKey('created_at', $data);
        $this->assertArrayHasKey('updated_at', $data);
    }

    public function test_customer_resource_maps_values_correctly(): void
    {
        $customer = new Customer();
        $customer->setRawAttributes([
            'id'         => 42,
            'first_name' => 'John',
            'last_name'  => 'Smith',
            'email'      => 'john@test.com',
            'phone'      => '+9876543210',
            'created_at' => '2024-06-01 10:00:00',
            'updated_at' => '2024-06-02 11:00:00',
        ]);

        $resource = new CustomerResource($customer);
        $data = $resource->toArray(Request::create('/'));

        $this->assertSame(42, $data['id']);
        $this->assertSame('John', $data['first_name']);
        $this->assertSame('Smith', $data['last_name']);
        $this->assertSame('john@test.com', $data['email']);
        $this->assertSame('+9876543210', $data['phone']);
    }
}
