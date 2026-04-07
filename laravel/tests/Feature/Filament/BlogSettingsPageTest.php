<?php

namespace Tests\Feature\Filament;

use App\Domains\Language\Models\Language;
use App\Filament\Pages\BlogSettingsPage;
use App\Models\User;
use App\Settings\GeneralBlogSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BlogSettingsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
        $this->actingAs($this->admin);

        Language::factory()->create([
            'code'       => 'en',
            'name'       => 'English',
            'is_default' => true,
        ]);

        Language::factory()->create([
            'code'       => 'de',
            'name'       => 'German',
            'is_default' => false,
        ]);
    }

    // ── Page Load ─────────────────────────────────────────────────────────

    public function test_page_loads_successfully(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertSuccessful();
    }

    // ── Form Field Existence ───────────────────────────────────────────────

    public function test_form_has_blog_title_fields_per_language(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertFormFieldExists('blog_title_en')
            ->assertFormFieldExists('blog_title_de');
    }

    public function test_form_has_blog_description_fields_per_language(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertFormFieldExists('blog_description_en')
            ->assertFormFieldExists('blog_description_de');
    }

    public function test_form_has_articles_per_page_field(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertFormFieldExists('articles_per_page');
    }

    public function test_form_has_slug_fields_per_language(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertFormFieldExists('slug_en')
            ->assertFormFieldExists('slug_de');
    }

    public function test_form_has_meta_title_fields_per_language(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertFormFieldExists('meta_title_en')
            ->assertFormFieldExists('meta_title_de');
    }

    public function test_form_has_meta_description_fields_per_language(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertFormFieldExists('meta_description_en')
            ->assertFormFieldExists('meta_description_de');
    }

    public function test_form_has_meta_keywords_fields_per_language(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertFormFieldExists('meta_keywords_en')
            ->assertFormFieldExists('meta_keywords_de');
    }

    // ── Form Loads Existing Data ───────────────────────────────────────────

    public function test_form_loads_existing_settings_data(): void
    {
        /** @var GeneralBlogSettings $settings */
        $settings = app(GeneralBlogSettings::class);
        $settings->blog_title      = ['en' => 'My Blog', 'de' => 'Mein Blog'];
        $settings->articles_per_page = 5;
        $settings->save();

        Livewire::test(BlogSettingsPage::class)
            ->assertFormSet([
                'blog_title_en'    => 'My Blog',
                'blog_title_de'    => 'Mein Blog',
                'articles_per_page' => 5,
            ]);
    }

    public function test_form_loads_default_articles_per_page_on_first_load(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->assertFormSet(fn (array $data) => $this->assertSame(10, $data['articles_per_page']));
    }

    // ── Form Submission: Translatable Fields ──────────────────────────────

    public function test_submitting_form_saves_blog_title_per_locale(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->fillForm([
                'blog_title_en' => 'English Blog Title',
                'blog_title_de' => 'Deutsches Blog-Titel',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralBlogSettings::class);
        $this->assertSame('English Blog Title', $settings->blog_title['en']);
        $this->assertSame('Deutsches Blog-Titel', $settings->blog_title['de']);
    }

    public function test_submitting_form_persists_articles_per_page(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->fillForm([
                'articles_per_page' => 15,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralBlogSettings::class);
        $this->assertSame(15, $settings->articles_per_page);
    }

    public function test_submitting_form_persists_slug_per_locale(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->fillForm([
                'slug_en' => 'my-blog',
                'slug_de' => 'mein-blog',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralBlogSettings::class);
        $this->assertSame('my-blog', $settings->slug['en']);
        $this->assertSame('mein-blog', $settings->slug['de']);
    }

    public function test_submitting_form_persists_blog_description_html_per_locale(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->fillForm([
                'blog_description_en' => '<p>This is the <strong>blog</strong> description.</p>',
                'blog_description_de' => '<p>Das ist die <em>Blog</em>-Beschreibung.</p>',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralBlogSettings::class);
        $this->assertSame('<p>This is the <strong>blog</strong> description.</p>', $settings->blog_description['en']);
        $this->assertSame('<p>Das ist die <em>Blog</em>-Beschreibung.</p>', $settings->blog_description['de']);
    }

    public function test_submitting_form_persists_meta_title_per_locale(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->fillForm([
                'meta_title_en' => 'Blog Meta Title EN',
                'meta_title_de' => 'Blog Meta-Titel DE',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralBlogSettings::class);
        $this->assertSame('Blog Meta Title EN', $settings->meta_title['en']);
        $this->assertSame('Blog Meta-Titel DE', $settings->meta_title['de']);
    }

    public function test_submitting_form_persists_meta_description_per_locale(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->fillForm([
                'meta_description_en' => 'Blog meta description in English',
                'meta_description_de' => 'Blog-Meta-Beschreibung auf Deutsch',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralBlogSettings::class);
        $this->assertSame('Blog meta description in English', $settings->meta_description['en']);
        $this->assertSame('Blog-Meta-Beschreibung auf Deutsch', $settings->meta_description['de']);
    }

    public function test_submitting_form_persists_meta_keywords_per_locale(): void
    {
        Livewire::test(BlogSettingsPage::class)
            ->fillForm([
                'meta_keywords_en' => 'blog, articles, news',
                'meta_keywords_de' => 'Blog, Artikel, Neuigkeiten',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app()->make(GeneralBlogSettings::class);
        $this->assertSame('blog, articles, news', $settings->meta_keywords['en']);
        $this->assertSame('Blog, Artikel, Neuigkeiten', $settings->meta_keywords['de']);
    }

    // ── Settings Reload ───────────────────────────────────────────────────

    public function test_reloading_page_shows_previously_saved_values(): void
    {
        // Submit the form to save settings
        Livewire::test(BlogSettingsPage::class)
            ->fillForm([
                'blog_title_en'    => 'Persisted Blog Title',
                'articles_per_page' => 20,
                'slug_en'          => 'persisted-blog',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Reload the page (new instance) and verify values are pre-populated
        Livewire::test(BlogSettingsPage::class)
            ->assertFormSet([
                'blog_title_en'    => 'Persisted Blog Title',
                'articles_per_page' => 20,
                'slug_en'          => 'persisted-blog',
            ]);
    }
}
