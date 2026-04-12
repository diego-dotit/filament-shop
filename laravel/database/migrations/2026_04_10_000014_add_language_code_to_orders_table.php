<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a language_code column to the orders table so that each order
     * can record the storefront language active at the time of purchase
     * (e.g. 'en', 'fr', 'de').  Nullable so that existing rows and guest
     * orders without an explicit language remain valid.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('language_code')->nullable()->after('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('language_code');
        });
    }
};
