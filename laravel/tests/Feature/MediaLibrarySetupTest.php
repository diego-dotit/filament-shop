<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaLibrarySetupTest extends TestCase
{
    public function test_media_library_config_is_published(): void
    {
        $this->assertFileExists(config_path('media-library.php'));
    }

    public function test_media_library_disk_name_defaults_to_public(): void
    {
        $diskName = config('media-library.disk_name');

        $this->assertSame('public', $diskName);
    }

    public function test_media_library_migration_is_published(): void
    {
        $migrations = File::glob(database_path('migrations/*create_media_table.php'));

        $this->assertNotEmpty($migrations, 'create_media_table migration should be published');
    }

    public function test_storage_public_directory_exists(): void
    {
        $this->assertDirectoryExists(storage_path('app/public'));
    }
}
