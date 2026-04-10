<?php

namespace Tests\Feature\Filament;

use App\Domains\Localisation\Models\City;
use App\Domains\Localisation\Models\Zone;
use App\Filament\Resources\CityResource;
use App\Filament\Resources\CityResource\Pages\CreateCity;
use App\Filament\Resources\CityResource\Pages\EditCity;
use App\Filament\Resources\CityResource\Pages\ListCities;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CityResourceTest extends TestCase
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

    public function test_city_resource_uses_correct_model(): void
    {
        $this->assertSame(City::class, CityResource::getModel());
    }

    public function test_city_resource_navigation_group_is_configuration(): void
    {
        $this->assertSame('Configuration', CityResource::getNavigationGroup());
    }

    public function test_city_resource_navigation_sort_is_5(): void
    {
        $this->assertSame(5, CityResource::getNavigationSort());
    }

    // ── List page ─────────────────────────────────────────────────────────────

    public function test_list_page_renders_successfully(): void
    {
        Livewire::test(ListCities::class)
            ->assertSuccessful();
    }

    public function test_list_page_displays_city_records(): void
    {
        $cities = City::factory()->count(3)->create();

        Livewire::test(ListCities::class)
            ->assertCanSeeTableRecords($cities);
    }

    public function test_list_page_paginates_20_per_page(): void
    {
        $component = Livewire::test(ListCities::class);

        $this->assertSame(20, $component->get('tableRecordsPerPage'));
    }

    public function test_list_page_shows_zone_name_column(): void
    {
        $zone = Zone::factory()->create(['name' => 'Nord']);
        City::factory()->create(['name' => 'Cluj', 'zone_id' => $zone->id]);

        Livewire::test(ListCities::class)
            ->assertSeeText('Nord');
    }

    // ── Create page ───────────────────────────────────────────────────────────

    public function test_create_page_renders_successfully(): void
    {
        Livewire::test(CreateCity::class)
            ->assertSuccessful();
    }

    public function test_can_create_a_city(): void
    {
        $zone = Zone::factory()->create();

        Livewire::test(CreateCity::class)
            ->fillForm([
                'name'       => 'Cluj-Napoca',
                'zone_id'    => $zone->id,
                'status'     => true,
                'sort_order' => 1,
                'postcode'   => '400001',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cities', [
            'name'     => 'Cluj-Napoca',
            'zone_id'  => $zone->id,
            'postcode' => '400001',
        ]);
    }

    public function test_create_validates_name_required(): void
    {
        $zone = Zone::factory()->create();

        Livewire::test(CreateCity::class)
            ->fillForm(['name' => '', 'zone_id' => $zone->id])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_validates_zone_id_required(): void
    {
        Livewire::test(CreateCity::class)
            ->fillForm(['name' => 'Some City', 'zone_id' => null])
            ->call('create')
            ->assertHasFormErrors(['zone_id' => 'required']);
    }

    public function test_create_allows_null_postcode(): void
    {
        $zone = Zone::factory()->create();

        Livewire::test(CreateCity::class)
            ->fillForm([
                'name'     => 'No Postcode City',
                'zone_id'  => $zone->id,
                'postcode' => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cities', ['name' => 'No Postcode City', 'postcode' => null]);
    }

    public function test_create_defaults_status_to_true(): void
    {
        $zone = Zone::factory()->create();

        Livewire::test(CreateCity::class)
            ->fillForm(['name' => 'Timișoara', 'zone_id' => $zone->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cities', ['name' => 'Timișoara', 'status' => true]);
    }

    // ── Edit page ─────────────────────────────────────────────────────────────

    public function test_edit_page_renders_successfully(): void
    {
        $city = City::factory()->create();

        Livewire::test(EditCity::class, ['record' => $city->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_edit_a_city(): void
    {
        $city = City::factory()->create(['name' => 'Old Name']);

        Livewire::test(EditCity::class, ['record' => $city->getRouteKey()])
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cities', ['id' => $city->id, 'name' => 'New Name']);
    }

    public function test_edit_can_change_zone(): void
    {
        $zone1 = Zone::factory()->create();
        $zone2 = Zone::factory()->create();
        $city  = City::factory()->create(['zone_id' => $zone1->id]);

        Livewire::test(EditCity::class, ['record' => $city->getRouteKey()])
            ->fillForm(['zone_id' => $zone2->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cities', ['id' => $city->id, 'zone_id' => $zone2->id]);
    }

    public function test_edit_can_update_postcode(): void
    {
        $city = City::factory()->create(['postcode' => '100000']);

        Livewire::test(EditCity::class, ['record' => $city->getRouteKey()])
            ->fillForm(['postcode' => '999999'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('cities', ['id' => $city->id, 'postcode' => '999999']);
    }

    // ── Delete action ─────────────────────────────────────────────────────────

    public function test_delete_action_removes_city(): void
    {
        $city = City::factory()->create();

        Livewire::test(ListCities::class)
            ->callTableAction('delete', $city);

        $this->assertDatabaseMissing('cities', ['id' => $city->id]);
    }
}
