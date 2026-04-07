<?php

namespace Tests\Feature\Api\Page;

use App\Domains\Language\Models\Language;
use App\Domains\Page\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear default Accept-Language header injected by Symfony test client
        $this->withServerVariables(['HTTP_ACCEPT_LANGUAGE' => '']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function createLanguage(string $code, bool $isDefault = false): Language
    {
        return Language::create([
            'code'       => $code,
            'name'       => strtoupper($code),
            'is_default' => $isDefault,
        ]);
    }

    private function createPage(array $overrides = []): Page
    {
        return Page::create(array_merge([
            'title'            => ['en' => 'Test Page'],
            'description'      => ['en' => 'Test description'],
            'meta_title'       => ['en' => 'Meta Title'],
            'meta_description' => ['en' => 'Meta description'],
            'meta_keywords'    => ['en' => 'keyword1, keyword2'],
            'status'           => 'active',
        ], $overrides));
    }

    // ── GET /api/pages/{slug} — Detail ────────────────────────────────────────

    public function test_show_returns_200_with_page_data_for_valid_slug(): void
    {
        $this->createLanguage('en', true);

        $page = $this->createPage(['title' => ['en' => 'About Us']]);
        $slug = $page->slugs()->where('locale', 'en')->first()->slug;

        $response = $this->getJson("/api/pages/{$slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $page->id)
            ->assertJsonPath('data.slug', $slug);
    }

    public function test_show_response_structure_matches_page_resource(): void
    {
        $this->createLanguage('en', true);

        $page = $this->createPage();
        $slug = $page->slugs()->where('locale', 'en')->first()->slug;

        $response = $this->getJson("/api/pages/{$slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'description',
                    'meta_title',
                    'meta_description',
                    'meta_keywords',
                    'slug',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);
    }

    public function test_show_returns_404_for_non_existent_slug(): void
    {
        $this->createLanguage('en', true);

        $response = $this->getJson('/api/pages/non-existent-slug');

        $response->assertStatus(404);
    }

    public function test_show_returns_404_for_inactive_page(): void
    {
        $this->createLanguage('en', true);

        $page = $this->createPage([
            'title'  => ['en' => 'Inactive Page'],
            'status' => 'inactive',
        ]);
        $slug = $page->slugs()->where('locale', 'en')->first()->slug;

        $response = $this->getJson("/api/pages/{$slug}");

        $response->assertStatus(404);
    }

    public function test_show_resolves_locale_via_accept_language_header(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);

        $page = $this->createPage([
            'title'       => ['en' => 'About Us', 'fr' => 'À propos'],
            'description' => ['en' => 'English desc', 'fr' => 'Description française'],
        ]);
        $slug = $page->slugs()->where('locale', 'en')->first()->slug;

        $response = $this->getJson("/api/pages/{$slug}", ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'À propos')
            ->assertJsonPath('data.description', 'Description française');
    }

    public function test_show_returns_all_translatable_fields_in_requested_locale(): void
    {
        $this->createLanguage('en', true);
        $this->createLanguage('fr', false);

        $page = $this->createPage([
            'title'            => ['en' => 'Contact', 'fr' => 'Contact FR'],
            'description'      => ['en' => 'Contact page', 'fr' => 'Page de contact'],
            'meta_title'       => ['en' => 'Contact Meta', 'fr' => 'Meta Contact FR'],
            'meta_description' => ['en' => 'Contact meta desc', 'fr' => 'Desc meta contact FR'],
            'meta_keywords'    => ['en' => 'contact', 'fr' => 'contact-fr'],
        ]);
        $slug = $page->slugs()->where('locale', 'en')->first()->slug;

        $response = $this->getJson("/api/pages/{$slug}", ['Accept-Language' => 'fr']);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Contact FR')
            ->assertJsonPath('data.description', 'Page de contact')
            ->assertJsonPath('data.meta_title', 'Meta Contact FR')
            ->assertJsonPath('data.meta_description', 'Desc meta contact FR')
            ->assertJsonPath('data.meta_keywords', 'contact-fr');
    }
}
