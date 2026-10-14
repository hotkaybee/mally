<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - users.is_admin: storefront shoppers now live in `users` too, so the
     *   Filament panel needs an explicit flag. Existing users without a phone
     *   and without a Telegram placeholder email were created with
     *   make:filament-user and keep admin access.
     * - users.telegram_id / phone_verified_at: a Telegram account is linked
     *   only after the bot confirmed it.
     * - customers.user_id: the storefront customer that belongs to a user,
     *   instead of guessing by email or phone.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->string('telegram_id', 32)->nullable()->unique()->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('telegram_id');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->after('id')->constrained()->nullOnDelete();
        });

        DB::table('users')
            ->whereNull('phone')
            ->where('email', 'not like', '%@telegram.mally.local')
            ->update(['is_admin' => true]);

        // Link existing shopper accounts to the customer row with exactly the
        // same email (the email was verified at registration). Nothing else.
        DB::table('users')->where('is_admin', false)->orderBy('id')->each(function ($user) {
            $customer = DB::table('customers')
                ->whereNull('user_id')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower((string) $user->email)])
                ->first();

            if ($customer) {
                DB::table('customers')->where('id', $customer->id)->update(['user_id' => $user->id]);
            }
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
            $table->dropColumn(['is_admin', 'telegram_id', 'phone_verified_at']);
        });
    }
};
