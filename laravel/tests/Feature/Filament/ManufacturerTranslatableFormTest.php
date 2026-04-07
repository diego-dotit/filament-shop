<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Domains\Manufacturer\Models\Manufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\CreateManufacturer;
use App\Filament\Resources\ManufacturerResource\Pages\EditManufacturer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManufacturerTranslatableFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Language $defaultLanguage;
    private Language $secondLanguage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        $this->defaultLanguage = Language::factory()->create([
            'code'       => 'en',
            'name'       => 'English',
            'is_default' => true,
        ]);

        $this->secondLanguage = Language::factory()->create([
            'code'       => 'de',
            'name'       => 'German',
            'is_default' => false,
        ]);
    }

    // ── Form Field Existence ───────────────────────────────────────────────

    public function test_create_form_has_name_fields_per_language(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->assertFormFieldExists('name_en')
            ->assertFormFieldExists('name_de');
    }

    public function test_create_form_has_description_rich_editor_per_language(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->assertFormFieldExists('description_en')
            ->assertFormFieldExists('description_de');
    }

    public function test_create_form_has_slug_fields_per_language(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    public function test_create_form_has_meta_title_fields_per_language(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->assertFormFieldExists('meta_title_en')
            ->assertFormFieldExists('meta_title_de');
    }

    public function test_create_form_has_meta_description_fields_per_language(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->assertFormFieldExists('meta_description_en')
            ->assertFormFieldExists('meta_description_de');
    }

    public function test_create_form_has_meta_keywords_fields_per_language(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->assertFormFieldExists('meta_keywords_en')
            ->assertFormFieldExists('meta_keywords_de');
    }

    public function test_create_form_has_thumbnail_upload_field(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->assertFormFieldExists('thumbnail');
    }

    // ── Create with Translations ───────────────────────────────────────────

    public function test_creating_manufacturer_saves_name_translations(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'Acme Corporation',
                'name_de' => 'Acme GmbH',
                'slug_en' => 'acme-corporation',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $manufacturer = Manufacturer::where('slug', 'acme-corporation')->firstOrFail();

        $this->assertSame('Acme Corporation', $manufacturer->getTranslation('name', 'en'));
        $this->assertSame('Acme GmbH', $manufacturer->getTranslation('name', 'de'));
    }

    public function test_creating_manufacturer_saves_description_translations(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en'        => 'Brand X',
                'slug_en'        => 'brand-x',
                'description_en' => 'English description',
                'description_de' => 'Deutsche Beschreibung',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $manufacturer = Manufacturer::where('slug', 'brand-x')->firstOrFail();

        $this->assertSame('English description', $manufacturer->getTranslation('description', 'en'));
        $this->assertSame('Deutsche Beschreibung', $manufacturer->getTranslation('description', 'de'));
    }

    public function test_creating_manufacturer_saves_meta_translations(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en'             => 'Meta Brand',
                'slug_en'             => 'meta-brand',
                'meta_title_en'       => 'Meta Title EN',
                'meta_description_en' => 'Meta Desc EN',
                'meta_keywords_en'    => 'keyword1, keyword2',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $manufacturer = Manufacturer::where('slug', 'meta-brand')->firstOrFail();

        $this->assertSame('Meta Title EN', $manufacturer->getTranslation('meta_title', 'en'));
        $this->assertSame('Meta Desc EN', $manufacturer->getTranslation('meta_description', 'en'));
        $this->assertSame('keyword1, keyword2', $manufacturer->getTranslation('meta_keywords', 'en'));
    }

    public function test_creating_manufacturer_persists_per_locale_slugs_to_slugs_table(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => 'Dual Slug Brand',
                'slug_en' => 'dual-slug-brand',
                'slug_de' => 'zweisprachig-marke',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $manufacturer = Manufacturer::where('slug', 'dual-slug-brand')->firstOrFail();

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'locale'         => 'en',
            'slug'           => 'dual-slug-brand',
        ]);

        $this->assertDatabaseHas('slugs', [
            'sluggable_type' => Manufacturer::class,
            'sluggable_id'   => $manufacturer->id,
            'locale'         => 'de',
            'slug'           => 'zweisprachig-marke',
        ]);
    }

    public function test_create_requires_name_in_default_language(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm([
                'name_en' => '',
                'name_de' => 'Nur Deutsch',
                'slug_en' => 'some-slug',
            ])
            ->call('create')
            ->assertHasFormErrors(['name_en']);
    }

    // ── Edit Pre-population ────────────────────────────────────────────────

    public function test_edit_form_pre_populates_name_translations(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'English Name', 'de' => 'Deutsches Name'],
            'slug' => 'english-name',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet([
                'name_en' => 'English Name',
                'name_de' => 'Deutsches Name',
            ]);
    }

    public function test_edit_form_pre_populates_description_translations(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name'        => ['en' => 'Brand EN'],
            'slug'        => 'brand-en',
            'description' => ['en' => 'English desc', 'de' => 'Deutsche Beschreibung'],
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet([
                'description_en' => 'English desc',
                'description_de' => 'Deutsche Beschreibung',
            ]);
    }

    public function test_edit_form_pre_populates_slug_per_locale(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'Slug Brand'],
            'slug' => 'slug-brand',
        ]);

        $manufacturer->slugs()->updateOrCreate(
            ['locale' => 'en'],
            ['slug'   => 'slug-brand-en']
        );
        $manufacturer->slugs()->updateOrCreate(
            ['locale' => 'de'],
            ['slug'   => 'slug-marke-de']
        );

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->assertFormSet([
                'slug_en' => 'slug-brand-en',
                'slug_de' => 'slug-marke-de',
            ]);
    }

    public function test_editing_manufacturer_updates_name_translations(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name' => ['en' => 'Original EN', 'de' => 'Original DE'],
            'slug' => 'original-en',
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->fillForm([
                'name_en' => 'Updated EN',
                'name_de' => 'Aktualisiert DE',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $manufacturer->refresh();
        $this->assertSame('Updated EN', $manufacturer->getTranslation('name', 'en'));
        $this->assertSame('Aktualisiert DE', $manufacturer->getTranslation('name', 'de'));
    }

    public function test_editing_manufacturer_updates_meta_translations(): void
    {
        $manufacturer = Manufacturer::factory()->create([
            'name'       => ['en' => 'Meta Edit Brand'],
            'slug'       => 'meta-edit-brand',
            'meta_title' => ['en' => 'Old Title'],
        ]);

        Livewire::test(EditManufacturer::class, ['record' => $manufacturer->getRouteKey()])
            ->fillForm([
                'meta_title_en'       => 'New Title',
                'meta_description_en' => 'New Meta Desc',
                'meta_keywords_en'    => 'new, keywords',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $manufacturer->refresh();
        $this->assertSame('New Title', $manufacturer->getTranslation('meta_title', 'en'));
        $this->assertSame('New Meta Desc', $manufacturer->getTranslation('meta_description', 'en'));
        $this->assertSame('new, keywords', $manufacturer->getTranslation('meta_keywords', 'en'));
    }

    public function test_slug_auto_generates_from_name_per_locale(): void
    {
        Livewire::test(CreateManufacturer::class)
            ->fillForm(['name_en' => 'Auto Slug Brand'])
            ->assertFormSet(fn (array $data) => $this->assertSame('auto-slug-brand', $data['slug_en']));
    }
}
