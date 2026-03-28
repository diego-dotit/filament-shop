<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CategorySlugAutoGenerationTest extends TestCase
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

        // Default language first (is_default = true, so orderBy desc puts it first)
        $this->defaultLanguage = Language::factory()->default()->create([
            'code' => 'en',
            'name' => 'English',
        ]);

        $this->secondLanguage = Language::factory()->create([
            'code' => 'de',
            'name' => 'German',
        ]);
    }

    // ── Auto-generation for Default Language ──────────────────────────────

    public function test_typing_default_language_name_auto_generates_slug(): void
    {
        Livewire::test(CreateCategory::class)
            ->set('data.name.en', 'My New Category')
            ->assertSet('data.slug_en', 'my-new-category');
    }

    public function test_slug_is_slugified_correctly_from_name(): void
    {
        Livewire::test(CreateCategory::class)
            ->set('data.name.en', 'Hello World & Stuff!')
            ->assertSet('data.slug_en', 'hello-world-stuff');
    }

    // ── Non-default Language Does Not Auto-generate ────────────────────────

    public function test_typing_non_default_language_name_does_not_update_slug(): void
    {
        Livewire::test(CreateCategory::class)
            ->set('data.slug_en', '')
            ->set('data.name.de', 'Meine Kategorie')
            ->assertSet('data.slug_en', '');
    }

    public function test_non_default_language_does_not_overwrite_existing_slug(): void
    {
        Livewire::test(CreateCategory::class)
            ->set('data.name.en', 'English Category')
            ->set('data.name.de', 'Deutsche Kategorie')
            ->assertSet('data.slug_en', 'english-category');
    }

    // ── Manual Override Detection ──────────────────────────────────────────

    public function test_manual_slug_edit_prevents_auto_generation(): void
    {
        Livewire::test(CreateCategory::class)
            ->set('data.name.en', 'Original Name')
            ->set('data.slug_en', 'custom-manual-slug')
            // Now changing the name should NOT overwrite the manual slug
            ->set('data.name.en', 'Updated Name')
            ->assertSet('data.slug_en', 'custom-manual-slug');
    }

    public function test_auto_generation_updates_slug_when_it_matches_old_name_slug(): void
    {
        Livewire::test(CreateCategory::class)
            ->set('data.name.en', 'Original Name')
            ->assertSet('data.slug_en', 'original-name')
            // Slug matches the auto-generated value, so it updates with new name
            ->set('data.name.en', 'Updated Name')
            ->assertSet('data.slug_en', 'updated-name');
    }

    // ── Default Language Order ─────────────────────────────────────────────

    public function test_default_language_is_identified_by_is_default_desc_ordering(): void
    {
        // The default language (is_default=true) should be first in Language::orderBy('is_default', 'desc')
        $languages = Language::orderBy('is_default', 'desc')->orderBy('name')->get();

        $this->assertTrue((bool) $languages->first()->is_default);
        $this->assertSame('en', $languages->first()->code);
    }

    // ── Empty Slug Gets Auto-generated ────────────────────────────────────

    public function test_empty_slug_is_auto_generated_from_default_language_name(): void
    {
        Livewire::test(CreateCategory::class)
            ->set('data.slug_en', '')
            ->set('data.name.en', 'Auto Generated')
            ->assertSet('data.slug_en', 'auto-generated');
    }
}
