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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('comment')->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('price_original', 10, 2);
            $table->unsignedInteger('markup')->default(0);
            $table->decimal('selling_price', 10, 2);
            $table->decimal('website_price', 10, 2);
            $table->string('link')->nullable();
            $table->string('image')->nullable();
            $table->enum('status', ['in_stock', 'out_of_stock', 'on_the_way'])->default('in_stock');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
