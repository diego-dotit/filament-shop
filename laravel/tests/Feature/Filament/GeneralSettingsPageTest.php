<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Filament\Pages\GeneralSettingsPage;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        Language::factory()->create([
            'code' => 'en',
            'name' => 'English',
            'is_default' => true,
        ]);

        Language::factory()->create([
            'code' => 'de',
            'name' => 'German',
            'is_default' => false,
        ]);
    }

    // ── Page Load ─────────────────────────────────────────────────────────

    public function test_page_loads_successfully(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->assertSuccessful();
    }

    // ── Form Field Existence ───────────────────────────────────────────────

    public function test_form_has_site_title_fields_per_language(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->assertFormFieldExists('site_title_en')
            ->assertFormFieldExists('site_title_de');
    }

    public function test_form_has_meta_title_fields_per_language(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->assertFormFieldExists('meta_title_en')
            ->assertFormFieldExists('meta_title_de');
    }

    public function test_form_has_meta_description_fields_per_language(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->assertFormFieldExists('meta_description_en')
            ->assertFormFieldExists('meta_description_de');
    }

    public function test_form_has_meta_keywords_fields_per_language(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->assertFormFieldExists('meta_keywords_en')
            ->assertFormFieldExists('meta_keywords_de');
    }

    // ── Form Submission: Translatable Fields ──────────────────────────────

    public function test_submitting_form_persists_site_title_per_locale(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->fillForm([
                'site_title_en' => 'My Shop',
                'site_title_de' => 'Mein Shop',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralSettings::class);
        $this->assertSame('My Shop', $settings->site_title['en']);
        $this->assertSame('Mein Shop', $settings->site_title['de']);
    }

    public function test_submitting_form_persists_meta_title_per_locale(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->fillForm([
                'meta_title_en' => 'Shop Meta EN',
                'meta_title_de' => 'Shop Meta DE',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralSettings::class);
        $this->assertSame('Shop Meta EN', $settings->meta_title['en']);
        $this->assertSame('Shop Meta DE', $settings->meta_title['de']);
    }

    public function test_submitting_form_persists_meta_description_per_locale(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->fillForm([
                'meta_description_en' => 'Shop description in English',
                'meta_description_de' => 'Shop-Beschreibung auf Deutsch',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralSettings::class);
        $this->assertSame('Shop description in English', $settings->meta_description['en']);
        $this->assertSame('Shop-Beschreibung auf Deutsch', $settings->meta_description['de']);
    }

    public function test_submitting_form_persists_meta_keywords_per_locale(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->fillForm([
                'meta_keywords_en' => 'shop, products, buy',
                'meta_keywords_de' => 'Shop, Produkte, kaufen',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralSettings::class);
        $this->assertSame('shop, products, buy', $settings->meta_keywords['en']);
        $this->assertSame('Shop, Produkte, kaufen', $settings->meta_keywords['de']);
    }

    public function test_submitting_form_persists_non_array_properties(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->fillForm([
                'is_open' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralSettings::class);
        $this->assertFalse($settings->is_open);
    }

    // ── Settings Reload ───────────────────────────────────────────────────

    public function test_reloading_page_shows_previously_saved_values(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->fillForm([
                'site_title_en' => 'Persisted Shop Title',
                'site_title_de' => 'Gespeicherter Shop-Titel',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Livewire::test(GeneralSettingsPage::class)
            ->assertFormSet([
                'site_title_en' => 'Persisted Shop Title',
                'site_title_de' => 'Gespeicherter Shop-Titel',
            ]);
    }

    // ── Form Field Existence: Non-array Fields ─────────────────────────────

    public function test_form_has_logo_field(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->assertFormFieldExists('logo');
    }

    public function test_form_has_favicon_field(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->assertFormFieldExists('favicon');
    }

    public function test_form_has_is_open_field(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->assertFormFieldExists('is_open');
    }

    // ── Form Submission: Non-array Properties ─────────────────────────────

    public function test_submitting_form_persists_logo_as_null_when_not_provided(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralSettings::class);
        $this->assertNull($settings->logo);
    }

    public function test_submitting_form_persists_favicon_as_null_when_not_provided(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralSettings::class);
        $this->assertNull($settings->favicon);
    }

    public function test_submitting_form_persists_is_open_as_true_by_default(): void
    {
        Livewire::test(GeneralSettingsPage::class)
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralSettings::class);
        $this->assertTrue($settings->is_open);
    }
}
