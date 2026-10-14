<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Order amounts stay in USD. These columns only record the CBU rate at
     * the moment of the order and the so'm amount the customer was shown,
     * so the receipt doesn't change when the rate does.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('exchange_rate', 12, 2)->nullable()->after('amount_paid');
            $table->unsignedBigInteger('amount_uzs')->nullable()->after('exchange_rate');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['exchange_rate', 'amount_uzs']);
        });
    }
};
