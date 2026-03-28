<?php

namespace Tests\Feature\Filament;

use App\Domains\Category\Models\Category;
use App\Domains\Language\Models\Language;
use App\Domains\Slug\Models\Slug;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EditCategorySlugPrePopulationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);
    }

    /**
     * When an existing slug entry is present, the slug_{code} field should
     * be pre-populated with its value.
     */
    public function test_edit_form_pre_populates_slug_field_from_slugs_table(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Electronics'],
            'slug' => 'electronics',
        ]);

        // Ensure a slug record exists for 'en'
        $slugRecord = Slug::firstOrCreate(
            [
                'sluggable_type' => Category::class,
                'sluggable_id'   => $category->id,
                'locale'         => 'en',
            ],
            ['slug' => 'electronics']
        );

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['slug_en' => 'electronics']);
    }

    /**
     * When no slug entry exists for a locale, the field should be null/empty.
     */
    public function test_edit_form_leaves_slug_field_empty_when_no_slug_entry_exists(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Books'],
            'slug' => 'books',
        ]);

        // Only create slug for 'en', not 'fr'
        Slug::firstOrCreate(
            [
                'sluggable_type' => Category::class,
                'sluggable_id'   => $category->id,
                'locale'         => 'en',
            ],
            ['slug' => 'books']
        );

        // Ensure no slug for 'fr'
        Slug::where('sluggable_type', Category::class)
            ->where('sluggable_id', $category->id)
            ->where('locale', 'fr')
            ->delete();

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['slug_fr' => null]);
    }

    /**
     * Multiple locales should each be pre-populated from their respective slug records.
     */
    public function test_edit_form_pre_populates_slug_fields_for_multiple_locales(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Clothing', 'fr' => 'Vêtements'],
            'slug' => 'clothing',
        ]);

        // Manually set slug values to known strings (the auto-generated ones may differ)
        Slug::where('sluggable_type', Category::class)
            ->where('sluggable_id', $category->id)
            ->delete();

        Slug::create([
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'clothing-en',
        ]);
        Slug::create([
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'fr',
            'slug'           => 'vetements-fr',
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet([
                'slug_en' => 'clothing-en',
                'slug_fr' => 'vetements-fr',
            ]);
    }

    /**
     * Slug values are preserved when the form is re-opened (no mutation on re-open).
     */
    public function test_slug_values_are_preserved_on_subsequent_form_opens(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $category = Category::factory()->active()->create([
            'name' => ['en' => 'Gadgets'],
            'slug' => 'gadgets',
        ]);

        Slug::where('sluggable_type', Category::class)
            ->where('sluggable_id', $category->id)
            ->delete();

        Slug::create([
            'sluggable_type' => Category::class,
            'sluggable_id'   => $category->id,
            'locale'         => 'en',
            'slug'           => 'gadgets-custom',
        ]);

        // Open form first time
        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(['slug_en' => 'gadgets-custom']);

        // Open form second time — value still present
        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertFormSet(['slug_en' => 'gadgets-custom']);
    }
}
