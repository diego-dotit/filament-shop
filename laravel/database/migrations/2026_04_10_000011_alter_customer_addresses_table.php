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
     * Replaces the plain string country/city columns with normalised FK columns
     * (country_id, zone_id, city_id) and adds address-type / owner fields
     * (shipping, business, firstname, lastname, company, company_id, tax_id).
     *
     * SQLite cannot add NOT-NULL columns or drop columns in-place on a table
     * that already carries FK constraints, so we use a full table recreation
     * (the standard 12-step SQLite schema-change procedure) on that driver.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "customer_addresses_new" (
                    "id"             integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "customer_id"    integer NOT NULL,
                    "shipping"       tinyint NOT NULL DEFAULT 0,
                    "business"       tinyint NOT NULL DEFAULT 0,
                    "firstname"      varchar NOT NULL DEFAULT \'\',
                    "lastname"       varchar NOT NULL DEFAULT \'\',
                    "company"        varchar NULL,
                    "company_id"     varchar NULL,
                    "tax_id"         varchar NULL,
                    "country_id"     integer NULL,
                    "zone_id"        integer NULL,
                    "city_id"        integer NULL,
                    "address_line_1" varchar NOT NULL,
                    "address_line_2" varchar NULL,
                    "postcode"       varchar NOT NULL,
                    "created_at"     datetime NULL,
                    "updated_at"     datetime NULL,
                    FOREIGN KEY ("customer_id") REFERENCES "customers" ("id") ON DELETE CASCADE,
                    FOREIGN KEY ("country_id") REFERENCES "countries" ("id") ON DELETE RESTRICT,
                    FOREIGN KEY ("zone_id") REFERENCES "zones" ("id") ON DELETE RESTRICT,
                    FOREIGN KEY ("city_id") REFERENCES "cities" ("id") ON DELETE RESTRICT
                )
            ');

            DB::statement('
                INSERT INTO "customer_addresses_new"
                    ("id", "customer_id", "shipping", "business",
                     "firstname", "lastname", "company", "company_id", "tax_id",
                     "country_id", "zone_id", "city_id",
                     "address_line_1", "address_line_2", "postcode",
                     "created_at", "updated_at")
                SELECT
                    "id", "customer_id", 0, 0,
                    \'\', \'\', NULL, NULL, NULL,
                    0, NULL, NULL,
                    "address_line_1", "address_line_2", "postcode",
                    "created_at", "updated_at"
                FROM "customer_addresses"
            ');

            DB::statement('DROP TABLE "customer_addresses"');
            DB::statement('ALTER TABLE "customer_addresses_new" RENAME TO "customer_addresses"');
            DB::statement('CREATE INDEX "customer_addresses_customer_id_index" ON "customer_addresses" ("customer_id")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL path ---------------------------------------------------------------
        // Step 1: add new columns
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->tinyInteger('shipping')->default(0)->after('customer_id');
            $table->tinyInteger('business')->default(0)->after('shipping');
            $table->string('firstname')->default('')->after('business');
            $table->string('lastname')->default('')->after('firstname');
            $table->string('company')->nullable()->after('lastname');
            $table->string('company_id')->nullable()->after('company');
            $table->string('tax_id')->nullable()->after('company_id');
            $table->unsignedBigInteger('country_id')->nullable()->after('tax_id');
            $table->unsignedBigInteger('zone_id')->nullable()->after('country_id');
            $table->unsignedBigInteger('city_id')->nullable()->after('zone_id');
        });

        // Step 2: add FK constraints for the new relational columns
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            $table->foreign('zone_id')->references('id')->on('zones')->onDelete('restrict');
            $table->foreign('city_id')->references('id')->on('cities')->onDelete('restrict');
        });

        // Step 3: remove the old plain-string country and city columns
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn(['country', 'city']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the original plain string country/city columns and removes all
     * columns added by up().  SQLite uses the same 12-step recreation approach.
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "customer_addresses_new" (
                    "id"             integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "customer_id"    integer NOT NULL,
                    "country"        varchar NOT NULL DEFAULT \'\',
                    "city"           varchar NOT NULL DEFAULT \'\',
                    "address_line_1" varchar NOT NULL,
                    "address_line_2" varchar NULL,
                    "postcode"       varchar NOT NULL,
                    "created_at"     datetime NULL,
                    "updated_at"     datetime NULL,
                    FOREIGN KEY ("customer_id") REFERENCES "customers" ("id") ON DELETE CASCADE
                )
            ');

            DB::statement('
                INSERT INTO "customer_addresses_new"
                    ("id", "customer_id", "country", "city",
                     "address_line_1", "address_line_2", "postcode",
                     "created_at", "updated_at")
                SELECT
                    "id", "customer_id", \'\', \'\',
                    "address_line_1", "address_line_2", "postcode",
                    "created_at", "updated_at"
                FROM "customer_addresses"
            ');

            DB::statement('DROP TABLE "customer_addresses"');
            DB::statement('ALTER TABLE "customer_addresses_new" RENAME TO "customer_addresses"');
            DB::statement('CREATE INDEX "customer_addresses_customer_id_index" ON "customer_addresses" ("customer_id")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL path ---------------------------------------------------------------
        // Step 1: drop FK constraints before removing FK columns
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropForeign(['country_id']);
            $table->dropForeign(['zone_id']);
            $table->dropForeign(['city_id']);
        });

        // Step 2: restore the old plain-string columns
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->string('country')->default('')->after('customer_id');
            $table->string('city')->default('')->after('country');
        });

        // Step 3: drop all new columns added by up()
        Schema::table('customer_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'shipping', 'business',
                'firstname', 'lastname',
                'company', 'company_id', 'tax_id',
                'country_id', 'zone_id', 'city_id',
            ]);
        });
    }
};
