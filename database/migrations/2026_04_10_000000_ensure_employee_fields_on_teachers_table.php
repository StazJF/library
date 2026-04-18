<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('teachers')) {
            return;
        }

        $hasEmployeeId = Schema::hasColumn('teachers', 'employee_id');
        $hasRankPosition = Schema::hasColumn('teachers', 'rank_position');

        if ($hasEmployeeId && $hasRankPosition) {
            return;
        }

        Schema::table('teachers', function (Blueprint $table) use ($hasEmployeeId, $hasRankPosition) {
            if (! $hasEmployeeId) {
                $table->string('employee_id')->nullable()->after('email');
            }

            if (! $hasRankPosition) {
                $table->string('rank_position')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('teachers')) {
            return;
        }

        $columnsToDrop = [];

        if (Schema::hasColumn('teachers', 'rank_position')) {
            $columnsToDrop[] = 'rank_position';
        }

        if (Schema::hasColumn('teachers', 'employee_id')) {
            $columnsToDrop[] = 'employee_id';
        }

        if ($columnsToDrop === []) {
            return;
        }

        Schema::table('teachers', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }
};

