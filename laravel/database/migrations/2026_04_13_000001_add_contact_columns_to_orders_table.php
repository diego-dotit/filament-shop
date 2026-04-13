<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds contact columns to the orders table so that each order can
     * store the customer's first name, last name, email address, and
     * telephone number at the time of purchase.  All columns are nullable
     * so that existing rows remain valid after the migration.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('firstname')->nullable()->after('language_code');
            $table->string('lastname')->nullable()->after('firstname');
            $table->string('email')->nullable()->after('lastname');
            $table->string('telephone')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['firstname', 'lastname', 'email', 'telephone']);
        });
    }
};
