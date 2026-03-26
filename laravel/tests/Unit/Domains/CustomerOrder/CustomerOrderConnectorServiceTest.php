<?php

namespace Tests\Unit\Domains\CustomerOrder;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use App\Domains\CustomerOrder\CustomerOrderConnectorService;
use App\Domains\CustomerOrder\Exceptions\UnauthorizedAddressException;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderAddress;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CustomerOrderConnectorServiceTest extends TestCase
{
    private CustomerOrderConnectorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CustomerOrderConnectorService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makeCustomer(int $id = 1): Customer
    {
        $customer = new Customer();
        $customer->setRawAttributes(['id' => $id]);

        return $customer;
    }

    private function makeAddress(int $customerId, array $fields = []): CustomerAddress
    {
        $address = new CustomerAddress();
        $address->setRawAttributes(array_merge([
            'id'             => 10,
            'customer_id'    => $customerId,
            'country'        => 'US',
            'city'           => 'New York',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'postcode'       => '10001',
        ], $fields));

        return $address;
    }

    /** @return Order&MockInterface */
    private function makeOrderMock(int $customerId = 1): Order
    {
        /** @var Order&MockInterface $order */
        $order = Mockery::mock(Order::class)->makePartial();
        $order->setRawAttributes(['id' => 99, 'customer_id' => $customerId]);

        return $order;
    }

    // -----------------------------------------------------------------------
    // Tests
    // -----------------------------------------------------------------------

    public function test_it_sets_customer_id_on_order(): void
    {
        $customer        = $this->makeCustomer(42);
        $billingAddress  = $this->makeAddress(42);
        $shippingAddress = $this->makeAddress(42, ['id' => 11]);

        $order = $this->makeOrderMock();

        $relMock = Mockery::mock(HasMany::class);
        $relMock->shouldReceive('saveMany')->once()->andReturnNull();
        $order->shouldReceive('addresses')->andReturn($relMock);
        $order->shouldReceive('load')->with('addresses')->andReturnSelf();

        $this->service->attachCustomerAndAddressesToOrder($order, $customer, $billingAddress, $shippingAddress);

        $this->assertSame(42, $order->customer_id);
    }

    public function test_it_attaches_two_addresses_via_save_many(): void
    {
        $customer        = $this->makeCustomer(1);
        $billingAddress  = $this->makeAddress(1);
        $shippingAddress = $this->makeAddress(1, ['id' => 11]);

        $order = $this->makeOrderMock();

        $capturedAddresses = [];
        $relMock           = Mockery::mock(HasMany::class);
        $relMock->shouldReceive('saveMany')
            ->once()
            ->with(Mockery::on(function (array $addresses) use (&$capturedAddresses) {
                $capturedAddresses = $addresses;

                return count($addresses) === 2;
            }))
            ->andReturnNull();
        $order->shouldReceive('addresses')->andReturn($relMock);
        $order->shouldReceive('load')->with('addresses')->andReturnSelf();

        $this->service->attachCustomerAndAddressesToOrder($order, $customer, $billingAddress, $shippingAddress);

        $this->assertCount(2, $capturedAddresses);
        $this->assertContainsOnlyInstancesOf(OrderAddress::class, $capturedAddresses);
    }

    public function test_it_captures_billing_address_snapshot_with_correct_fields(): void
    {
        $customer = $this->makeCustomer(1);
        $billing  = $this->makeAddress(1, [
            'country'        => 'GB',
            'city'           => 'London',
            'address_line_1' => '10 Downing St',
            'address_line_2' => null,
            'postcode'       => 'SW1A 2AA',
        ]);
        $shipping = $this->makeAddress(1, ['id' => 11]);

        $order = $this->makeOrderMock();

        $capturedAddresses = [];
        $relMock           = Mockery::mock(HasMany::class);
        $relMock->shouldReceive('saveMany')
            ->once()
            ->with(Mockery::on(function (array $addresses) use (&$capturedAddresses) {
                $capturedAddresses = $addresses;

                return true;
            }))
            ->andReturnNull();
        $order->shouldReceive('addresses')->andReturn($relMock);
        $order->shouldReceive('load')->with('addresses')->andReturnSelf();

        $this->service->attachCustomerAndAddressesToOrder($order, $customer, $billing, $shipping);

        /** @var OrderAddress $billingSnapshot */
        $billingSnapshot = collect($capturedAddresses)->firstWhere('type', 'billing');

        $this->assertNotNull($billingSnapshot);
        $this->assertSame('billing', $billingSnapshot->type);
        $this->assertSame('GB', $billingSnapshot->country);
        $this->assertSame('London', $billingSnapshot->city);
        $this->assertSame('10 Downing St', $billingSnapshot->address_line_1);
        $this->assertNull($billingSnapshot->address_line_2);
        $this->assertSame('SW1A 2AA', $billingSnapshot->postcode);
        $this->assertNull($billingSnapshot->customer_address_id);
    }

    public function test_it_captures_shipping_address_snapshot_with_correct_fields(): void
    {
        $customer = $this->makeCustomer(1);
        $billing  = $this->makeAddress(1);
        $shipping = $this->makeAddress(1, [
            'id'             => 11,
            'country'        => 'DE',
            'city'           => 'Berlin',
            'address_line_1' => 'Unter den Linden 1',
            'address_line_2' => 'Floor 3',
            'postcode'       => '10117',
        ]);

        $order = $this->makeOrderMock();

        $capturedAddresses = [];
        $relMock           = Mockery::mock(HasMany::class);
        $relMock->shouldReceive('saveMany')
            ->once()
            ->with(Mockery::on(function (array $addresses) use (&$capturedAddresses) {
                $capturedAddresses = $addresses;

                return true;
            }))
            ->andReturnNull();
        $order->shouldReceive('addresses')->andReturn($relMock);
        $order->shouldReceive('load')->with('addresses')->andReturnSelf();

        $this->service->attachCustomerAndAddressesToOrder($order, $customer, $billing, $shipping);

        /** @var OrderAddress $shippingSnapshot */
        $shippingSnapshot = collect($capturedAddresses)->firstWhere('type', 'shipping');

        $this->assertNotNull($shippingSnapshot);
        $this->assertSame('shipping', $shippingSnapshot->type);
        $this->assertSame('DE', $shippingSnapshot->country);
        $this->assertSame('Berlin', $shippingSnapshot->city);
        $this->assertSame('Unter den Linden 1', $shippingSnapshot->address_line_1);
        $this->assertSame('Floor 3', $shippingSnapshot->address_line_2);
        $this->assertSame('10117', $shippingSnapshot->postcode);
        $this->assertNull($shippingSnapshot->customer_address_id);
    }

    public function test_it_throws_when_billing_address_belongs_to_different_customer(): void
    {
        $customer        = $this->makeCustomer(1);
        $billingAddress  = $this->makeAddress(2); // belongs to customer 2, not 1
        $shippingAddress = $this->makeAddress(1, ['id' => 11]);

        $order = $this->makeOrderMock();

        $this->expectException(UnauthorizedAddressException::class);

        $this->service->attachCustomerAndAddressesToOrder($order, $customer, $billingAddress, $shippingAddress);
    }

    public function test_it_throws_when_shipping_address_belongs_to_different_customer(): void
    {
        $customer        = $this->makeCustomer(1);
        $billingAddress  = $this->makeAddress(1);
        $shippingAddress = $this->makeAddress(2, ['id' => 11]); // belongs to customer 2, not 1

        $order = $this->makeOrderMock();

        $this->expectException(UnauthorizedAddressException::class);

        $this->service->attachCustomerAndAddressesToOrder($order, $customer, $billingAddress, $shippingAddress);
    }

    public function test_it_returns_order_with_addresses_loaded(): void
    {
        $customer        = $this->makeCustomer(1);
        $billingAddress  = $this->makeAddress(1);
        $shippingAddress = $this->makeAddress(1, ['id' => 11]);

        $order = $this->makeOrderMock();

        $relMock = Mockery::mock(HasMany::class);
        $relMock->shouldReceive('saveMany')->once()->andReturnNull();
        $order->shouldReceive('addresses')->andReturn($relMock);
        $order->shouldReceive('load')->with('addresses')->once()->andReturnSelf();

        $result = $this->service->attachCustomerAndAddressesToOrder($order, $customer, $billingAddress, $shippingAddress);

        $this->assertSame($order, $result);
    }

    public function test_snapshots_do_not_store_customer_address_id_reference(): void
    {
        $customer        = $this->makeCustomer(1);
        $billingAddress  = $this->makeAddress(1, ['id' => 55]);
        $shippingAddress = $this->makeAddress(1, ['id' => 66]);

        $order = $this->makeOrderMock();

        $capturedAddresses = [];
        $relMock           = Mockery::mock(HasMany::class);
        $relMock->shouldReceive('saveMany')
            ->once()
            ->with(Mockery::on(function (array $addresses) use (&$capturedAddresses) {
                $capturedAddresses = $addresses;

                return true;
            }))
            ->andReturnNull();
        $order->shouldReceive('addresses')->andReturn($relMock);
        $order->shouldReceive('load')->with('addresses')->andReturnSelf();

        $this->service->attachCustomerAndAddressesToOrder($order, $customer, $billingAddress, $shippingAddress);

        foreach ($capturedAddresses as $snapshot) {
            $this->assertNull($snapshot->customer_address_id, 'Snapshots must not reference the original CustomerAddress ID');
        }
    }
}
