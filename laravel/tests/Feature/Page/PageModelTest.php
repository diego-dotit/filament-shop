<?php

namespace Tests\Feature\Page;

use App\Domains\Language\Models\Language;
use App\Domains\Page\Models\Page;
use App\Domains\Shared\Traits\HasSlugs;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;
use Tests\TestCase;

class PageModelTest extends TestCase
{
    use RefreshDatabase;

    // ── Traits & interfaces ────────────────────────────────────────────────────

    public function test_page_uses_has_slugs_trait(): void
    {
        $this->assertContains(HasSlugs::class, class_uses_recursive(Page::class));
    }

    public function test_page_uses_has_translations_trait(): void
    {
        $this->assertContains(HasTranslations::class, class_uses_recursive(Page::class));
    }

    public function test_page_uses_has_uuids_trait(): void
    {
        $this->assertContains(HasUuids::class, class_uses_recursive(Page::class));
    }

    public function test_page_uses_has_factory_trait(): void
    {
        $this->assertContains(HasFactory::class, class_uses_recursive(Page::class));
    }

    public function test_page_does_not_implement_has_media_interface(): void
    {
        $this->assertNotInstanceOf(HasMedia::class, new Page());
    }

    // ── UUID primary key ──────────────────────────────────────────────────────

    public function test_page_key_type_is_string(): void
    {
        $model = new Page();
        $this->assertSame('string', $model->getKeyType());
    }

    public function test_page_is_not_incrementing(): void
    {
        $model = new Page();
        $this->assertFalse($model->getIncrementing());
    }

    public function test_page_generates_uuid_on_create(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $page = Page::create([
            'title'  => ['en' => 'UUID Test Page'],
            'status' => 'active',
        ]);

        $this->assertNotNull($page->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $page->id,
        );
    }

    // ── $translatable ─────────────────────────────────────────────────────────

    public function test_page_translatable_includes_required_fields(): void
    {
        $page = new Page();

        $expected = ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords'];

        foreach ($expected as $field) {
            $this->assertContains($field, $page->getTranslatableAttributes());
        }
    }

    // ── $fillable ─────────────────────────────────────────────────────────────

    public function test_page_fillable_includes_required_fields(): void
    {
        $page = new Page();

        $expected = ['title', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'status'];

        foreach ($expected as $field) {
            $this->assertContains($field, $page->getFillable());
        }
    }

    // ── $casts ────────────────────────────────────────────────────────────────

    public function test_page_status_is_cast_to_string(): void
    {
        $page = new Page();
        $casts = $page->getCasts();

        $this->assertArrayHasKey('status', $casts);
        $this->assertSame('string', $casts['status']);
    }

    // ── Slug generation from title ────────────────────────────────────────────

    public function test_page_generates_slug_from_title_field(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        $page = Page::create([
            'title'  => ['en' => 'My About Page'],
            'status' => 'active',
        ]);

        $slug = $page->getSlugForLocale('en');

        $this->assertNotNull($slug);
        $this->assertSame('my-about-page', $slug->slug);
    }

    public function test_page_generates_slugs_for_multiple_locales(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);
        Language::factory()->create(['code' => 'fr', 'is_default' => false]);

        $page = Page::create([
            'title'  => ['en' => 'Contact Us', 'fr' => 'Contactez-nous'],
            'status' => 'active',
        ]);

        $slugEn = $page->getSlugForLocale('en');
        $slugFr = $page->getSlugForLocale('fr');

        $this->assertNotNull($slugEn);
        $this->assertSame('contact-us', $slugEn->slug);

        $this->assertNotNull($slugFr);
        $this->assertSame('contactez-nous', $slugFr->slug);
    }

    // ── scopeActive ───────────────────────────────────────────────────────────

    public function test_scope_active_filters_by_active_status(): void
    {
        Language::factory()->create(['code' => 'en', 'is_default' => true]);

        Page::create(['title' => ['en' => 'Active Page'], 'status' => 'active']);
        Page::create(['title' => ['en' => 'Draft Page'], 'status' => 'draft']);

        $activePages = Page::active()->get();

        $this->assertCount(1, $activePages);
        $this->assertSame('active', $activePages->first()->status);
    }

    // ── Table name ────────────────────────────────────────────────────────────

    public function test_page_uses_pages_table(): void
    {
        $model = new Page();
        $this->assertSame('pages', $model->getTable());
    }

    // ── No media, no relationships ────────────────────────────────────────────

    public function test_page_has_no_media_collections(): void
    {
        $this->assertFalse(method_exists(Page::class, 'registerMediaCollections'));
    }

    public function test_page_has_no_blog_articles_relationship(): void
    {
        $this->assertFalse(method_exists(Page::class, 'blogArticles'));
    }
}
