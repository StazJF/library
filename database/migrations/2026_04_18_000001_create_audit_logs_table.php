<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('audit_session_id')
                ->constrained('audit_sessions')
                ->cascadeOnDelete();

            $table->enum('event_type', ['SCAN', 'STATUS_SET', 'NOTE', 'FINALIZE']);

            // Control number scanned/entered. Stored even when not found in DB.
            $table->string('control_number', 100);

            $table->foreignId('book_copy_id')
                ->nullable()
                ->constrained('book_copies')
                ->nullOnDelete();

            $table->enum('result_status', ['VERIFIED', 'MISSING', 'DAMAGED', 'MISPLACED', 'BORROWED', 'REPLACED'])
                ->nullable();

            // For MISPLACED or general context ("Found at shelf X")
            $table->string('location', 120)->nullable();
            $table->string('remarks', 255)->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('system_users')
                ->nullOnDelete();

            // We only need created_at; keep updated_at for simplicity/consistency with Laravel.
            $table->timestamps();

            $table->index(['audit_session_id', 'event_type', 'created_at']);
            $table->index(['audit_session_id', 'control_number']);
            $table->index(['audit_session_id', 'book_copy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
