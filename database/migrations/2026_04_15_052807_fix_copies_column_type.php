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
        Schema::table('books', function (Blueprint $table) {
            // Change copies column to ensure it can handle large values (not TINYINT/SMALLINT)
            // This fixes "Out of range value for column 'copies'" errors
            $table->integer('copies')->default(1)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            // Rollback to original column type
            $table->integer('copies')->default(1)->change();
        });
    }
};
