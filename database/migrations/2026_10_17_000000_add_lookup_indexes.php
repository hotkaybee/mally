<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes only (no data changes): phone lookups at login/registration and
 * the API, and orders by date for the dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('phone');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->index('phone_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['phone_number']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['phone']);
        });
    }
};
