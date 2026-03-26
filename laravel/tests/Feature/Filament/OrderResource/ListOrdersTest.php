<?php

namespace Tests\Feature\Filament\OrderResource;

use App\Domains\Customer\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListOrdersTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->create();
    }

    public function test_list_orders_page_renders_for_authenticated_user(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('filament.admin.resources.orders.index'));

        $response->assertSuccessful();
    }

    public function test_list_orders_displays_order_data(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
        ]);

        $order = Order::factory()->create([
            'customer_id'   => $customer->id,
            'status'        => 'pending',
            'total_amount'  => 150.00,
            'currency_code' => 'USD',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ListOrders::class)
            ->assertCanSeeTableRecords([$order]);
    }

    public function test_list_orders_displays_customer_name_column(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create([
            'first_name' => 'Alice',
            'last_name'  => 'Smith',
        ]);

        Order::factory()->create([
            'customer_id'   => $customer->id,
            'status'        => 'pending',
            'total_amount'  => 99.99,
            'currency_code' => 'EUR',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ListOrders::class)
            ->assertSee('Alice');
    }

    public function test_list_orders_filter_by_status_shows_only_matching_orders(): void
    {
        $this->actingAs($this->adminUser);

        $pendingOrder = Order::factory()->create(['status' => 'pending']);
        $completedOrder = Order::factory()->create(['status' => 'completed']);
        $cancelledOrder = Order::factory()->create(['status' => 'cancelled']);

        Livewire::actingAs($this->adminUser)
            ->test(ListOrders::class)
            ->filterTable('status', 'pending')
            ->assertCanSeeTableRecords([$pendingOrder])
            ->assertCanNotSeeTableRecords([$completedOrder, $cancelledOrder]);
    }

    public function test_list_orders_filter_by_completed_status(): void
    {
        $this->actingAs($this->adminUser);

        $pendingOrder = Order::factory()->create(['status' => 'pending']);
        $completedOrder = Order::factory()->create(['status' => 'completed']);

        Livewire::actingAs($this->adminUser)
            ->test(ListOrders::class)
            ->filterTable('status', 'completed')
            ->assertCanSeeTableRecords([$completedOrder])
            ->assertCanNotSeeTableRecords([$pendingOrder]);
    }

    public function test_list_orders_displays_total_amount_with_currency_code(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();

        Order::factory()->create([
            'customer_id'   => $customer->id,
            'status'        => 'pending',
            'total_amount'  => 250.00,
            'currency_code' => 'USD',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ListOrders::class)
            ->assertSee('250.00')
            ->assertSee('USD');
    }

    public function test_list_orders_table_has_status_sortable_column(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::actingAs($this->adminUser)
            ->test(ListOrders::class)
            ->sortTable('status')
            ->assertSuccessful();
    }

    public function test_list_orders_table_has_created_at_sortable_column(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::actingAs($this->adminUser)
            ->test(ListOrders::class)
            ->sortTable('created_at')
            ->assertSuccessful();
    }

    public function test_list_orders_resource_has_view_action(): void
    {
        $pages = OrderResource::getPages();

        $this->assertArrayHasKey('view', $pages);
    }

    public function test_list_orders_paginates_at_ten_per_page(): void
    {
        $this->actingAs($this->adminUser);

        Order::factory()->count(15)->create();

        $component = Livewire::actingAs($this->adminUser)
            ->test(ListOrders::class);

        // Default pagination should be 10
        $this->assertEquals(10, $component->instance()->getDefaultTableRecordsPerPageSelectOption());
    }
}
