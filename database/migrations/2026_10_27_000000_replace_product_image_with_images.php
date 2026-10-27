<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A product gets an ordered list of photos (paths on the public disk) instead
 * of a single image. The first photo is the main one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('images')->nullable()->after('image');
        });

        DB::table('products')->whereNotNull('image')->where('image', '!=', '')->orderBy('id')
            ->each(fn ($product) => DB::table('products')->where('id', $product->id)
                ->update(['images' => json_encode([$product->image])]));

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('images');
        });

        DB::table('products')->whereNotNull('images')->orderBy('id')
            ->each(fn ($product) => DB::table('products')->where('id', $product->id)
                ->update(['image' => json_decode($product->images, true)[0] ?? null]));

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
