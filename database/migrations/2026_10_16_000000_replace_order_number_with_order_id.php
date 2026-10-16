<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The order number is no longer stored: Order::order_number is id + 1000.
 * Reviews point at their order by order_id instead of the number.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('order_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });

        // Link each review to the order that carried its number. Prefer the
        // stored orders.order_number; fall back to id = number - 1000. Reviews
        // whose order no longer exists keep order_id = null.
        DB::table('reviews')->whereNotNull('order_number')->orderBy('id')->each(function ($review) {
            $number = (int) $review->order_number;
            $orderId = DB::table('orders')->where('order_number', $number)->value('id')
                ?? DB::table('orders')->whereNull('order_number')->where('id', $number - 1000)->value('id');

            if ($orderId) {
                DB::table('reviews')->where('id', $review->id)->update(['order_id' => $orderId]);
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['order_number']);
            $table->dropColumn('order_number');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropColumn('order_number');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('order_number')->nullable()->unique()->after('id');
        });
        DB::table('orders')->update(['order_number' => DB::raw('id + 1000')]);

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('order_number')->nullable()->index();
        });
        DB::table('reviews')->whereNotNull('order_id')->update(['order_number' => DB::raw('order_id + 1000')]);

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
