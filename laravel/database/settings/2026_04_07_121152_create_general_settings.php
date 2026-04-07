<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general_settings.site_title', []);
        $this->migrator->add('general_settings.meta_title', []);
        $this->migrator->add('general_settings.meta_description', []);
        $this->migrator->add('general_settings.meta_keywords', []);
        $this->migrator->add('general_settings.is_open', true);
        $this->migrator->add('general_settings.logo', null);
        $this->migrator->add('general_settings.favicon', null);
    }
};
