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
     * Makes customer_id nullable on the orders table so that orders can exist
     * without a linked customer (e.g. guest checkouts, deleted customers).
     *
     * SQLite cannot modify column nullability in-place, so we use the standard
     * 12-step table-recreation procedure on that driver.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "orders_new" (
                    "id"            integer        NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "customer_id"   integer        NULL REFERENCES "customers" ("id") ON DELETE RESTRICT,
                    "status"        varchar        NOT NULL DEFAULT \'pending\',
                    "total_amount"  numeric(12,2)  NOT NULL,
                    "currency_code" varchar(3)     NOT NULL,
                    "exchange_rate" numeric(12,6)  NOT NULL,
                    "created_at"    datetime       NULL,
                    "updated_at"    datetime       NULL
                )
            ');

            DB::statement('
                INSERT INTO "orders_new"
                    ("id","customer_id","status","total_amount","currency_code","exchange_rate","created_at","updated_at")
                SELECT "id","customer_id","status","total_amount","currency_code","exchange_rate","created_at","updated_at"
                FROM "orders"
            ');

            DB::statement('DROP TABLE "orders"');
            DB::statement('ALTER TABLE "orders_new" RENAME TO "orders"');

            DB::statement('CREATE INDEX "orders_status_index" ON "orders" ("status")');
            DB::statement('CREATE INDEX "orders_customer_id_created_at_index" ON "orders" ("customer_id", "created_at")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------
        // Step 1: drop the existing foreign key constraint
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        // Step 2: modify customer_id to be nullable
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });

        // Step 3: recreate the foreign key (nullable, onDelete restrict preserved)
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores customer_id to NOT NULL on the orders table.
     * Assumes all rows already have a non-null customer_id value.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "orders_new" (
                    "id"            integer        NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "customer_id"   integer        NOT NULL REFERENCES "customers" ("id") ON DELETE RESTRICT,
                    "status"        varchar        NOT NULL DEFAULT \'pending\',
                    "total_amount"  numeric(12,2)  NOT NULL,
                    "currency_code" varchar(3)     NOT NULL,
                    "exchange_rate" numeric(12,6)  NOT NULL,
                    "created_at"    datetime       NULL,
                    "updated_at"    datetime       NULL
                )
            ');

            DB::statement('
                INSERT INTO "orders_new"
                    ("id","customer_id","status","total_amount","currency_code","exchange_rate","created_at","updated_at")
                SELECT "id","customer_id","status","total_amount","currency_code","exchange_rate","created_at","updated_at"
                FROM "orders"
            ');

            DB::statement('DROP TABLE "orders"');
            DB::statement('ALTER TABLE "orders_new" RENAME TO "orders"');

            DB::statement('CREATE INDEX "orders_status_index" ON "orders" ("status")');
            DB::statement('CREATE INDEX "orders_customer_id_created_at_index" ON "orders" ("customer_id", "created_at")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------
        // Step 1: drop the nullable foreign key
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        // Step 2: revert customer_id to NOT NULL
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });

        // Step 3: recreate the original foreign key (NOT NULL, onDelete restrict)
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
        });
    }
};
