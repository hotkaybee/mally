<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pricing (purchase price, delivery cost, markup, website price) now lives on
     * product_receipts. Products keep only `quantity` and `selling_price`, which
     * become read-only caches maintained from the receipts.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['purchase_price', 'delivery_cost', 'markup', 'website_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('website_price', 10, 2)->default(0)->after('selling_price');
            $table->decimal('delivery_cost', 10, 2)->default(0)->after('selling_price');
            $table->unsignedInteger('markup')->default(0)->after('selling_price');
            $table->decimal('purchase_price', 10, 2)->default(0)->after('selling_price');
        });
    }
};
