<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_sessions', function (Blueprint $table) {
            $table->id();

            $table->string('school_year', 9); // e.g. 2025-2026
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('system_users')
                ->nullOnDelete();

            $table->enum('status', ['OPEN', 'FINALIZED'])->default('OPEN');

            // Scope controls (kept minimal to match current schema)
            $table->boolean('include_borrowed')->default(false);
            $table->boolean('include_lost_damaged')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['status', 'started_at']);
            $table->index('school_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_sessions');
    }
};

