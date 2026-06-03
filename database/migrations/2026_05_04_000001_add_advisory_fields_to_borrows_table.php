<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            if (!Schema::hasColumn('borrows', 'advisory_grade')) {
                $table->unsignedTinyInteger('advisory_grade')->nullable()->after('origin');
            }
            if (!Schema::hasColumn('borrows', 'advisory_section')) {
                $table->string('advisory_section', 50)->nullable()->after('advisory_grade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            if (Schema::hasColumn('borrows', 'advisory_section')) {
                $table->dropColumn('advisory_section');
            }
            if (Schema::hasColumn('borrows', 'advisory_grade')) {
                $table->dropColumn('advisory_grade');
            }
        });
    }
};

