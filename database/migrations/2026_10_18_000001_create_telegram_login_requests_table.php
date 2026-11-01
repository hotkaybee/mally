<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per "continue with Telegram" attempt. The deep-link token and
     * the confirmation code are stored only as hashes; the row is bound to
     * the browser session that started it.
     */
    public function up(): void
    {
        Schema::create('telegram_login_requests', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->string('session_hash', 64);
            $table->string('purpose', 16)->default('login'); // login | reset
            $table->string('status', 20)->default('pending'); // pending | contact_requested | code_sent | used
            $table->string('chat_id', 32)->nullable();
            $table->string('telegram_user_id', 32)->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('code_hash')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['telegram_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_login_requests');
    }
};
