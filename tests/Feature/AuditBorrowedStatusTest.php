<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\AuditSession;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Borrow;
use App\Models\SystemUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditBorrowedStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_borrowed_copies_are_auto_marked_as_borrowed_when_included(): void
    {
        $admin = SystemUser::create([
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'employee_id' => 'EMP-001',
        ]);

        $this->actingAs($admin);

        $book = Book::factory()->create([
            'copies' => 1,
            'available_copies' => 1,
            'status' => 'available',
        ]);

        $copy = BookCopy::create([
            'book_id' => $book->id,
            'control_number' => 'AUD-001',
            'status' => 'available', // should still be detected via active borrow record
            'condition' => 'good',
            'is_lost_damaged' => false,
        ]);

        $auditStart = now()->startOfDay()->addHours(9);

        Borrow::create([
            'user_id' => null,
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'borrowed_at' => $auditStart->toDateString(),
            'due_date' => $auditStart->copy()->addDays(7)->toDateString(),
            'returned_at' => null,
            'role' => 'student',
            'origin' => 'personal',
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => $auditStart,
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => true,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $response = $this->get(route('audit.summary', $session));
        $response->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'audit_session_id' => $session->id,
            'event_type' => AuditLog::EVENT_STATUS_SET,
            'control_number' => $copy->control_number,
            'book_copy_id' => $copy->id,
            'result_status' => AuditLog::RESULT_BORROWED,
        ]);
    }
}

