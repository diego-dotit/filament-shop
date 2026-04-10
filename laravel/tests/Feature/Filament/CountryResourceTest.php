<?php

namespace Tests\Feature\Filament;

use App\Domains\Localisation\Models\Country;
use App\Filament\Resources\CountryResource;
use App\Filament\Resources\CountryResource\Pages\CreateCountry;
use App\Filament\Resources\CountryResource\Pages\EditCountry;
use App\Filament\Resources\CountryResource\Pages\ListCountries;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CountryResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Resource configuration ─────────────────────────────────────────────────

    public function test_country_resource_uses_correct_model(): void
    {
        $this->assertSame(Country::class, CountryResource::getModel());
    }

    public function test_country_resource_navigation_group_is_configuration(): void
    {
        $this->assertSame('Configuration', CountryResource::getNavigationGroup());
    }

    public function test_country_resource_navigation_sort_is_3(): void
    {
        $this->assertSame(3, CountryResource::getNavigationSort());
    }

    // ── List page ─────────────────────────────────────────────────────────────

    public function test_list_page_renders_successfully(): void
    {
        Livewire::test(ListCountries::class)
            ->assertSuccessful();
    }

    public function test_list_page_displays_country_records(): void
    {
        $countries = Country::factory()->count(3)->create();

        Livewire::test(ListCountries::class)
            ->assertCanSeeTableRecords($countries);
    }

    public function test_list_page_paginates_20_per_page(): void
    {
        $component = Livewire::test(ListCountries::class);

        $this->assertSame(20, $component->get('tableRecordsPerPage'));
    }

    // ── Create page ───────────────────────────────────────────────────────────

    public function test_create_page_renders_successfully(): void
    {
        Livewire::test(CreateCountry::class)
            ->assertSuccessful();
    }

    public function test_can_create_a_country(): void
    {
        Livewire::test(CreateCountry::class)
            ->fillForm([
                'name'       => 'Romania',
                'code'       => 'RO',
                'status'     => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('countries', [
            'name' => 'Romania',
            'code' => 'RO',
        ]);
    }

    public function test_create_validates_name_required(): void
    {
        Livewire::test(CreateCountry::class)
            ->fillForm(['name' => '', 'code' => 'RO'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_validates_code_required(): void
    {
        Livewire::test(CreateCountry::class)
            ->fillForm(['name' => 'Romania', 'code' => ''])
            ->call('create')
            ->assertHasFormErrors(['code' => 'required']);
    }

    public function test_create_validates_code_unique(): void
    {
        Country::factory()->create(['code' => 'RO']);

        Livewire::test(CreateCountry::class)
            ->fillForm(['name' => 'Romania Duplicate', 'code' => 'RO'])
            ->call('create')
            ->assertHasFormErrors(['code']);
    }

    public function test_create_defaults_status_to_true(): void
    {
        Livewire::test(CreateCountry::class)
            ->fillForm(['name' => 'Germany', 'code' => 'DE'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('countries', ['code' => 'DE', 'status' => true]);
    }

    // ── Edit page ─────────────────────────────────────────────────────────────

    public function test_edit_page_renders_successfully(): void
    {
        $country = Country::factory()->create();

        Livewire::test(EditCountry::class, ['record' => $country->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_edit_a_country(): void
    {
        $country = Country::factory()->create(['name' => 'Romania', 'code' => 'RO']);

        Livewire::test(EditCountry::class, ['record' => $country->getRouteKey()])
            ->fillForm(['name' => 'România Updated'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('countries', ['id' => $country->id, 'name' => 'România Updated']);
    }

    public function test_edit_allows_same_code_on_same_record(): void
    {
        $country = Country::factory()->create(['code' => 'RO']);

        Livewire::test(EditCountry::class, ['record' => $country->getRouteKey()])
            ->fillForm(['code' => 'RO', 'name' => 'Romania Updated'])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_edit_validates_code_unique_against_other_records(): void
    {
        Country::factory()->create(['code' => 'DE']);
        $country = Country::factory()->create(['code' => 'RO']);

        Livewire::test(EditCountry::class, ['record' => $country->getRouteKey()])
            ->fillForm(['code' => 'DE'])
            ->call('save')
            ->assertHasFormErrors(['code']);
    }

    // ── Delete action ─────────────────────────────────────────────────────────

    public function test_delete_action_removes_country(): void
    {
        $country = Country::factory()->create();

        Livewire::test(ListCountries::class)
            ->callTableAction('delete', $country);

        $this->assertDatabaseMissing('countries', ['id' => $country->id]);
    }
}
