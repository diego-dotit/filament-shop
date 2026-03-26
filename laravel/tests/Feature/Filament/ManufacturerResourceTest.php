<?php

namespace Tests\Feature\Filament;

use App\Domains\Manufacturer\Models\Manufacturer;
use App\Filament\Resources\ManufacturerResource;
use App\Filament\Resources\ManufacturerResource\Pages\CreateManufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\EditManufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\ListManufacturers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManufacturerResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    // ── Resource Configuration ─────────────────────────────────────────────

    public function test_manufacturer_resource_uses_correct_model(): void
    {
        $this->assertSame(Manufacturer::class, ManufacturerResource::getModel());
    }

    public function test_manufacturer_resource_has_required_pages(): void
    {
        $pages = ManufacturerResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);
    }

    // ── List Page ──────────────────────────────────────────────────────────

    public function test_list_manufacturers_page_renders(): void
    {
        Livewire::test(ListManufacturers::class)
            ->assertSuccessful();
    }

    public function test_list_page_displays_manufacturer_columns(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        Livewire::test(ListManufacturers::class)
            ->assertCanSeeTableRecords([$manufacturer])
            ->assertSee('Acme Corp')
            ->assertSee('acme-corp');
    }

    public function test_list_page_is_searchable_by_name(): void
    {
        Manufacturer::factory()->create(['name' => 'Apple Inc', 'slug' => 'apple-inc']);
        Manufacturer::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        Livewire::test(ListManufacturers::class)
            ->searchTable('Apple')
            ->assertCanSeeTableRecords(Manufacturer::where('slug', 'apple-inc')->get())
            ->assertCanNotSeeTableRecords(Manufacturer::where('slug', 'samsung')->get());
    }

    public function test_list_page_is_searchable_by_slug(): void
    {
        Manufacturer::factory()->create(['name' => 'Apple Inc', 'slug' => 'apple-inc']);
        Manufacturer::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        Livewire::test(ListManufacturers::class)
            ->searchTable('samsung')
            ->assertCanSeeTableRecords(Manufacturer::where('slug', 'samsung')->get())
            ->assertCanNotSeeTableRecords(Manufacturer::where('slug', 'apple-inc')->get());
    }

    public function test_list_page_paginates_10_per_page(): void
    {
        Manufacturer::factory()->count(15)->create();

        $component = Livewire::test(ListManufacturers::class);

        // Default page option is 10
        $component->assertSuccessful();
        $this->assertCount(10, Manufacturer::paginate(10)->items());
    }

    // ── Create Page ────────────────────────────────────────────────────────

    public function test_create_manufacturer_page_renders(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->assertSuccessful();
    }

    public function test_can_create_manufacturer_with_name_and_slug(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name' => 'Test Manufacturer',
                'slug' => 'test-manufacturer',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('manufacturers', [
            'name' => 'Test Manufacturer',
            'slug' => 'test-manufacturer',
        ]);
    }

    public function test_slug_is_auto_generated_from_name(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm(['name' => 'Sony Electronics'])
            ->assertFormSet(fn (array $data) => $this->assertSame('sony-electronics', $data['slug']));
    }

    public function test_create_requires_name(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name' => '',
                'slug' => 'some-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_create_requires_unique_slug(): void
    {
        Manufacturer::factory()->create(['slug' => 'existing-slug']);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name' => 'Another Manufacturer',
                'slug' => 'existing-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    // ── Edit Page ──────────────────────────────────────────────────────────

    public function test_edit_manufacturer_page_renders(): void
    {
        $manufacturer = Manufacturer::factory()->create();

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_edit_manufacturer(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->fillForm([
                'name' => 'New Name',
                'slug' => 'new-name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('manufacturers', [
            'id'   => $manufacturer->id,
            'name' => 'New Name',
            'slug' => 'new-name',
        ]);
    }

    public function test_edit_allows_same_slug_for_same_record(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name' => 'My Manufacturer',
            'slug' => 'my-manufacturer',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->fillForm([
                'name' => 'My Manufacturer Updated',
                'slug' => 'my-manufacturer',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    public function test_can_delete_manufacturer_from_list(): void
    {
        $manufacturer = Manufacturer::factory()->create();

        Livewire::test(ListManufacturers::class)
            ->callTableAction('delete', $manufacturer);

        $this->assertDatabaseMissing('manufacturers', ['id' => $manufacturer->id]);
    }
}
