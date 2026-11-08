<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Continue with Google": the Google account is identified by its stable
     * `sub` (never by email). Such an account may have no local password, so
     * users.password becomes optional.
     *
     * The profile now shows whether the email is confirmed. Shoppers could
     * only get an email by typing the code we sent to it, but registration
     * never saved that, so existing shopper emails are marked confirmed.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 255)->nullable()->unique()->after('telegram_id');
            $table->string('password')->nullable()->change();
        });

        DB::table('users')
            ->where('is_admin', false)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn('google_id');
        });

        // Accounts without a password (Google sign-ups) must be removed first.
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
