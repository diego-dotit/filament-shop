<?php

namespace Tests\Feature\Filament\OrderResource;

use App\Domains\Customer\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Order\Models\OrderAddress;
use App\Domains\Order\Models\OrderItem;
use App\Filament\Resources\OrderResource\Pages\ViewOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ViewOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create();
    }

    public function test_view_order_page_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->adminUser);

        $order = Order::factory()->create();

        $response = $this->get(route('filament.admin.resources.orders.view', ['record' => $order->id]));

        $response->assertSuccessful();
    }

    public function test_view_order_page_displays_order_summary_fields(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create([
            'first_name' => 'John',
            'last_name'  => 'Smith',
        ]);

        $order = Order::factory()->create([
            'customer_id'   => $customer->id,
            'status'        => 'processing',
            'total_amount'  => 199.99,
            'currency_code' => 'USD',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->assertSee((string) $order->id)
            ->assertSee('John')
            ->assertSee('processing')
            ->assertSee('199.99')
            ->assertSee('USD');
    }

    public function test_view_order_page_displays_order_items(): void
    {
        $this->actingAs($this->adminUser);

        $order = Order::factory()->create();

        OrderItem::factory()->create([
            'order_id'              => $order->id,
            'product_name_snapshot' => 'Blue Widget',
            'variant_sku_snapshot'  => 'SKU-1234-ABC',
            'unit_price_snapshot'   => 25.00,
            'quantity'              => 3,
            'line_total_snapshot'   => 75.00,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->assertSee('Blue Widget')
            ->assertSee('SKU-1234-ABC')
            ->assertSee('25.00')
            ->assertSee('75.00');
    }

    public function test_view_order_page_displays_billing_address_section(): void
    {
        $this->actingAs($this->adminUser);

        $order = Order::factory()->create();

        OrderAddress::factory()->create([
            'order_id'       => $order->id,
            'type'           => 'billing',
            'country'        => 'United States',
            'city'           => 'New York',
            'address_line_1' => '123 Main St',
            'postcode'       => '10001',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->assertSee('Billing Address')
            ->assertSee('United States')
            ->assertSee('New York')
            ->assertSee('123 Main St')
            ->assertSee('10001');
    }

    public function test_view_order_page_displays_shipping_address_section(): void
    {
        $this->actingAs($this->adminUser);

        $order = Order::factory()->create();

        OrderAddress::factory()->create([
            'order_id'       => $order->id,
            'type'           => 'shipping',
            'country'        => 'Canada',
            'city'           => 'Toronto',
            'address_line_1' => '456 Maple Ave',
            'address_line_2' => 'Apt 7',
            'postcode'       => 'M5V 2T6',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->assertSee('Shipping Address')
            ->assertSee('Canada')
            ->assertSee('Toronto')
            ->assertSee('456 Maple Ave')
            ->assertSee('Apt 7')
            ->assertSee('M5V 2T6');
    }

    public function test_view_order_page_has_status_update_placeholder(): void
    {
        $this->actingAs($this->adminUser);

        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->assertSee('pending');
    }

    public function test_view_order_page_has_update_status_action(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->assertActionExists('updateStatus');
    }

    public function test_update_status_action_updates_order_status_to_completed(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->callAction('updateStatus', data: ['status' => 'completed'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_update_status_action_updates_order_status_to_cancelled(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->callAction('updateStatus', data: ['status' => 'cancelled'])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_update_status_action_sends_success_notification(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->callAction('updateStatus', data: ['status' => 'processing'])
            ->assertNotified('Status updated');
    }

    public function test_update_status_action_requires_status_field(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->callAction('updateStatus', data: ['status' => null])
            ->assertHasActionErrors(['status' => 'required']);
    }

    public function test_update_status_persists_to_database(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $this->assertEquals('pending', $order->fresh()->status);

        Livewire::actingAs($this->adminUser)
            ->test(ViewOrder::class, ['record' => $order->id])
            ->callAction('updateStatus', data: ['status' => 'shipped']);

        $this->assertEquals('shipped', $order->fresh()->status);
    }
}
