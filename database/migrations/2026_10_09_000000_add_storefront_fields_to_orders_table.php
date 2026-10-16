<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Order amounts stay in USD. exchange_rate records the CBU rate at the
     * moment of the order, so the so'm total on the receipt doesn't change
     * when the rate does.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('secure_hash', 64)->nullable()->after('id');
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('city')->nullable()->after('customer_phone');
            $table->text('address')->nullable()->after('city');
            $table->text('comment')->nullable()->after('address');
            $table->string('promo_code')->nullable()->after('comment');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('promo_code');
            $table->decimal('exchange_rate', 12, 2)->nullable()->after('amount_paid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'secure_hash',
                'customer_name',
                'customer_phone',
                'city',
                'address',
                'comment',
                'promo_code',
                'discount_amount',
                'exchange_rate',
            ]);
        });
    }
};
