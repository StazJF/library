<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        $driver = DB::getDriverName();

        // SQLite "enum" is effectively stored as a string, so no schema change is needed.
        if ($driver === 'sqlite') {
            return;
        }

        // MySQL/MariaDB: expand enum to include REPLACED.
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN result_status ENUM('VERIFIED','MISSING','DAMAGED','MISPLACED','BORROWED','REPLACED') NULL DEFAULT NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE audit_logs MODIFY COLUMN result_status ENUM('VERIFIED','MISSING','DAMAGED','MISPLACED','BORROWED') NULL DEFAULT NULL");
        }
    }
};

