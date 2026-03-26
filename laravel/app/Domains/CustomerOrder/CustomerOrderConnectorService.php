<?php

namespace App\Domains\CustomerOrder;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use App\Domains\CustomerOrder\Exceptions\UnauthorizedAddressException;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderAddress;

class CustomerOrderConnectorService
{
    /**
     * Attach a customer and their address snapshots to an order.
     *
     * Validates that both addresses belong to the given customer, then creates
     * immutable OrderAddress snapshot records and saves them onto the order.
     *
     * @throws UnauthorizedAddressException
     */
    public function attachCustomerAndAddressesToOrder(
        Order $order,
        Customer $customer,
        CustomerAddress $billingAddress,
        CustomerAddress $shippingAddress,
    ): Order {
        if ($billingAddress->customer_id !== $customer->id) {
            throw new UnauthorizedAddressException();
        }

        if ($shippingAddress->customer_id !== $customer->id) {
            throw new UnauthorizedAddressException();
        }

        $order->customer_id = $customer->id;

        $billingSnapshot = new OrderAddress([
            'type'                => 'billing',
            'customer_address_id' => null,
            'country'             => $billingAddress->country,
            'city'                => $billingAddress->city,
            'address_line_1'      => $billingAddress->address_line_1,
            'address_line_2'      => $billingAddress->address_line_2,
            'postcode'            => $billingAddress->postcode,
        ]);

        $shippingSnapshot = new OrderAddress([
            'type'                => 'shipping',
            'customer_address_id' => null,
            'country'             => $shippingAddress->country,
            'city'                => $shippingAddress->city,
            'address_line_1'      => $shippingAddress->address_line_1,
            'address_line_2'      => $shippingAddress->address_line_2,
            'postcode'            => $shippingAddress->postcode,
        ]);

        $order->addresses()->saveMany([$billingSnapshot, $shippingSnapshot]);

        return $order->load('addresses');
    }
}
