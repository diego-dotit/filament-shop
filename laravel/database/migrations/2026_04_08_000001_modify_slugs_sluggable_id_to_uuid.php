<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change slugs.sluggable_id from unsignedBigInteger to char(36) to support UUID morphs.
     *
     * The slugs table carries two indexes on the sluggable columns:
     *   1. slugs_sluggable_type_sluggable_id_locale_unique  – composite unique (type, id, locale)
     *   2. slugs_sluggable_type_sluggable_id_index          – index created by morphs()
     *
     * There are no foreign key constraints on sluggable_id (morphs() does not add FKs).
     *
     * Order: drop indexes → change column type → recreate indexes.
     *
     * Note: Laravel 12's native grammar handles SQLite column-type changes without table recreation;
     * no driver-specific path is required here, unlike the companion media migration which uses an
     * explicit SQLite table-recreation path due to that migration's more complex schema structure.
     */
    public function up(): void
    {
        // Step 1: drop both indexes that reference sluggable_id
        Schema::table('slugs', function (Blueprint $table) {
            $table->dropUnique(['sluggable_type', 'sluggable_id', 'locale']);
            $table->dropIndex(['sluggable_type', 'sluggable_id']);
        });

        // Step 2: change sluggable_id to char(36) for UUID values
        Schema::table('slugs', function (Blueprint $table) {
            $table->char('sluggable_id', 36)->change();
        });

        // Step 3: recreate both indexes with the corrected column type
        Schema::table('slugs', function (Blueprint $table) {
            $table->index(['sluggable_type', 'sluggable_id']);
            $table->unique(['sluggable_type', 'sluggable_id', 'locale']);
        });
    }

    /**
     * Reverse the migration: change sluggable_id back to unsignedBigInteger.
     *
     * NOTE: if existing sluggable_id values are UUID strings they will be truncated
     * to 0 on MySQL when converted back to BIGINT UNSIGNED. This is intentional
     * and acceptable — the down() method exists only to allow CI rollback testing
     * in clean-database environments.
     */
    public function down(): void
    {
        // Step 1: drop both indexes that reference sluggable_id
        Schema::table('slugs', function (Blueprint $table) {
            $table->dropUnique(['sluggable_type', 'sluggable_id', 'locale']);
            $table->dropIndex(['sluggable_type', 'sluggable_id']);
        });

        // Step 2: revert sluggable_id to unsignedBigInteger
        Schema::table('slugs', function (Blueprint $table) {
            $table->unsignedBigInteger('sluggable_id')->change();
        });

        // Step 3: recreate both indexes with the original column type
        Schema::table('slugs', function (Blueprint $table) {
            $table->index(['sluggable_type', 'sluggable_id']);
            $table->unique(['sluggable_type', 'sluggable_id', 'locale']);
        });
    }
};
