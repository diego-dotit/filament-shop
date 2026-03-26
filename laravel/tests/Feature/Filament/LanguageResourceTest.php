<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Filament\Resources\LanguageResource;
use App\Filament\Resources\LanguageResource\Pages\CreateLanguage;
use App\Filament\Resources\LanguageResource\Pages\EditLanguage;
use App\Filament\Resources\LanguageResource\Pages\ListLanguages;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LanguageResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    // -------------------------------------------------------------------------
    // Resource configuration
    // -------------------------------------------------------------------------

    public function test_language_resource_uses_correct_model(): void
    {
        $this->assertSame(Language::class, LanguageResource::getModel());
    }

    // -------------------------------------------------------------------------
    // List page
    // -------------------------------------------------------------------------

    public function test_list_page_renders_successfully(): void
    {
        Livewire::test(ListLanguages::class)
            ->assertSuccessful();
    }

    public function test_list_page_displays_language_records(): void
    {
        $languages = Language::factory()->count(3)->create();

        Livewire::test(ListLanguages::class)
            ->assertCanSeeTableRecords($languages);
    }

    public function test_list_page_paginates_20_per_page(): void
    {
        $component = Livewire::test(ListLanguages::class);

        $this->assertSame(20, $component->get('tableRecordsPerPage'));
    }

    // -------------------------------------------------------------------------
    // Create page
    // -------------------------------------------------------------------------

    public function test_create_page_renders_successfully(): void
    {
        Livewire::test(CreateLanguage::class)
            ->assertSuccessful();
    }

    public function test_create_language_stores_record(): void
    {
        Livewire::test(CreateLanguage::class)
            ->fillForm([
                'code'       => 'en',
                'name'       => 'English',
                'is_default' => false,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('languages', [
            'code' => 'en',
            'name' => 'English',
        ]);
    }

    public function test_create_language_validates_required_fields(): void
    {
        Livewire::test(CreateLanguage::class)
            ->fillForm([
                'code' => '',
                'name' => '',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'required', 'name' => 'required']);
    }

    public function test_create_language_validates_code_max_length(): void
    {
        Livewire::test(CreateLanguage::class)
            ->fillForm([
                'code' => 'toolongcode!',  // 12 chars > max 10
                'name' => 'English',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'max']);
    }

    public function test_create_language_validates_unique_code(): void
    {
        Language::factory()->create(['code' => 'en']);

        Livewire::test(CreateLanguage::class)
            ->fillForm([
                'code' => 'en',
                'name' => 'English Duplicate',
            ])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);
    }

    // -------------------------------------------------------------------------
    // Edit page
    // -------------------------------------------------------------------------

    public function test_edit_page_renders_successfully(): void
    {
        $language = Language::factory()->create();

        Livewire::test(EditLanguage::class, ['record' => $language->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_edit_language_updates_record(): void
    {
        $language = Language::factory()->create(['code' => 'de', 'name' => 'German']);

        Livewire::test(EditLanguage::class, ['record' => $language->getRouteKey()])
            ->fillForm([
                'code' => 'de',
                'name' => 'Deutsch',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('languages', [
            'id'   => $language->id,
            'name' => 'Deutsch',
        ]);
    }

    public function test_edit_allows_same_code_on_same_record(): void
    {
        $language = Language::factory()->create(['code' => 'fr']);

        Livewire::test(EditLanguage::class, ['record' => $language->getRouteKey()])
            ->fillForm([
                'code' => 'fr',
                'name' => 'French Updated',
            ])
            ->call('save')
            ->assertHasNoFormErrors();
    }

    // -------------------------------------------------------------------------
    // Delete action
    // -------------------------------------------------------------------------

    public function test_delete_action_removes_language(): void
    {
        $language = Language::factory()->create();

        Livewire::test(ListLanguages::class)
            ->callTableAction('delete', $language);

        $this->assertDatabaseMissing('languages', ['id' => $language->id]);
    }

    // -------------------------------------------------------------------------
    // Set as Default action
    // -------------------------------------------------------------------------

    public function test_set_as_default_marks_language_as_default(): void
    {
        $language = Language::factory()->create(['is_default' => false]);

        Livewire::test(ListLanguages::class)
            ->callTableAction('setDefault', $language);

        $this->assertDatabaseHas('languages', [
            'id'         => $language->id,
            'is_default' => true,
        ]);
    }

    public function test_set_as_default_clears_previous_default(): void
    {
        $previousDefault = Language::factory()->create(['is_default' => true]);
        $newDefault      = Language::factory()->create(['is_default' => false]);

        Livewire::test(ListLanguages::class)
            ->callTableAction('setDefault', $newDefault);

        $this->assertDatabaseHas('languages', [
            'id'         => $previousDefault->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('languages', [
            'id'         => $newDefault->id,
            'is_default' => true,
        ]);
    }

    public function test_only_one_default_language_exists_after_set_as_default(): void
    {
        Language::factory()->count(5)->create(['is_default' => false]);
        $target = Language::factory()->create(['is_default' => false]);

        Livewire::test(ListLanguages::class)
            ->callTableAction('setDefault', $target);

        $defaultCount = Language::where('is_default', true)->count();
        $this->assertSame(1, $defaultCount);
    }
}
