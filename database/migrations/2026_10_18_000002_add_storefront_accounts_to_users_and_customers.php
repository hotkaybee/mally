<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Storefront shoppers sign in as Users, so `users` now holds both admins
     * and shoppers; `is_admin` decides who may open /admin. Each shopper User
     * owns exactly one Customer (customers.user_id).
     *
     * Accounts registered through Telegram have no email, so users.email is
     * optional (still unique). users.phone is stored in the same format as
     * customers.phone_number and is unique: people sign in with it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 32)->nullable()->after('email')->unique();
            $table->string('telegram_id', 32)->nullable()->unique()->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('telegram_id');
            $table->boolean('is_admin')->default(false)->after('password');
        });

        // Until now only admins had a User row (the storefront used customers
        // only), so every existing user keeps access to the panel.
        DB::table('users')->update(['is_admin' => true]);

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['telegram_id']);
            $table->dropUnique(['phone']);
            $table->dropColumn(['phone', 'telegram_id', 'phone_verified_at', 'is_admin']);
        });

        // Users without an email (Telegram sign-ups) must be removed first.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
