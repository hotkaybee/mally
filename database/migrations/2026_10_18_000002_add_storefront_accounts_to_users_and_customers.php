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
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email')->index();
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
            $table->dropIndex(['phone']);
            $table->dropColumn(['phone', 'telegram_id', 'phone_verified_at', 'is_admin']);
        });
    }
};
