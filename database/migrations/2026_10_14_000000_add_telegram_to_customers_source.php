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
            $table->enum('source', ['word_of_mouth', 'friend', 'instagram', 'telegram'])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // The narrower enum can't hold 'telegram', so move those rows to the
        // closest remaining channel before shrinking the column.
        DB::table('customers')->where('source', 'telegram')->update(['source' => 'instagram']);

        Schema::table('customers', function (Blueprint $table) {
            $table->enum('source', ['word_of_mouth', 'friend', 'instagram'])->change();
        });
    }
};
