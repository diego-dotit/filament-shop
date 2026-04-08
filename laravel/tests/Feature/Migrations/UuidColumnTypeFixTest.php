<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature tests for T1.3 and T1.4 migrations:
 *  - 2026_04_08_000001_modify_slugs_sluggable_id_to_uuid.php
 *  - 2026_04_08_000001_alter_media_model_id_to_char36.php
 *
 * Verifies column types, data retention, index correctness, and reversibility.
 */
class UuidColumnTypeFixTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Column-type assertions (post-migration state)
    // -------------------------------------------------------------------------

    public function test_slugs_sluggable_id_is_char36_after_migration(): void
    {
        $type = $this->getColumnType('slugs', 'sluggable_id');

        $this->assertStringContainsStringIgnoringCase(
            'char',
            $type,
            "Expected slugs.sluggable_id to be char(36), got: {$type}",
        );
    }

    public function test_media_model_id_is_char36_after_migration(): void
    {
        $type = $this->getColumnType('media', 'model_id');

        $this->assertStringContainsStringIgnoringCase(
            'char',
            $type,
            "Expected media.model_id to be char(36), got: {$type}",
        );
    }

    // -------------------------------------------------------------------------
    // Data-retention: slugs
    // -------------------------------------------------------------------------

    public function test_existing_slug_records_retain_values_after_migration(): void
    {
        $sluggableId = Str::uuid()->toString();

        DB::table('slugs')->insert([
            'sluggable_type' => 'App\\Domains\\Blog\\Models\\BlogArticle',
            'sluggable_id'   => $sluggableId,
            'locale'         => 'en',
            'slug'           => 'test-article-slug',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $row = DB::table('slugs')->where('slug', 'test-article-slug')->first();

        $this->assertNotNull($row, 'Slug record should exist after migration');
        $this->assertEquals($sluggableId, $row->sluggable_id);
        $this->assertEquals('test-article-slug', $row->slug);
        $this->assertEquals('en', $row->locale);
    }

    // -------------------------------------------------------------------------
    // Data-retention: media
    // -------------------------------------------------------------------------

    public function test_existing_media_records_retain_values_after_migration(): void
    {
        $modelId   = Str::uuid()->toString();
        $mediaUuid = Str::uuid()->toString();

        DB::table('media')->insert([
            'model_type'            => 'App\\Domains\\Blog\\Models\\BlogArticle',
            'model_id'              => $modelId,
            'uuid'                  => $mediaUuid,
            'collection_name'       => 'thumbnail',
            'name'                  => 'test-image',
            'file_name'             => 'test-image.jpg',
            'mime_type'             => 'image/jpeg',
            'disk'                  => 'public',
            'size'                  => 1024,
            'manipulations'         => '[]',
            'custom_properties'     => '[]',
            'generated_conversions' => '[]',
            'responsive_images'     => '[]',
        ]);

        $row = DB::table('media')->where('uuid', $mediaUuid)->first();

        $this->assertNotNull($row, 'Media record should exist after migration');
        $this->assertEquals($modelId, $row->model_id);
        $this->assertEquals('test-image', $row->name);
        $this->assertEquals('thumbnail', $row->collection_name);
    }

    // -------------------------------------------------------------------------
    // Index correctness: slugs unique constraint on (type, id, locale)
    // -------------------------------------------------------------------------

    public function test_slugs_unique_index_on_type_id_locale_is_enforced(): void
    {
        $uuid = Str::uuid()->toString();

        DB::table('slugs')->insert([
            'sluggable_type' => 'App\\Models\\Product',
            'sluggable_id'   => $uuid,
            'locale'         => 'en',
            'slug'           => 'my-product',
        ]);

        // Different id — allowed (same type + locale, different id)
        DB::table('slugs')->insert([
            'sluggable_type' => 'App\\Models\\Product',
            'sluggable_id'   => Str::uuid()->toString(),
            'locale'         => 'en',
            'slug'           => 'other-product',
        ]);

        // Exact duplicate (type + id + locale) — must be rejected
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('slugs')->insert([
            'sluggable_type' => 'App\\Models\\Product',
            'sluggable_id'   => $uuid,
            'locale'         => 'en',
            'slug'           => 'duplicate-slug',
        ]);
    }

    // -------------------------------------------------------------------------
    // Index correctness: media composite index on (model_type, model_id)
    // -------------------------------------------------------------------------

    public function test_media_composite_index_allows_multiple_records_per_model(): void
    {
        $modelId = Str::uuid()->toString();

        // Two media records for the same model are perfectly valid (non-unique index)
        DB::table('media')->insert([
            'model_type'            => 'App\\Models\\Product',
            'model_id'              => $modelId,
            'uuid'                  => Str::uuid()->toString(),
            'collection_name'       => 'images',
            'name'                  => 'img-one',
            'file_name'             => 'img-one.jpg',
            'disk'                  => 'public',
            'size'                  => 512,
            'manipulations'         => '[]',
            'custom_properties'     => '[]',
            'generated_conversions' => '[]',
            'responsive_images'     => '[]',
        ]);

        DB::table('media')->insert([
            'model_type'            => 'App\\Models\\Product',
            'model_id'              => $modelId,
            'uuid'                  => Str::uuid()->toString(),
            'collection_name'       => 'documents',
            'name'                  => 'doc-one',
            'file_name'             => 'doc-one.pdf',
            'disk'                  => 'public',
            'size'                  => 2048,
            'manipulations'         => '[]',
            'custom_properties'     => '[]',
            'generated_conversions' => '[]',
            'responsive_images'     => '[]',
        ]);

        $count = DB::table('media')->where('model_id', $modelId)->count();

        $this->assertEquals(2, $count, 'Both media records should be stored (index is non-unique)');
    }

    // -------------------------------------------------------------------------
    // Reversibility: down() restores original column types
    // -------------------------------------------------------------------------

    public function test_migration_down_runs_without_errors_and_restores_integer_column_types(): void
    {
        // Sanity: confirm char(36) before rollback
        $this->assertStringContainsStringIgnoringCase(
            'char',
            $this->getColumnType('slugs', 'sluggable_id'),
        );
        $this->assertStringContainsStringIgnoringCase(
            'char',
            $this->getColumnType('media', 'model_id'),
        );

        // Roll back both migrations (the 2 most recently applied)
        Artisan::call('migrate:rollback', ['--step' => 2]);

        // After rollback, columns should revert to integer (unsignedBigInteger → integer on SQLite)
        $slugsType = $this->getColumnType('slugs', 'sluggable_id');
        $mediaType = $this->getColumnType('media', 'model_id');

        $this->assertStringNotContainsStringIgnoringCase(
            'char',
            $slugsType,
            "After rollback slugs.sluggable_id should not be char, got: {$slugsType}",
        );
        $this->assertStringNotContainsStringIgnoringCase(
            'char',
            $mediaType,
            "After rollback media.model_id should not be char, got: {$mediaType}",
        );

        // Re-apply migrations so the test database is clean for subsequent tests
        Artisan::call('migrate', ['--force' => true]);

        // Confirm char(36) is back after re-migration
        $this->assertStringContainsStringIgnoringCase(
            'char',
            $this->getColumnType('slugs', 'sluggable_id'),
            'After re-migration slugs.sluggable_id should be char(36) again',
        );
        $this->assertStringContainsStringIgnoringCase(
            'char',
            $this->getColumnType('media', 'model_id'),
            'After re-migration media.model_id should be char(36) again',
        );
    }

    // -------------------------------------------------------------------------
    // Helper: retrieve column type string from the active DB driver
    // -------------------------------------------------------------------------

    private function getColumnType(string $table, string $column): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $columns = DB::select("PRAGMA table_info(\"{$table}\")");

            foreach ($columns as $col) {
                if ($col->name === $column) {
                    return $col->type;
                }
            }

            return '';
        }

        // MySQL / MariaDB
        $result = DB::selectOne(
            'SELECT COLUMN_TYPE
               FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = ?
                AND COLUMN_NAME  = ?',
            [$table, $column],
        );

        return $result?->COLUMN_TYPE ?? '';
    }
}
