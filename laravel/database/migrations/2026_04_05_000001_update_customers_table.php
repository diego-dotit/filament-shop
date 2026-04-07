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
     * Removes the user_id FK column and adds standalone auth columns
     * (password, remember_token, email_verified_at) so that Customer
     * is an independent auth entity rather than a proxy for User.
     *
     * SQLite cannot drop a column that carries a UNIQUE or FOREIGN KEY
     * constraint in the CREATE TABLE DDL, so we use a full-table recreation
     * (the standard 12-step SQLite schema-change procedure) on that driver.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "customers_new" (
                    "id"                integer      NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "first_name"        varchar      NOT NULL,
                    "last_name"         varchar      NOT NULL,
                    "email"             varchar      NOT NULL,
                    "password"          varchar      NULL     DEFAULT NULL,
                    "remember_token"    varchar(100) NULL,
                    "email_verified_at" datetime     NULL,
                    "phone"             varchar      NULL,
                    "created_at"        datetime     NULL,
                    "updated_at"        datetime     NULL
                )
            ');

            DB::statement('
                INSERT INTO "customers_new"
                    ("id","first_name","last_name","email","phone","created_at","updated_at")
                SELECT "id","first_name","last_name","email","phone","created_at","updated_at"
                FROM "customers"
            ');

            DB::statement('DROP TABLE "customers"');
            DB::statement('ALTER TABLE "customers_new" RENAME TO "customers"');
            DB::statement('CREATE UNIQUE INDEX "customers_email_unique" ON "customers" ("email")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------
        // Step 1: drop the foreign key constraint
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Step 2: drop user_id, fix the email index, add auth columns
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropIndex(['email']);
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('remember_token');
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * SQLite cannot drop columns that carry constraints defined in the original
     * CREATE TABLE DDL, so we use the same 12-step table-recreation approach as up().
     */
    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            DB::statement('
                CREATE TABLE "customers_new" (
                    "id"         integer NOT NULL PRIMARY KEY AUTOINCREMENT,
                    "user_id"    integer NULL,
                    "first_name" varchar NOT NULL,
                    "last_name"  varchar NOT NULL,
                    "email"      varchar NOT NULL,
                    "phone"      varchar NULL,
                    "created_at" datetime NULL,
                    "updated_at" datetime NULL
                )
            ');

            DB::statement('
                INSERT INTO "customers_new"
                    ("id","user_id","first_name","last_name","email","phone","created_at","updated_at")
                SELECT "id", NULL, "first_name","last_name","email","phone","created_at","updated_at"
                FROM "customers"
            ');

            DB::statement('DROP TABLE "customers"');
            DB::statement('ALTER TABLE "customers_new" RENAME TO "customers"');
            DB::statement('CREATE INDEX "customers_email_index" ON "customers" ("email")');

            DB::statement('PRAGMA foreign_keys = ON');

            return;
        }

        // MySQL / PostgreSQL path ---------------------------------------------------
        // Remove new auth columns and unique constraint, restore plain email index
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropColumn(['password', 'remember_token', 'email_verified_at']);
            $table->index('email');
        });

        // Re-add user_id column (nullable to avoid FK violation on existing rows)
        Schema::table('customers', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->unique()->after('id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
