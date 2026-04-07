<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralBlogSettings extends Settings
{
    public array $blog_title = [];

    public array $blog_description = [];

    public array $meta_title = [];

    public array $meta_description = [];

    public array $meta_keywords = [];

    public array $slug = [];

    public int $articles_per_page = 10;

    public static function group(): string
    {
        return 'general_blog_settings';
    }
}
