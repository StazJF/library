<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            if (!Schema::hasColumn('borrows', 'created_by')) {
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('system_users')
                    ->nullOnDelete()
                    ->after('role');
            }

            if (!Schema::hasColumn('borrows', 'created_by_role')) {
                $table->string('created_by_role')->nullable()->after('created_by');
            }

            if (!Schema::hasColumn('borrows', 'returned_by')) {
                $table->foreignId('returned_by')
                    ->nullable()
                    ->constrained('system_users')
                    ->nullOnDelete()
                    ->after('created_by_role');
            }

            if (!Schema::hasColumn('borrows', 'returned_by_role')) {
                $table->string('returned_by_role')->nullable()->after('returned_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('borrows', function (Blueprint $table) {
            if (Schema::hasColumn('borrows', 'returned_by')) {
                $table->dropConstrainedForeignId('returned_by');
            }
            if (Schema::hasColumn('borrows', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            if (Schema::hasColumn('borrows', 'returned_by_role')) {
                $table->dropColumn('returned_by_role');
            }
            if (Schema::hasColumn('borrows', 'created_by_role')) {
                $table->dropColumn('created_by_role');
            }
        });
    }
};

