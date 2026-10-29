<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A product can have several photos: products.images is an ordered list
     * of paths on the public disk (the first one is the main photo). An
     * existing single image becomes the first item of the list.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('images')->nullable()->after('selling_price');
        });

        foreach (DB::table('products')->whereNotNull('image')->where('image', '!=', '')->select('id', 'image')->lazyById(500) as $product) {
            DB::table('products')->where('id', $product->id)->update(['images' => json_encode([$product->image])]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }

    /** Only the first photo can be kept in the single column. */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('image')->nullable()->after('selling_price');
        });

        foreach (DB::table('products')->whereNotNull('images')->select('id', 'images')->lazyById(500) as $product) {
            $first = json_decode((string) $product->images, true)[0] ?? null;
            DB::table('products')->where('id', $product->id)->update(['image' => $first]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
