<?php

namespace Tests\Feature\Filament;

use App\Domains\Customer\Models\Customer;
use App\Domains\Customer\Models\CustomerAddress;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create();
    }

    public function test_list_customers_page_is_accessible_to_authenticated_user(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(CustomerResource::getUrl('index'));

        $response->assertOk();
    }

    public function test_list_customers_page_redirects_unauthenticated_user(): void
    {
        $response = $this->get(CustomerResource::getUrl('index'));

        $response->assertRedirect();
    }

    public function test_list_customers_table_displays_customer_data(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create([
            'first_name' => 'Jane',
            'last_name'  => 'Doe',
            'email'      => 'jane.doe@example.com',
        ]);

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$customer])
            ->assertSee('Jane')
            ->assertSee('Doe')
            ->assertSee('jane.doe@example.com');
    }

    public function test_list_customers_table_is_searchable_by_first_name(): void
    {
        $this->actingAs($this->adminUser);

        $targetCustomer = Customer::factory()->create(['first_name' => 'Alice', 'last_name' => 'Smith']);
        $otherCustomer  = Customer::factory()->create(['first_name' => 'Bob', 'last_name' => 'Jones']);

        Livewire::test(ListCustomers::class)
            ->searchTable('Alice')
            ->assertCanSeeTableRecords([$targetCustomer])
            ->assertCanNotSeeTableRecords([$otherCustomer]);
    }

    public function test_list_customers_table_is_searchable_by_last_name(): void
    {
        $this->actingAs($this->adminUser);

        $targetCustomer = Customer::factory()->create(['first_name' => 'Alice', 'last_name' => 'Smith']);
        $otherCustomer  = Customer::factory()->create(['first_name' => 'Bob', 'last_name' => 'Jones']);

        Livewire::test(ListCustomers::class)
            ->searchTable('Smith')
            ->assertCanSeeTableRecords([$targetCustomer])
            ->assertCanNotSeeTableRecords([$otherCustomer]);
    }

    public function test_list_customers_table_is_searchable_by_email(): void
    {
        $this->actingAs($this->adminUser);

        $targetCustomer = Customer::factory()->create(['email' => 'target@example.com']);
        $otherCustomer  = Customer::factory()->create(['email' => 'other@example.com']);

        Livewire::test(ListCustomers::class)
            ->searchTable('target@example.com')
            ->assertCanSeeTableRecords([$targetCustomer])
            ->assertCanNotSeeTableRecords([$otherCustomer]);
    }

    public function test_list_customers_table_has_expected_columns(): void
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ListCustomers::class)
            ->assertTableColumnExists('id')
            ->assertTableColumnExists('first_name')
            ->assertTableColumnExists('last_name')
            ->assertTableColumnExists('email')
            ->assertTableColumnExists('phone')
            ->assertTableColumnExists('created_at');
    }

    public function test_list_customers_table_has_edit_action(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();

        Livewire::test(ListCustomers::class)
            ->assertTableActionExists('edit');
    }

    public function test_list_customers_table_has_view_action(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();

        Livewire::test(ListCustomers::class)
            ->assertTableActionExists('view');
    }

    // -----------------------------------------------------------------------
    // Edit page tests
    // -----------------------------------------------------------------------

    public function test_edit_customer_page_is_accessible(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();

        $response = $this->get(CustomerResource::getUrl('edit', ['record' => $customer]));

        $response->assertOk();
    }

    public function test_edit_customer_form_has_expected_fields(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertFormFieldExists('first_name')
            ->assertFormFieldExists('last_name')
            ->assertFormFieldExists('email')
            ->assertFormFieldExists('phone');
    }

    public function test_edit_customer_form_updates_customer_record(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create([
            'first_name' => 'OldFirst',
            'last_name'  => 'OldLast',
            'email'      => 'old@example.com',
            'phone'      => '111-0000',
        ]);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm([
                'first_name' => 'NewFirst',
                'last_name'  => 'NewLast',
                'email'      => 'new@example.com',
                'phone'      => '999-1111',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $customer->refresh();

        $this->assertSame('NewFirst', $customer->first_name);
        $this->assertSame('NewLast', $customer->last_name);
        $this->assertSame('new@example.com', $customer->email);
        $this->assertSame('999-1111', $customer->phone);
    }

    public function test_edit_customer_form_requires_first_name(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['first_name' => ''])
            ->call('save')
            ->assertHasFormErrors(['first_name' => 'required']);
    }

    public function test_edit_customer_form_requires_last_name(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['last_name' => ''])
            ->call('save')
            ->assertHasFormErrors(['last_name' => 'required']);
    }

    public function test_edit_customer_form_rejects_duplicate_email(): void
    {
        $this->actingAs($this->adminUser);

        $existingCustomer = Customer::factory()->create(['email' => 'taken@example.com']);
        $customer         = Customer::factory()->create(['email' => 'mine@example.com']);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm(['email' => 'taken@example.com'])
            ->call('save')
            ->assertHasFormErrors(['email' => 'unique']);
    }

    public function test_edit_customer_form_allows_saving_own_email(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create(['email' => 'mine@example.com']);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->fillForm([
                'first_name' => $customer->first_name,
                'last_name'  => $customer->last_name,
                'email'      => 'mine@example.com',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // -----------------------------------------------------------------------
    // View page tests
    // -----------------------------------------------------------------------

    public function test_view_customer_page_is_accessible(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();

        $response = $this->get(CustomerResource::getUrl('view', ['record' => $customer]));

        $response->assertOk();
    }

    public function test_view_customer_page_displays_customer_data(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create([
            'first_name' => 'ViewFirst',
            'last_name'  => 'ViewLast',
            'email'      => 'view@example.com',
        ]);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertSee('ViewFirst')
            ->assertSee('ViewLast')
            ->assertSee('view@example.com');
    }

    public function test_view_customer_page_displays_addresses(): void
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::factory()->create();
        $address  = CustomerAddress::factory()->create([
            'customer_id'    => $customer->id,
            'city'           => 'TestCity',
            'country'        => 'TestCountry',
            'address_line_1' => '123 Test St',
            'postcode'       => 'TC123',
        ]);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertSee('TestCity')
            ->assertSee('TestCountry')
            ->assertSee('123 Test St')
            ->assertSee('TC123');
    }
}
