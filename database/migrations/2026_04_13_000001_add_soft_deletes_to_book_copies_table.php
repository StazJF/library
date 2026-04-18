<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('book_copies', function (Blueprint $table) {
            if (!Schema::hasColumn('book_copies', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
                $table->index('deleted_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('book_copies', function (Blueprint $table) {
            if (Schema::hasColumn('book_copies', 'deleted_at')) {
                $table->dropIndex(['deleted_at']);
                $table->dropSoftDeletes();
            }
        });
    }
};

