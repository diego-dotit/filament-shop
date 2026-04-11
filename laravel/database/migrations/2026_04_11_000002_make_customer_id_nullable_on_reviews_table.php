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
     * Makes customer_id nullable on the reviews table so that reviews can exist
     * without a linked customer (e.g. guest reviews, deleted customers).
     *
     * SQLite cannot modify column nullability in-place, so we use the standard
     * table-recreation procedure on that driver.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "reviews_new" (
                    "id"          integer  NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "product_id"  integer  NOT NULL REFERENCES "products" ("id") ON DELETE CASCADE,
                    "customer_id" integer  NULL     REFERENCES "customers" ("id") ON DELETE CASCADE,
                    "rating"      tinyint  NOT NULL,
                    "comment"     text     NULL,
                    "status"      varchar  NOT NULL DEFAULT \'pending\',
                    "created_at"  datetime NULL,
                    "updated_at"  datetime NULL
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

            DB::statement('CREATE INDEX "reviews_status_index" ON "reviews" ("status")');
            DB::statement('CREATE INDEX "reviews_product_id_status_index" ON "reviews" ("product_id", "status")');
            DB::statement('CREATE INDEX "reviews_customer_id_index" ON "reviews" ("customer_id")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------
        // Step 1: drop the existing foreign key constraint (if present)
        $foreignKeys = collect(Schema::getForeignKeys('reviews'));
        $hasFk = $foreignKeys->contains(
            fn ($fk) => in_array('customer_id', $fk['columns'])
        );
        if ($hasFk) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        }

        // Step 2: modify customer_id to be nullable
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        // Step 3: recreate the foreign key with cascadeOnDelete (matches original)
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores customer_id to NOT NULL on the reviews table.
     * Assumes all rows already have a non-null customer_id value.
     */
    public function down(): void
    {
        if (DB::table('reviews')->whereNull('customer_id')->exists()) {
            throw new \RuntimeException(
                'Cannot reverse migration: the reviews table contains rows with NULL customer_id. ' .
                'Remove or reassign those rows before rolling back.'
            );
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "reviews_new" (
                    "id"          integer  NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "product_id"  integer  NOT NULL REFERENCES "products" ("id") ON DELETE CASCADE,
                    "customer_id" integer  NOT NULL REFERENCES "customers" ("id") ON DELETE CASCADE,
                    "rating"      tinyint  NOT NULL,
                    "comment"     text     NULL,
                    "status"      varchar  NOT NULL DEFAULT \'pending\',
                    "created_at"  datetime NULL,
                    "updated_at"  datetime NULL
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

            DB::statement('CREATE INDEX "reviews_status_index" ON "reviews" ("status")');
            DB::statement('CREATE INDEX "reviews_product_id_status_index" ON "reviews" ("product_id", "status")');
            DB::statement('CREATE INDEX "reviews_customer_id_index" ON "reviews" ("customer_id")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------
        // Step 1: drop the nullable foreign key
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        // Step 2: revert customer_id to NOT NULL
        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });

        // Step 3: recreate the original foreign key (NOT NULL, cascadeOnDelete)
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }
};
