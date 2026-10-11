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
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('order_number')->nullable()->unique()->after('id');
            $table->string('secure_hash', 64)->nullable()->after('order_number');
            $table->string('customer_name')->nullable()->after('customer_id');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->string('city')->nullable()->after('customer_phone');
            $table->text('address')->nullable()->after('city');
            $table->text('comment')->nullable()->after('address');
            $table->string('promo_code')->nullable()->after('comment');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('promo_code');
            $table->string('payment_method')->default('card_fast')->after('discount_amount');
            $table->string('payment_status')->default('PENDING')->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_number',
                'secure_hash',
                'customer_name',
                'customer_phone',
                'city',
                'address',
                'comment',
                'promo_code',
                'discount_amount',
                'payment_method',
                'payment_status',
            ]);
        });
    }
};
