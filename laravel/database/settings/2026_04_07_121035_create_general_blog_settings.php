<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general_blog_settings.blog_title', []);
        $this->migrator->add('general_blog_settings.blog_description', []);
        $this->migrator->add('general_blog_settings.meta_title', []);
        $this->migrator->add('general_blog_settings.meta_description', []);
        $this->migrator->add('general_blog_settings.meta_keywords', []);
        $this->migrator->add('general_blog_settings.slug', []);
        $this->migrator->add('general_blog_settings.articles_per_page', 10);
    }
};
