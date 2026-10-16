<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('source', ['word_of_mouth', 'friend', 'instagram', 'telegram', 'website'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The narrower enum can't hold 'website', so move those rows to the
        // value the storefront used before this source existed.
        DB::table('customers')->where('source', 'website')->update(['source' => 'instagram']);

        Schema::table('customers', function (Blueprint $table) {
            $table->enum('source', ['word_of_mouth', 'friend', 'instagram', 'telegram'])->change();
        });
    }
};
