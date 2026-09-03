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
        Schema::table('order_product', function (Blueprint $table) {
            $table->foreignId('product_batch_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_batches')
                ->nullOnDelete();
            $table->decimal('unit_cost', 10, 2)->default(0)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_product', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_batch_id');
            $table->dropColumn('unit_cost');
        });
    }
};
