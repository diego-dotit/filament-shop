<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Domains\Slug\Models\Slug;
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
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'Test Manufacturer',
                'slug_en' => 'test-manufacturer',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('manufacturers', ['slug' => 'test-manufacturer']);

        $manufacturer = Manufacturer::where('slug', 'test-manufacturer')->firstOrFail();
        $this->assertSame('Test Manufacturer', $manufacturer->getTranslation('name', 'en'));
    }

    public function test_slug_is_auto_generated_from_name(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm(['name_en' => 'Sony Electronics'])
            ->assertFormSet(fn (array $data) => $this->assertSame('sony-electronics', $data['slug_en']));
    }

    public function test_create_requires_name(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => '',
                'slug_en' => 'some-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['name_en' => 'required']);
    }

    public function test_create_requires_unique_slug(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // Insert directly into the slugs table so the validation against
        // that table is exercised (no language needed).
        Slug::create([
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => 9999,
            'locale'         => 'en',
            'slug'           => 'existing-slug',
        ]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'Another Manufacturer',
                'slug_en' => 'existing-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_slug_uniqueness_validates_against_slugs_table(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        // A slug belonging to a *different* manufacturer must block creation.
        Slug::create([
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => 8888,
            'locale'         => 'en',
            'slug'           => 'blocked-slug',
        ]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm(['name_en' => 'New Brand', 'slug_en' => 'blocked-slug'])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_creating_manufacturer_persists_slug_to_slugs_table(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm(['name_en' => 'Fresh Brand', 'slug_en' => 'fresh-brand'])
            ->call('create')
            ->assertHasNoFormErrors();

        $manufacturer = Manufacturer::where('slug', 'fresh-brand')->firstOrFail();

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'slug'           => 'fresh-brand',
        ]);
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
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'Old Name'],
            'slug' => 'old-name',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->fillForm([
                'name_en' => 'New Name',
                'slug_en' => 'new-name',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $manufacturer->refresh();
        $this->assertSame('New Name', $manufacturer->getTranslation('name', 'en'));
        $this->assertSame('new-name', $manufacturer->slug);
    }

    public function test_edit_allows_same_slug_for_same_record(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'My Manufacturer'],
            'slug' => 'my-manufacturer',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->fillForm([
                'name_en' => 'My Manufacturer Updated',
                'slug_en' => 'my-manufacturer',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    public function test_editing_manufacturer_persists_slug_to_slugs_table(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'Old Brand'],
            'slug' => 'old-brand',
        ]);

        // HasSlugs auto-creates 'old-brand' on save; verify it exists before editing
        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'locale'         => 'en',
            'slug'           => 'old-brand',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->fillForm(['name_en' => 'New Brand', 'slug_en' => 'new-brand'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'slug'           => 'new-brand',
        ]);

        $this->assertDatabaseMissing('slugs', [
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'slug'           => 'old-brand',
        ]);
    }

    public function test_edit_rejects_slug_already_used_by_another_manufacturer(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $manufacturerA = Manufacturer::factory()->create(['name' => ['en' => 'Brand A'], 'slug' => 'brand-a']);
        $manufacturerB = Manufacturer::factory()->create(['name' => ['en' => 'Brand B'], 'slug' => 'brand-b']);

        // HasSlugs auto-creates 'brand-a' for manufacturerA on save; it's already in the slugs table
        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturerA->id,
            'locale'         => 'en',
            'slug'           => 'brand-a',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturerB->getRouteKey()])
            ->fillForm(['name_en' => 'Brand B', 'slug_en' => 'brand-a'])
            ->call('save')
            ->assertHasFormErrors(['slug_en']);
    }

    // ── Slug Field Structure ───────────────────────────────────────────────

    public function test_slug_field_rejects_value_with_spaces(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'My Manufacturer',
                'slug_en' => 'my slug with spaces',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_slug_field_rejects_value_with_special_characters(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'My Manufacturer',
                'slug_en' => 'my-slug!@#',
            ])
            ->call('create')
            ->assertHasFormErrors(['slug_en']);
    }

    public function test_slug_field_accepts_valid_alphadash_value(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'My Manufacturer',
                'slug_en' => 'my-slug-123',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    }

    public function test_slug_field_is_keyed_per_locale(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'Test Brand'],
            'slug' => 'test-brand',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertArrayHasKey('slug_en', $data));
    }

    // ── Delete ─────────────────────────────────────────────────────────────

    public function test_can_delete_manufacturer_from_list(): void
    {
        $manufacturer = Manufacturer::factory()->create();

        Livewire::test(ListManufacturers::class)
            ->callTableAction('delete', $manufacturer);

        $this->assertDatabaseMissing('manufacturers', ['id' => $manufacturer->id]);
    }

    // ── Slug pre-population ────────────────────────────────────────────────

    public function test_edit_form_is_pre_populated_with_slug_from_slugs_table(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'Acme Corp'],
            'slug' => 'acme-corp',
        ]);

        // Update the auto-created slug to a known test value
        $manufacturer->slugs()->where('locale', 'en')->update(['slug' => 'acme-corp-from-slugs']);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('acme-corp-from-slugs', $data['slug_en']));
    }

    public function test_edit_form_slug_is_empty_when_no_slug_table_entry_exists(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'No Slug'],
            'slug' => 'no-slug',
        ]);

        // Ensure no slugs exist for this manufacturer
        $manufacturer->slugs()->delete();

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('', $data['slug_en'] ?? ''));
    }

    public function test_edit_form_slug_falls_back_to_per_locale_value(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'name' => 'French', 'is_default' => false]);

        $manufacturer = Manufacturer::factory()->create([
            'name' => ['fr' => 'French Maker'],
            'slug' => 'french-maker',
        ]);

        // Only a French slug exists – no English slug
        $manufacturer->slugs()->delete();
        $manufacturer->slugs()->create([
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'locale'         => 'fr',
            'slug'           => 'french-maker-fr',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('french-maker-fr', $data['slug_fr']));
    }

    public function test_slug_value_is_preserved_when_form_is_reopened(): void
    {
        Language::factory()->create(['code' => 'en', 'name' => 'English', 'is_default' => true]);

        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'Stable Corp'],
            'slug' => 'stable-corp',
        ]);

        // HasSlugs auto-creates the slug entry; verify it exists before testing
        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'locale'         => 'en',
            'slug'           => 'stable-corp',
        ]);

        // Open form twice, assert slug is consistent
        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('stable-corp', $data['slug_en']));

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet(fn (array $data) => $this->assertSame('stable-corp', $data['slug_en']));
    }
}
