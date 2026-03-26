<?php

namespace Tests\Feature;

use Spatie\Translatable\Facades\Translatable;
use Tests\TestCase;

class TranslatableSetupTest extends TestCase
{
    public function test_translatable_config_file_exists(): void
    {
        $this->assertFileExists(config_path('translatable.php'));
    }

    public function test_translatable_config_has_fallback_locale_set_to_en(): void
    {
        $this->assertSame('en', config('translatable.fallback_locale'));
    }

    public function test_translatable_config_has_supported_locales(): void
    {
        $locales = config('translatable.locales');

        $this->assertIsArray($locales);
        $this->assertNotEmpty($locales);
        $this->assertContains('en', $locales);
    }

    public function test_translatable_singleton_is_configured_with_fallback_locale(): void
    {
        $translatable = app(\Spatie\Translatable\Translatable::class);

        $this->assertSame('en', $translatable->fallbackLocale);
    }
}
