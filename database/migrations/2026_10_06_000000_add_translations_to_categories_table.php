<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Category names are shown to customers, so they need one column per
     * supported locale. `name` held English values, so it becomes `name_en`;
     * `name_ru` and `name_uz` are added alongside it.
     *
     * Only `name_en` keeps the unique index — a translation may legitimately
     * be missing, and MySQL would otherwise reject a second blank one.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('name', 'name_en');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('name_ru')->nullable()->after('name_en');
            $table->string('name_uz')->nullable()->after('name_ru');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['name_ru', 'name_uz']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('name_en', 'name');
        });
    }
};
