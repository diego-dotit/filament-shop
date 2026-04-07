<?php

namespace Tests\Feature\Settings;

use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelSettings\Settings;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_settings_class_exists(): void
    {
        $this->assertTrue(class_exists(GeneralSettings::class));
    }

    public function test_general_settings_extends_spatie_settings(): void
    {
        $this->assertTrue(is_subclass_of(GeneralSettings::class, Settings::class));
    }

    public function test_general_settings_group_returns_correct_string(): void
    {
        $this->assertSame('general_settings', GeneralSettings::group());
    }

    public function test_general_settings_can_be_resolved_from_container(): void
    {
        $settings = app(GeneralSettings::class);

        $this->assertInstanceOf(GeneralSettings::class, $settings);
    }

    public function test_translatable_fields_default_to_empty_arrays(): void
    {
        $settings = app(GeneralSettings::class);

        $this->assertSame([], $settings->site_title);
        $this->assertSame([], $settings->meta_title);
        $this->assertSame([], $settings->meta_description);
        $this->assertSame([], $settings->meta_keywords);
    }

    public function test_is_open_defaults_to_true(): void
    {
        $settings = app(GeneralSettings::class);

        $this->assertTrue($settings->is_open);
    }

    public function test_media_fields_default_to_null(): void
    {
        $settings = app(GeneralSettings::class);

        $this->assertNull($settings->logo);
        $this->assertNull($settings->favicon);
    }

    public function test_settings_can_be_saved_and_reloaded(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->site_title = ['en' => 'My Shop', 'es' => 'Mi Tienda'];
        $settings->is_open = false;
        $settings->logo = '/storage/logo.png';
        $settings->save();

        // Reload fresh instance
        $fresh = app(GeneralSettings::class)->refresh();

        $this->assertSame(['en' => 'My Shop', 'es' => 'Mi Tienda'], $fresh->site_title);
        $this->assertFalse($fresh->is_open);
        $this->assertSame('/storage/logo.png', $fresh->logo);
    }
}
