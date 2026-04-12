<?php

namespace Tests\Feature\Filament\OrderResource;

use App\Filament\Resources\OrderResource\Pages\EditOrder;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Tests for EditOrder address logic:
 *  - detectSameAsBilling  (private): compares billing vs shipping fields
 *  - mutateFormDataBeforeSave (protected): copies billing→shipping when ON, strips keys
 */
class EditOrderAddressTest extends TestCase
{
    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function makePage(): EditOrder
    {
        return new EditOrder();
    }

    private function callDetect(EditOrder $page, array $billing, array $shipping): bool
    {
        $method = new ReflectionMethod(EditOrder::class, 'detectSameAsBilling');
        $method->setAccessible(true);

        return $method->invoke($page, $billing, $shipping);
    }

    /** @param array<string, mixed> $data */
    private function callBeforeSave(EditOrder $page, array $data): array
    {
        $method = new ReflectionMethod(EditOrder::class, 'mutateFormDataBeforeSave');
        $method->setAccessible(true);

        return $method->invoke($page, $data);
    }

    /** @return array<int, array<string, mixed>> */
    private function readPending(EditOrder $page, string $property): array
    {
        $prop = new ReflectionProperty(EditOrder::class, $property);
        $prop->setAccessible(true);

        return $prop->getValue($page);
    }

    /** @return array<string, mixed> */
    private function sampleAddress(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }

    // -----------------------------------------------------------------------
    // detectSameAsBilling
    // -----------------------------------------------------------------------

    public function test_detect_returns_true_when_all_compare_fields_match(): void
    {
        $page    = $this->makePage();
        $billing  = $this->sampleAddress();
        $shipping = $this->sampleAddress();

        $this->assertTrue($this->callDetect($page, $billing, $shipping));
    }

    public function test_detect_returns_false_when_firstname_differs(): void
    {
        $page     = $this->makePage();
        $billing  = $this->sampleAddress(['firstname' => 'Alice']);
        $shipping = $this->sampleAddress(['firstname' => 'Bob']);

        $this->assertFalse($this->callDetect($page, $billing, $shipping));
    }

    public function test_detect_returns_false_when_country_id_differs(): void
    {
        $page     = $this->makePage();
        $billing  = $this->sampleAddress(['country_id' => 10]);
        $shipping = $this->sampleAddress(['country_id' => 99]);

        $this->assertFalse($this->callDetect($page, $billing, $shipping));
    }

    public function test_detect_returns_false_when_address_line_differs(): void
    {
        $page     = $this->makePage();
        $billing  = $this->sampleAddress(['address_line_1' => '789 Pine Rd']);
        $shipping = $this->sampleAddress(['address_line_1' => '1 Other St']);

        $this->assertFalse($this->callDetect($page, $billing, $shipping));
    }

    public function test_detect_returns_false_when_billing_is_empty(): void
    {
        $page = $this->makePage();

        $this->assertFalse($this->callDetect($page, [], $this->sampleAddress()));
    }

    public function test_detect_returns_false_when_shipping_is_empty(): void
    {
        $page = $this->makePage();

        $this->assertFalse($this->callDetect($page, $this->sampleAddress(), []));
    }

    public function test_detect_returns_false_when_both_empty(): void
    {
        $page = $this->makePage();

        $this->assertFalse($this->callDetect($page, [], []));
    }

    public function test_detect_treats_null_and_missing_keys_as_equal(): void
    {
        $page     = $this->makePage();
        $billing  = $this->sampleAddress(['zone_id' => null]);
        $shipping = $this->sampleAddress(['zone_id' => null]); // both null — should match

        $this->assertTrue($this->callDetect($page, $billing, $shipping));
    }

    // -----------------------------------------------------------------------
    // mutateFormDataBeforeSave — copy path (same_as_billing = true)
    // -----------------------------------------------------------------------

    public function test_billing_fields_copied_to_shipping_when_same_as_billing_on(): void
    {
        $page = $this->makePage();

        $billing = $this->sampleAddress(['shipping' => 0]);

        $data = [
            'same_as_billing'    => true,
            'billing_addresses'  => [$billing],
            'shipping_addresses' => [['firstname' => 'Original', 'shipping' => 1]],
            'items'              => [],
        ];

        $this->callBeforeSave($page, $data);

        $pending = $this->readPending($page, 'pendingShippingAddresses');

        $this->assertSame('Alice', $pending[0]['firstname']);
        $this->assertSame('Brown', $pending[0]['lastname']);
        $this->assertSame(10, $pending[0]['country_id']);
        $this->assertSame('789 Pine Rd', $pending[0]['address_line_1']);
    }

    public function test_shipping_flag_is_one_after_copy(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => true,
            'billing_addresses'  => [$this->sampleAddress(['shipping' => 0])],
            'shipping_addresses' => [['shipping' => 1]],
            'items'              => [],
        ];

        $this->callBeforeSave($page, $data);

        $pending = $this->readPending($page, 'pendingShippingAddresses');

        $this->assertSame(1, $pending[0]['shipping']);
    }

    // -----------------------------------------------------------------------
    // mutateFormDataBeforeSave — no-copy path (same_as_billing = false)
    // -----------------------------------------------------------------------

    public function test_shipping_not_overridden_when_same_as_billing_is_off(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => false,
            'billing_addresses'  => [$this->sampleAddress()],
            'shipping_addresses' => [$this->sampleAddress(['firstname' => 'Different', 'shipping' => 1])],
            'items'              => [],
        ];

        $this->callBeforeSave($page, $data);

        $pending = $this->readPending($page, 'pendingShippingAddresses');

        $this->assertSame('Different', $pending[0]['firstname']);
    }

    // -----------------------------------------------------------------------
    // Key cleanup
    // -----------------------------------------------------------------------

    public function test_same_as_billing_key_removed_from_returned_data(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => true,
            'billing_addresses'  => [$this->sampleAddress()],
            'shipping_addresses' => [['shipping' => 1]],
            'items'              => [],
        ];

        $result = $this->callBeforeSave($page, $data);

        $this->assertArrayNotHasKey('same_as_billing', $result);
    }

    public function test_addresses_and_items_removed_from_returned_data(): void
    {
        $page = $this->makePage();

        $data = [
            'same_as_billing'    => false,
            'billing_addresses'  => [$this->sampleAddress()],
            'shipping_addresses' => [$this->sampleAddress(['shipping' => 1])],
            'items'              => [['product_id' => 1]],
        ];

        $result = $this->callBeforeSave($page, $data);

        $this->assertArrayNotHasKey('billing_addresses', $result);
        $this->assertArrayNotHasKey('shipping_addresses', $result);
        $this->assertArrayNotHasKey('items', $result);
    }

    // -----------------------------------------------------------------------
    // Pending arrays stored correctly
    // -----------------------------------------------------------------------

    public function test_pending_billing_set_from_form_data(): void
    {
        $page = $this->makePage();

        $billing = $this->sampleAddress(['shipping' => 0]);

        $data = [
            'same_as_billing'    => false,
            'billing_addresses'  => [$billing],
            'shipping_addresses' => [$this->sampleAddress(['shipping' => 1])],
            'items'              => [],
        ];

        $this->callBeforeSave($page, $data);

        $pending = $this->readPending($page, 'pendingBillingAddresses');

        $this->assertCount(1, $pending);
        $this->assertSame('Alice', $pending[0]['firstname']);
    }
}
