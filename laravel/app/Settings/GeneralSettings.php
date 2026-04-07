<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public array $site_title = [];

    public array $meta_title = [];

    public array $meta_description = [];

    public array $meta_keywords = [];

    public bool $is_open = true;

    public ?string $logo = null;

    public ?string $favicon = null;

    public static function group(): string
    {
        return 'general_settings';
    }
}
