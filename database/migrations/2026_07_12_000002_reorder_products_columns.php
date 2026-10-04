<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Column reordering via ALTER TABLE ... MODIFY is MySQL-specific and is
        // purely cosmetic (physical column order). Skip on other drivers (e.g.
        // the sqlite database used by the test suite).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Desired order: quantity, status, website_price, purchase_price, markup, delivery_cost, selling_price
        DB::statement("ALTER TABLE products MODIFY status ENUM('in_stock', 'out_of_stock', 'on_the_way') NOT NULL DEFAULT 'in_stock' AFTER quantity");
        DB::statement('ALTER TABLE products MODIFY website_price DECIMAL(10, 2) NOT NULL AFTER status');
        DB::statement('ALTER TABLE products MODIFY purchase_price DECIMAL(10, 2) NOT NULL AFTER website_price');
        DB::statement('ALTER TABLE products MODIFY markup INT UNSIGNED NOT NULL DEFAULT 0 AFTER purchase_price');
        DB::statement('ALTER TABLE products MODIFY delivery_cost DECIMAL(10, 2) NOT NULL AFTER markup');
        DB::statement('ALTER TABLE products MODIFY selling_price DECIMAL(10, 2) NOT NULL AFTER delivery_cost');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Restore the previous physical order
        DB::statement('ALTER TABLE products MODIFY delivery_cost DECIMAL(10, 2) NOT NULL AFTER quantity');
        DB::statement('ALTER TABLE products MODIFY markup INT UNSIGNED NOT NULL DEFAULT 0 AFTER delivery_cost');
        DB::statement('ALTER TABLE products MODIFY selling_price DECIMAL(10, 2) NOT NULL AFTER markup');
        DB::statement('ALTER TABLE products MODIFY website_price DECIMAL(10, 2) NOT NULL AFTER selling_price');
        DB::statement('ALTER TABLE products MODIFY purchase_price DECIMAL(10, 2) NOT NULL AFTER website_price');
        DB::statement("ALTER TABLE products MODIFY status ENUM('in_stock', 'out_of_stock', 'on_the_way') NOT NULL DEFAULT 'in_stock' AFTER image");
    }
};
