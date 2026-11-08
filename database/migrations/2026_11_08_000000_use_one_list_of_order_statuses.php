<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * orders.status gets the statuses of App\Enums\OrderStatus: processing,
     * packing, shipped, delivered, cancelled. The column becomes a string
     * (the enum class lists the allowed values) and "awaiting_delivery"
     * becomes "packing", the step the website already showed for it.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 20)->default('processing')->change();
        });

        DB::table('orders')->where('status', 'awaiting_delivery')->update(['status' => 'packing']);
    }

    /**
     * Cancelled orders hold no stock, and the old list has no status for
     * them, so going back is refused while there are any.
     */
    public function down(): void
    {
        $cancelled = DB::table('orders')->where('status', 'cancelled')->pluck('id');
        if ($cancelled->isNotEmpty()) {
            throw new RuntimeException('Orders #'.$cancelled->implode(', #').' are cancelled. The old status list has no such status, so this migration cannot be rolled back.');
        }

        DB::table('orders')->whereIn('status', ['packing', 'shipped'])->update(['status' => 'awaiting_delivery']);

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['processing', 'awaiting_delivery', 'delivered'])->default('processing')->change();
        });
    }
};
