<?php

namespace Tests\Feature\Filament;

use App\Domains\Localisation\Models\Country;
use App\Domains\Localisation\Models\Zone;
use App\Filament\Resources\ZoneResource;
use App\Filament\Resources\ZoneResource\Pages\CreateZone;
use App\Filament\Resources\ZoneResource\Pages\EditZone;
use App\Filament\Resources\ZoneResource\Pages\ListZones;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ZoneResourceTest extends TestCase
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

    public function test_zone_resource_uses_correct_model(): void
    {
        $this->assertSame(Zone::class, ZoneResource::getModel());
    }

    public function test_zone_resource_navigation_group_is_configuration(): void
    {
        $this->assertSame('Configuration', ZoneResource::getNavigationGroup());
    }

    public function test_zone_resource_navigation_sort_is_4(): void
    {
        $this->assertSame(4, ZoneResource::getNavigationSort());
    }

    // ── List page ─────────────────────────────────────────────────────────────

    public function test_list_page_renders_successfully(): void
    {
        Livewire::test(ListZones::class)
            ->assertSuccessful();
    }

    public function test_list_page_displays_zone_records(): void
    {
        $zones = Zone::factory()->count(3)->create();

        Livewire::test(ListZones::class)
            ->assertCanSeeTableRecords($zones);
    }

    public function test_list_page_paginates_20_per_page(): void
    {
        $component = Livewire::test(ListZones::class);

        $this->assertSame(20, $component->get('tableRecordsPerPage'));
    }

    public function test_list_page_shows_country_name_column(): void
    {
        $country = Country::factory()->create(['name' => 'Romania']);
        Zone::factory()->create(['name' => 'Zone A', 'country_id' => $country->id]);

        Livewire::test(ListZones::class)
            ->assertSeeText('Romania');
    }

    // ── Create page ───────────────────────────────────────────────────────────

    public function test_create_page_renders_successfully(): void
    {
        Livewire::test(CreateZone::class)
            ->assertSuccessful();
    }

    public function test_can_create_a_zone(): void
    {
        $country = Country::factory()->create();

        Livewire::test(CreateZone::class)
            ->fillForm([
                'name'       => 'Nord-Vest',
                'country_id' => $country->id,
                'code'       => 'NV',
                'status'     => true,
                'sort_order' => 1,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('zones', [
            'name'       => 'Nord-Vest',
            'country_id' => $country->id,
            'code'       => 'NV',
        ]);
    }

    public function test_create_validates_name_required(): void
    {
        $country = Country::factory()->create();

        Livewire::test(CreateZone::class)
            ->fillForm(['name' => '', 'country_id' => $country->id])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_validates_country_id_required(): void
    {
        Livewire::test(CreateZone::class)
            ->fillForm(['name' => 'Some Zone', 'country_id' => null])
            ->call('create')
            ->assertHasFormErrors(['country_id' => 'required']);
    }

    public function test_create_allows_null_code(): void
    {
        $country = Country::factory()->create();

        Livewire::test(CreateZone::class)
            ->fillForm([
                'name'       => 'No Code Zone',
                'country_id' => $country->id,
                'code'       => null,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('zones', ['name' => 'No Code Zone', 'code' => null]);
    }

    public function test_create_defaults_status_to_true(): void
    {
        $country = Country::factory()->create();

        Livewire::test(CreateZone::class)
            ->fillForm(['name' => 'Zone B', 'country_id' => $country->id])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('zones', ['name' => 'Zone B', 'status' => true]);
    }

    // ── Edit page ─────────────────────────────────────────────────────────────

    public function test_edit_page_renders_successfully(): void
    {
        $zone = Zone::factory()->create();

        Livewire::test(EditZone::class, ['record' => $zone->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_edit_a_zone(): void
    {
        $zone = Zone::factory()->create(['name' => 'Old Name']);

        Livewire::test(EditZone::class, ['record' => $zone->getRouteKey()])
            ->fillForm(['name' => 'New Name'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('zones', ['id' => $zone->id, 'name' => 'New Name']);
    }

    public function test_edit_can_change_country(): void
    {
        $country1 = Country::factory()->create();
        $country2 = Country::factory()->create();
        $zone     = Zone::factory()->create(['country_id' => $country1->id]);

        Livewire::test(EditZone::class, ['record' => $zone->getRouteKey()])
            ->fillForm(['country_id' => $country2->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('zones', ['id' => $zone->id, 'country_id' => $country2->id]);
    }

    // ── Delete action ─────────────────────────────────────────────────────────

    public function test_delete_action_removes_zone(): void
    {
        $zone = Zone::factory()->create();

        Livewire::test(ListZones::class)
            ->callTableAction('delete', $zone);

        $this->assertDatabaseMissing('zones', ['id' => $zone->id]);
    }
}
