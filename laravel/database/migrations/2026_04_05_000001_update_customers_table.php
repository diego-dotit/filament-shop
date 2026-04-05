<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the foreign key constraint first
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Drop the user_id column and email index, then add new auth columns
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->dropIndex(['email']);
            $table->string('password')->after('email');
            $table->rememberToken()->after('password');
            $table->timestamp('email_verified_at')->nullable()->after('remember_token');
            $table->unique('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove new auth columns and unique constraint, restore email index
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
