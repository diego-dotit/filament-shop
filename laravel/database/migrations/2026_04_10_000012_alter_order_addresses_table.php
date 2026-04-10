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
     * Replaces the old type/country/city/customer_address_id columns with
     * structured address columns: shipping flag, business flag, name parts,
     * company details, and FK-linked country_id / zone_id / city_id.
     *
     * SQLite cannot drop or alter columns that carry constraints defined in
     * the original CREATE TABLE DDL, so we use full-table recreation on that
     * driver (the standard 12-step SQLite schema-change procedure).
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "order_addresses_new" (
                    "id"            integer  NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "order_id"      integer  NOT NULL,
                    "shipping"      tinyint  NOT NULL DEFAULT 0,
                    "business"      tinyint  NOT NULL DEFAULT 0,
                    "firstname"     varchar  NOT NULL DEFAULT \'\',
                    "lastname"      varchar  NOT NULL DEFAULT \'\',
                    "company"       varchar  NULL,
                    "company_id"    varchar  NULL,
                    "tax_id"        varchar  NULL,
                    "country_id"    integer  NULL,
                    "zone_id"       integer  NULL,
                    "city_id"       integer  NULL,
                    "address_line_1" varchar NOT NULL,
                    "address_line_2" varchar NULL,
                    "postcode"      varchar  NOT NULL,
                    "created_at"    datetime NULL,
                    "updated_at"    datetime NULL,
                    FOREIGN KEY ("order_id") REFERENCES "orders" ("id") ON DELETE CASCADE,
                    FOREIGN KEY ("country_id") REFERENCES "countries" ("id") ON DELETE RESTRICT,
                    FOREIGN KEY ("zone_id") REFERENCES "zones" ("id") ON DELETE RESTRICT,
                    FOREIGN KEY ("city_id") REFERENCES "cities" ("id") ON DELETE RESTRICT
                )
            ');

            DB::statement('
                INSERT INTO "order_addresses_new" (
                    "id", "order_id",
                    "shipping",
                    "business",
                    "firstname", "lastname",
                    "company", "company_id", "tax_id",
                    "country_id", "zone_id", "city_id",
                    "address_line_1", "address_line_2", "postcode",
                    "created_at", "updated_at"
                )
                SELECT
                    "id", "order_id",
                    CASE WHEN "type" = \'shipping\' THEN 1 WHEN "type" = \'billing\' THEN 0 ELSE 0 END,
                    0,
                    \'\', \'\',
                    NULL, NULL, NULL,
                    NULL, NULL, NULL,
                    "address_line_1", "address_line_2", "postcode",
                    "created_at", "updated_at"
                FROM "order_addresses"
            ');

            DB::statement('DROP TABLE "order_addresses"');
            DB::statement('ALTER TABLE "order_addresses_new" RENAME TO "order_addresses"');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------

        // Step 1: drop customer_address_id FK if it exists, then drop the column
        try {
            Schema::table('order_addresses', function (Blueprint $table) {
                $table->dropForeign(['customer_address_id']);
            });
        } catch (\Throwable) {
            // FK may not exist on this installation — continue
        }

        // Step 2: drop legacy columns
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->dropColumn(['type', 'country', 'city', 'customer_address_id']);
        });

        // Step 3: add new columns
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->tinyInteger('shipping')->default(0)->after('order_id');
            $table->tinyInteger('business')->default(0)->after('shipping');
            $table->string('firstname')->default('')->after('business');
            $table->string('lastname')->default('')->after('firstname');
            $table->string('company')->nullable()->after('lastname');
            $table->string('company_id')->nullable()->after('company');
            $table->string('tax_id')->nullable()->after('company_id');
            $table->unsignedBigInteger('country_id')->nullable()->after('tax_id'); // TODO: country_id should be NOT NULL per spec; requires data backfill migration
            $table->unsignedBigInteger('zone_id')->nullable()->after('country_id');
            $table->unsignedBigInteger('city_id')->nullable()->after('zone_id');
        });

        // Step 4: add FK constraints for new geographic columns
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('restrict');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the original type/country/city/customer_address_id columns,
     * deriving the type value from the shipping flag.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "order_addresses_new" (
                    "id"                  integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "order_id"            integer NOT NULL,
                    "type"                varchar NOT NULL DEFAULT \'billing\',
                    "country"             varchar NOT NULL DEFAULT \'\',
                    "city"                varchar NOT NULL DEFAULT \'\',
                    "address_line_1"      varchar NOT NULL,
                    "address_line_2"      varchar NULL,
                    "postcode"            varchar NOT NULL,
                    "customer_address_id" integer NULL,
                    "created_at"          datetime NULL,
                    "updated_at"          datetime NULL,
                    FOREIGN KEY ("order_id") REFERENCES "orders" ("id") ON DELETE CASCADE
                )
            ');

            DB::statement('
                INSERT INTO "order_addresses_new" (
                    "id", "order_id",
                    "type",
                    "country", "city",
                    "address_line_1", "address_line_2", "postcode",
                    "customer_address_id",
                    "created_at", "updated_at"
                )
                SELECT
                    "id", "order_id",
                    CASE WHEN "shipping" = 1 THEN \'shipping\' ELSE \'billing\' END,
                    \'\', \'\',
                    "address_line_1", "address_line_2", "postcode",
                    NULL,
                    "created_at", "updated_at"
                FROM "order_addresses"
            ');

            DB::statement('DROP TABLE "order_addresses"');
            DB::statement('ALTER TABLE "order_addresses_new" RENAME TO "order_addresses"');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------

        // Step 1: drop new FK constraints and geographic columns
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['zone_id']);
            $table->dropForeign(['city_id']);
        });

        Schema::table('order_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'shipping', 'business',
                'firstname', 'lastname',
                'company', 'company_id', 'tax_id',
                'country_id', 'zone_id', 'city_id',
            ]);
        });

        // Step 2: restore legacy columns
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->string('type')->default('billing')->after('order_id');
            $table->string('country')->default('')->after('type');
            $table->string('city')->default('')->after('country');
            $table->unsignedBigInteger('customer_address_id')->nullable()->after('postcode');
        });

        // Step 3: restore customer_address_id FK (nullable, set null on delete)
        Schema::table('order_addresses', function (Blueprint $table) {
            $table->foreign('customer_address_id')
                ->references('id')->on('customer_addresses')
                ->onDelete('set null');
        });
    }
};
