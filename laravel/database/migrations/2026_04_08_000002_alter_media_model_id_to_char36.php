<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change media.model_id from unsignedBigInteger to char(36) so that
     * the polymorphic relationship works with UUID primary keys.
     *
     * The morphs() helper creates a composite index on (model_type, model_id).
     * We drop that index, change the column type, then recreate the index.
     *
     * No foreign-key constraints exist on model_id (spatie/laravel-medialibrary
     * intentionally omits them so any model can own media).
     *
     * SQLite cannot ALTER most column types, so we fall back to the standard
     * 12-step table-recreation procedure on that driver.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "media_new" (
                    "id"                    integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "model_type"            varchar(255) NOT NULL,
                    "model_id"              char(36)     NOT NULL,
                    "uuid"                  char(36)     NULL,
                    "collection_name"       varchar(255) NOT NULL,
                    "name"                  varchar(255) NOT NULL,
                    "file_name"             varchar(255) NOT NULL,
                    "mime_type"             varchar(255) NULL,
                    "disk"                  varchar(255) NOT NULL,
                    "conversions_disk"      varchar(255) NULL,
                    "size"                  integer      NOT NULL,
                    "manipulations"         text         NOT NULL,
                    "custom_properties"     text         NOT NULL,
                    "generated_conversions" text         NOT NULL,
                    "responsive_images"     text         NOT NULL,
                    "order_column"          integer      NULL,
                    "created_at"            datetime     NULL,
                    "updated_at"            datetime     NULL
                )
            ');

            DB::statement('
                INSERT INTO "media_new"
                    ("id","model_type","model_id","uuid","collection_name","name",
                     "file_name","mime_type","disk","conversions_disk","size",
                     "manipulations","custom_properties","generated_conversions",
                     "responsive_images","order_column","created_at","updated_at")
                SELECT "id","model_type","model_id","uuid","collection_name","name",
                       "file_name","mime_type","disk","conversions_disk","size",
                       "manipulations","custom_properties","generated_conversions",
                       "responsive_images","order_column","created_at","updated_at"
                FROM "media"
            ');

            DB::statement('DROP TABLE "media"');
            DB::statement('ALTER TABLE "media_new" RENAME TO "media"');
            DB::statement('CREATE UNIQUE INDEX "media_uuid_unique" ON "media" ("uuid")');
            DB::statement('CREATE INDEX "media_model_type_model_id_index" ON "media" ("model_type","model_id")');
            DB::statement('CREATE INDEX "media_order_column_index" ON "media" ("order_column")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL: drop the composite index, change the column, recreate index
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('media_model_type_model_id_index');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->char('model_id', 36)->change();
        });

        Schema::table('media', function (Blueprint $table) {
            $table->index(['model_type', 'model_id']);
        });
    }

    /**
     * Reverse the migration: change model_id back to unsignedBigInteger.
     *
     * NOTE: if existing model_id values are UUID strings they will be truncated
     * to 0 on MySQL when converted back to BIGINT UNSIGNED. This is intentional
     * and acceptable — the down() method exists only to allow CI rollback testing
     * in clean-database environments.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "media_new" (
                    "id"                    integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "model_type"            varchar(255) NOT NULL,
                    "model_id"              integer      NOT NULL,
                    "uuid"                  char(36)     NULL,
                    "collection_name"       varchar(255) NOT NULL,
                    "name"                  varchar(255) NOT NULL,
                    "file_name"             varchar(255) NOT NULL,
                    "mime_type"             varchar(255) NULL,
                    "disk"                  varchar(255) NOT NULL,
                    "conversions_disk"      varchar(255) NULL,
                    "size"                  integer      NOT NULL,
                    "manipulations"         text         NOT NULL,
                    "custom_properties"     text         NOT NULL,
                    "generated_conversions" text         NOT NULL,
                    "responsive_images"     text         NOT NULL,
                    "order_column"          integer      NULL,
                    "created_at"            datetime     NULL,
                    "updated_at"            datetime     NULL
                )
            ');

            DB::statement('
                INSERT INTO "media_new"
                    ("id","model_type","model_id","uuid","collection_name","name",
                     "file_name","mime_type","disk","conversions_disk","size",
                     "manipulations","custom_properties","generated_conversions",
                     "responsive_images","order_column","created_at","updated_at")
                SELECT "id","model_type","model_id","uuid","collection_name","name",
                       "file_name","mime_type","disk","conversions_disk","size",
                       "manipulations","custom_properties","generated_conversions",
                       "responsive_images","order_column","created_at","updated_at"
                FROM "media"
            ');

            DB::statement('DROP TABLE "media"');
            DB::statement('ALTER TABLE "media_new" RENAME TO "media"');
            DB::statement('CREATE UNIQUE INDEX "media_uuid_unique" ON "media" ("uuid")');
            DB::statement('CREATE INDEX "media_model_type_model_id_index" ON "media" ("model_type","model_id")');
            DB::statement('CREATE INDEX "media_order_column_index" ON "media" ("order_column")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('media_model_type_model_id_index');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->unsignedBigInteger('model_id')->change();
        });

        Schema::table('media', function (Blueprint $table) {
            $table->index(['model_type', 'model_id']);
        });
    }
};
