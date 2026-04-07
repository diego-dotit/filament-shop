<?php

namespace Tests\Feature\Settings;

use App\Settings\GeneralBlogSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelSettings\Settings;
use Tests\TestCase;

class GeneralBlogSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_blog_settings_extends_spatie_settings(): void
    {
        $this->assertInstanceOf(Settings::class, app(GeneralBlogSettings::class));
    }

    public function test_general_blog_settings_group_returns_correct_string(): void
    {
        $this->assertEquals('general_blog_settings', GeneralBlogSettings::group());
    }

    public function test_translatable_fields_are_arrays_with_empty_defaults(): void
    {
        $settings = app(GeneralBlogSettings::class);

        $this->assertIsArray($settings->blog_title);
        $this->assertIsArray($settings->blog_description);
        $this->assertIsArray($settings->meta_title);
        $this->assertIsArray($settings->meta_description);
        $this->assertIsArray($settings->meta_keywords);
        $this->assertIsArray($settings->slug);
    }

    public function test_articles_per_page_defaults_to_10(): void
    {
        $settings = app(GeneralBlogSettings::class);

        $this->assertSame(10, $settings->articles_per_page);
    }

    public function test_settings_can_be_persisted_and_retrieved(): void
    {
        $settings = app(GeneralBlogSettings::class);
        $settings->blog_title = ['en' => 'My Blog', 'ro' => 'Blogul Meu'];
        $settings->articles_per_page = 5;
        $settings->save();

        // Re-resolve from container to verify persistence
        $this->app->forgetInstance(GeneralBlogSettings::class);
        $reloaded = app(GeneralBlogSettings::class);

        $this->assertEquals(['en' => 'My Blog', 'ro' => 'Blogul Meu'], $reloaded->blog_title);
        $this->assertSame(5, $reloaded->articles_per_page);
    }

    public function test_settings_group_is_unique_identifier(): void
    {
        $this->assertIsString(GeneralBlogSettings::group());
        $this->assertNotEmpty(GeneralBlogSettings::group());
    }
}
