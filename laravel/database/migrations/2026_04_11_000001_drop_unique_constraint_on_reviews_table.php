<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Drops the compound unique constraint on (product_id, customer_id) so that
     * a customer can submit multiple reviews for the same product.
     *
     * SQLite cannot drop a unique index that was defined inline in the
     * CREATE TABLE DDL, so we use the standard 12-step table-recreation
     * procedure on that driver.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "reviews_new" (
                    "id"         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "product_id" integer      NOT NULL REFERENCES "products"  ("id") ON DELETE CASCADE,
                    "customer_id" integer     NOT NULL REFERENCES "customers" ("id") ON DELETE CASCADE,
                    "rating"     tinyint      NOT NULL,
                    "comment"    text         NULL,
                    "status"     varchar      NOT NULL DEFAULT \'pending\',
                    "created_at" datetime     NULL,
                    "updated_at" datetime     NULL
                )
            ');

            DB::statement('
                INSERT INTO "reviews_new"
                    ("id","product_id","customer_id","rating","comment","status","created_at","updated_at")
                SELECT "id","product_id","customer_id","rating","comment","status","created_at","updated_at"
                FROM "reviews"
            ');

            DB::statement('DROP TABLE "reviews"');
            DB::statement('ALTER TABLE "reviews_new" RENAME TO "reviews"');

            // Restore all original indexes except the dropped unique constraint
            DB::statement('CREATE INDEX "reviews_status_index" ON "reviews" ("status")');
            DB::statement('CREATE INDEX "reviews_product_id_status_index" ON "reviews" ("product_id", "status")');
            DB::statement('CREATE INDEX "reviews_customer_id_index" ON "reviews" ("customer_id")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the compound unique constraint on (product_id, customer_id).
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "reviews_new" (
                    "id"         integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "product_id" integer      NOT NULL REFERENCES "products"  ("id") ON DELETE CASCADE,
                    "customer_id" integer     NOT NULL REFERENCES "customers" ("id") ON DELETE CASCADE,
                    "rating"     tinyint      NOT NULL,
                    "comment"    text         NULL,
                    "status"     varchar      NOT NULL DEFAULT \'pending\',
                    "created_at" datetime     NULL,
                    "updated_at" datetime     NULL
                )
            ');

            DB::statement('
                INSERT INTO "reviews_new"
                    ("id","product_id","customer_id","rating","comment","status","created_at","updated_at")
                SELECT "id","product_id","customer_id","rating","comment","status","created_at","updated_at"
                FROM "reviews"
            ');

            DB::statement('DROP TABLE "reviews"');
            DB::statement('ALTER TABLE "reviews_new" RENAME TO "reviews"');

            // Restore all original indexes including the unique constraint
            DB::statement('CREATE UNIQUE INDEX "reviews_product_id_customer_id_unique" ON "reviews" ("product_id", "customer_id")');
            DB::statement('CREATE INDEX "reviews_status_index" ON "reviews" ("status")');
            DB::statement('CREATE INDEX "reviews_product_id_status_index" ON "reviews" ("product_id", "status")');
            DB::statement('CREATE INDEX "reviews_customer_id_index" ON "reviews" ("customer_id")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------
        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['product_id', 'customer_id']);
        });
    }
};
