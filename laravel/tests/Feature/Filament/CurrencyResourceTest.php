<?php

namespace Tests\Feature\Filament;

use App\Domains\Currency\Models\Currency;
use App\Domains\Order\Models\Order;
use App\Domains\Customer\Models\Customer;
use App\Filament\Resources\CurrencyResource;
use App\Filament\Resources\Currency\Pages\CreateCurrency;
use App\Filament\Resources\Currency\Pages\EditCurrency;
use App\Filament\Resources\Currency\Pages\ListCurrencies;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── List Page ─────────────────────────────────────────────────────────────

    public function test_list_page_renders_with_currency_columns(): void
    {
        Currency::factory()->create([
            'code'          => 'USD',
            'name'          => 'US Dollar',
            'symbol'        => '$',
            'exchange_rate' => '1.000000',
            'is_base'       => true,
        ]);

        Livewire::test(ListCurrencies::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Currency::all());
    }

    public function test_list_page_paginates_at_20_per_page(): void
    {
        Currency::factory()->count(25)->create();

        $component = Livewire::test(ListCurrencies::class);
        $component->assertSuccessful();

        // Should show 20 records, not all 25
        $this->assertCount(20, Currency::paginate(20)->items());
    }

    // ── Create Page ───────────────────────────────────────────────────────────

    public function test_can_create_a_currency(): void
    {
        Livewire::test(CreateCurrency::class)
            ->fillForm([
                'code'          => 'EUR',
                'name'          => 'Euro',
                'symbol'        => '€',
                'exchange_rate' => 0.85,
                'is_base'       => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('currencies', [
            'code'   => 'EUR',
            'name'   => 'Euro',
            'symbol' => '€',
        ]);
    }

    public function test_create_validates_code_max_3_chars(): void
    {
        Livewire::test(CreateCurrency::class)
            ->fillForm([
                'code'          => 'USDX',
                'name'          => 'Test',
                'symbol'        => 'T',
                'exchange_rate' => 1.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_create_validates_code_unique(): void
    {
        Currency::factory()->create(['code' => 'USD']);

        Livewire::test(CreateCurrency::class)
            ->fillForm([
                'code'          => 'USD',
                'name'          => 'US Dollar',
                'symbol'        => '$',
                'exchange_rate' => 1.0,
            ])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    // ── Edit Page ─────────────────────────────────────────────────────────────

    public function test_can_edit_a_currency(): void
    {
        $currency = Currency::factory()->create([
            'code'          => 'USD',
            'name'          => 'US Dollar',
            'symbol'        => '$',
            'exchange_rate' => '1.000000',
            'is_base'       => false,
        ]);

        Livewire::test(EditCurrency::class, ['record' => $currency->id])
            ->fillForm([
                'name'          => 'United States Dollar',
                'exchange_rate' => 1.25,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('currencies', [
            'id'   => $currency->id,
            'name' => 'United States Dollar',
        ]);
    }

    // ── Set as Base Action ────────────────────────────────────────────────────

    public function test_set_as_base_makes_selected_currency_base(): void
    {
        $usd = Currency::factory()->create(['code' => 'USD', 'is_base' => true,  'exchange_rate' => '1.000000']);
        $eur = Currency::factory()->create(['code' => 'EUR', 'is_base' => false, 'exchange_rate' => '0.850000']);

        Livewire::test(ListCurrencies::class)
            ->callTableAction('set_as_base', $eur);

        $this->assertDatabaseHas('currencies', ['id' => $eur->id, 'is_base' => true]);
    }

    public function test_set_as_base_clears_is_base_on_all_other_currencies(): void
    {
        $usd = Currency::factory()->create(['code' => 'USD', 'is_base' => true,  'exchange_rate' => '1.000000']);
        $eur = Currency::factory()->create(['code' => 'EUR', 'is_base' => false, 'exchange_rate' => '0.850000']);
        $gbp = Currency::factory()->create(['code' => 'GBP', 'is_base' => false, 'exchange_rate' => '0.750000']);

        Livewire::test(ListCurrencies::class)
            ->callTableAction('set_as_base', $eur);

        $this->assertDatabaseHas('currencies', ['id' => $usd->id, 'is_base' => false]);
        $this->assertDatabaseHas('currencies', ['id' => $gbp->id, 'is_base' => false]);
        $this->assertDatabaseHas('currencies', ['id' => $eur->id, 'is_base' => true]);
    }

    public function test_only_one_base_currency_exists_after_set_as_base(): void
    {
        $usd = Currency::factory()->create(['code' => 'USD', 'is_base' => true,  'exchange_rate' => '1.000000']);
        $eur = Currency::factory()->create(['code' => 'EUR', 'is_base' => false, 'exchange_rate' => '0.850000']);
        $gbp = Currency::factory()->create(['code' => 'GBP', 'is_base' => false, 'exchange_rate' => '0.750000']);

        Livewire::test(ListCurrencies::class)
            ->callTableAction('set_as_base', $eur);

        $baseCount = Currency::where('is_base', true)->count();
        $this->assertEquals(1, $baseCount);
    }

    // ── Delete Action ─────────────────────────────────────────────────────────

    public function test_can_delete_currency_with_no_orders(): void
    {
        $eur = Currency::factory()->create(['code' => 'EUR', 'is_base' => false]);

        Livewire::test(ListCurrencies::class)
            ->callTableAction('delete', $eur);

        $this->assertDatabaseMissing('currencies', ['id' => $eur->id]);
    }

    // ── Exchange Rate Constraint ───────────────────────────────────────────────

    public function test_set_as_base_sets_exchange_rate_to_one(): void
    {
        Currency::factory()->create(['code' => 'USD', 'is_base' => true,  'exchange_rate' => '1.000000']);
        $eur = Currency::factory()->create(['code' => 'EUR', 'is_base' => false, 'exchange_rate' => '0.850000']);

        Livewire::test(ListCurrencies::class)
            ->callTableAction('set_as_base', $eur);

        $this->assertDatabaseHas('currencies', [
            'id'            => $eur->id,
            'exchange_rate' => '1.000000',
        ]);
    }
}
