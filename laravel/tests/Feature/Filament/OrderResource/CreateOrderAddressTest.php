<?php

namespace Tests\Feature\Filament\OrderResource;

use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Tests for CreateOrder::mutateFormDataBeforeCreate address logic.
 *
 * Covers the "same as billing" copy behaviour and pending-array extraction.
 */
class CreateOrderAddressTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makePage(): CreateOrder
    {
        return new CreateOrder();
    }

    /** @param array<string, mixed> $data */
    private function callMutate(CreateOrder $page, array $data): array
    {
        $method = new ReflectionMethod(CreateOrder::class, 'mutateFormDataBeforeCreate');
        $method->setAccessible(true);

        return $method->invoke($page, $data);
    }

    /** @return array<int, array<string, mixed>> */
    private function readPending(CreateOrder $page, string $property): array
    {
        $prop = new ReflectionProperty(CreateOrder::class, $property);
        $prop->setAccessible(true);

        return $prop->getValue($page);
    }

    /** @return array<string, mixed> */
    private function sampleBilling(): array
    {
        return [
            'firstname'      => 'Alice',
            'lastname'       => 'Brown',
            'business'       => false,
            'company'        => null,
            'company_id'     => null,
            'tax_id'         => null,
            'country_id'     => 10,
            'zone_id'        => 20,
            'city_id'        => 30,
            'address_line_1' => '789 Pine Rd',
            'address_line_2' => null,
            'postcode'       => '67890',
            'shipping'       => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function sampleShipping(): array
    {
        return [
            'firstname'      => 'Bob',
            'lastname'       => 'Smith',
            'business'       => false,
            'company'        => null,
            'company_id'     => null,
            'tax_id'         => null,
            'country_id'     => 99,
            'zone_id'        => null,
            'city_id'        => null,
            'address_line_1' => '1 Different St',
            'address_line_2' => null,
            'postcode'       => '11111',
            'shipping'       => 1,
        ];
    }

    // -----------------------------------------------------------------------
    // same_as_billing = true  (copy path)
    // -----------------------------------------------------------------------

    public function test_billing_fields_copied_to_shipping_when_same_as_billing_is_on(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => true,
            'billing_addresses'  => [$this->sampleBilling()],
            'shipping_addresses' => [['shipping' => 1]],
            'items'              => [],
        ];

        $this->callMutate($page, $data);

        $pending = $this->readPending($page, 'pendingShippingAddresses');

        $this->assertSame('Alice', $pending[0]['firstname']);
        $this->assertSame('Brown', $pending[0]['lastname']);
        $this->assertSame(10, $pending[0]['country_id']);
        $this->assertSame('789 Pine Rd', $pending[0]['address_line_1']);
        $this->assertSame('67890', $pending[0]['postcode']);
    }

    public function test_shipping_flag_preserved_as_one_after_copy(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => true,
            'billing_addresses'  => [$this->sampleBilling()],
            'shipping_addresses' => [['shipping' => 1]],
            'items'              => [],
        ];

        $this->callMutate($page, $data);

        $pending = $this->readPending($page, 'pendingShippingAddresses');

        $this->assertSame(1, $pending[0]['shipping']);
    }

    public function test_shipping_billing_flag_not_leaked_into_shipping_record(): void
    {
        $page = $this->makePage();

        $billing = $this->sampleBilling(); // billing has shipping=0

        $data = [
            'same_as_billing'    => true,
            'billing_addresses'  => [$billing],
            'shipping_addresses' => [['shipping' => 1]],
            'items'              => [],
        ];

        $this->callMutate($page, $data);

        $pending = $this->readPending($page, 'pendingShippingAddresses');

        // The shipping field must be 1, not 0 (billing value must not overwrite it)
        $this->assertSame(1, $pending[0]['shipping']);
    }

    // -----------------------------------------------------------------------
    // same_as_billing = false  (no-copy path)
    // -----------------------------------------------------------------------

    public function test_shipping_not_overridden_when_same_as_billing_is_off(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => false,
            'billing_addresses'  => [$this->sampleBilling()],
            'shipping_addresses' => [$this->sampleShipping()],
            'items'              => [],
        ];

        $this->callMutate($page, $data);

        $pending = $this->readPending($page, 'pendingShippingAddresses');

        $this->assertSame('Bob', $pending[0]['firstname']);
        $this->assertSame('Smith', $pending[0]['lastname']);
        $this->assertSame(99, $pending[0]['country_id']);
    }

    // -----------------------------------------------------------------------
    // Key cleanup
    // -----------------------------------------------------------------------

    public function test_same_as_billing_key_removed_from_returned_data(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => true,
            'billing_addresses'  => [$this->sampleBilling()],
            'shipping_addresses' => [['shipping' => 1]],
            'items'              => [],
        ];

        $result = $this->callMutate($page, $data);

        $this->assertArrayNotHasKey('same_as_billing', $result);
    }

    public function test_addresses_and_items_removed_from_returned_data(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => false,
            'billing_addresses'  => [$this->sampleBilling()],
            'shipping_addresses' => [$this->sampleShipping()],
            'items'              => [['product_id' => 1, 'quantity' => 2]],
        ];

        $result = $this->callMutate($page, $data);

        $this->assertArrayNotHasKey('billing_addresses', $result);
        $this->assertArrayNotHasKey('shipping_addresses', $result);
        $this->assertArrayNotHasKey('items', $result);
    }

    // -----------------------------------------------------------------------
    // Pending arrays are stored correctly
    // -----------------------------------------------------------------------

    public function test_billing_addresses_stored_in_pending_property(): void
    {
        $page = $this->makePage();

        $billing = $this->sampleBilling();

        $data = [
            'same_as_billing'    => false,
            'billing_addresses'  => [$billing],
            'shipping_addresses' => [$this->sampleShipping()],
            'items'              => [],
        ];

        $this->callMutate($page, $data);

        $pending = $this->readPending($page, 'pendingBillingAddresses');

        $this->assertCount(1, $pending);
        $this->assertSame('Alice', $pending[0]['firstname']);
    }

    public function test_items_stored_in_pending_property(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => false,
            'billing_addresses'  => [$this->sampleBilling()],
            'shipping_addresses' => [$this->sampleShipping()],
            'items'              => [['product_id' => 5, 'quantity' => 3]],
        ];

        $this->callMutate($page, $data);

        $pending = $this->readPending($page, 'pendingItems');

        $this->assertCount(1, $pending);
        $this->assertSame(5, $pending[0]['product_id']);
    }

    // -----------------------------------------------------------------------
    // Edge cases
    // -----------------------------------------------------------------------

    public function test_missing_billing_address_skips_copy_when_same_as_billing_on(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => true,
            'billing_addresses'  => [],          // empty — no billing address
            'shipping_addresses' => [['firstname' => 'Original', 'shipping' => 1]],
            'items'              => [],
        ];

        $this->callMutate($page, $data);

        $pending = $this->readPending($page, 'pendingShippingAddresses');

        // Copy should not occur; original shipping data preserved
        $this->assertSame('Original', $pending[0]['firstname']);
    }
}
