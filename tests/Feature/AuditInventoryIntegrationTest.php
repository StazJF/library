<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\AuditSession;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Borrow;
use App\Models\LostDamagedItem;
use App\Models\SystemUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditInventoryIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finalize_auto_marks_unreviewed_copies_missing_and_can_undo(): void
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
            'copies' => 2,
            'available_copies' => 2,
            'status' => 'available',
        ]);

        $scanned = BookCopy::create([
            'book_id' => $book->id,
            'control_number' => 'AUD-FIN-SCAN-001',
            'status' => 'available',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        $unscanned = BookCopy::create([
            'book_id' => $book->id,
            'control_number' => 'AUD-FIN-MISS-001',
            'status' => 'available',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => now(),
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => false,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $this->from(route('audit.show', $session))->post(route('audit.scan', $session), [
            'control_number' => $scanned->control_number,
        ])->assertRedirect(route('audit.show', $session));

        $this->from(route('audit.summary', $session))->post(route('audit.finalize', $session))
            ->assertRedirect(route('audit.summary', $session));

        $session->refresh();
        $this->assertSame(AuditSession::STATUS_FINALIZED, $session->status);

        $unscanned->refresh();
        $this->assertSame('missing', $unscanned->status);
        $this->assertTrue((bool) $unscanned->is_lost_damaged);

        $this->assertDatabaseHas('audit_logs', [
            'audit_session_id' => $session->id,
            'event_type' => AuditLog::EVENT_STATUS_SET,
            'control_number' => $unscanned->control_number,
            'book_copy_id' => $unscanned->id,
            'result_status' => AuditLog::RESULT_MISSING,
            'remarks' => 'Auto-marked missing on finalize',
        ]);

        $this->from(route('audit.summary', $session))->post(route('audit.undo-auto-missing', $session), [
            'redirect_to' => route('audit.summary', $session),
        ])->assertRedirect(route('audit.summary', $session));

        $unscanned->refresh();
        $this->assertSame('available', $unscanned->status);
        $this->assertFalse((bool) $unscanned->is_lost_damaged);

        $latest = AuditLog::where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->where('control_number', $unscanned->control_number)
            ->latest('id')
            ->first();

        $this->assertNotNull($latest);
        $this->assertSame(AuditLog::RESULT_VERIFIED, $latest->result_status);
    }

    public function test_admin_marking_missing_updates_inventory_and_logs(): void
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
            'control_number' => 'AUD-MISS-001',
            'status' => 'available',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => now(),
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => false,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $response = $this->from(route('audit.summary', $session))->post(route('audit.status', $session), [
            'control_number' => $copy->control_number,
            'result_status' => AuditLog::RESULT_MISSING,
            'remarks' => 'Missing during audit',
            'redirect_to' => route('audit.summary', $session),
        ]);

        $response->assertRedirect(route('audit.summary', $session));

        $copy->refresh();
        $this->assertSame('missing', $copy->status);
        $this->assertTrue((bool) $copy->is_lost_damaged);

        $this->assertDatabaseHas('audit_logs', [
            'audit_session_id' => $session->id,
            'event_type' => AuditLog::EVENT_STATUS_SET,
            'control_number' => $copy->control_number,
            'book_copy_id' => $copy->id,
            'result_status' => AuditLog::RESULT_MISSING,
            'created_by' => $admin->id,
        ]);
    }

    public function test_admin_returning_missing_restores_inventory_and_marks_verified(): void
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
            'control_number' => 'AUD-RET-001',
            'status' => 'available',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => now(),
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => false,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $this->from(route('audit.summary', $session))->post(route('audit.status', $session), [
            'control_number' => $copy->control_number,
            'result_status' => AuditLog::RESULT_MISSING,
            'redirect_to' => route('audit.summary', $session),
        ])->assertRedirect(route('audit.summary', $session));

        $session->update([
            'status' => AuditSession::STATUS_FINALIZED,
            'ended_at' => now(),
        ]);

        $this->from(route('audit.summary', $session))->post(route('audit.returnMissing', $session), [
            'control_number' => $copy->control_number,
            'redirect_to' => route('audit.summary', $session),
        ])->assertRedirect(route('audit.summary', $session));

        $copy->refresh();
        $this->assertSame('available', $copy->status);
        $this->assertFalse((bool) $copy->is_lost_damaged);

        $latest = AuditLog::where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->where('control_number', $copy->control_number)
            ->latest('id')
            ->first();

        $this->assertNotNull($latest);
        $this->assertSame(AuditLog::RESULT_VERIFIED, $latest->result_status);
    }

    public function test_missing_is_blocked_when_copy_has_active_loan(): void
    {
        $admin = SystemUser::create([
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'employee_id' => 'EMP-001',
        ]);

        $this->actingAs($admin);

        $book = Book::factory()->create();

        $copy = BookCopy::create([
            'book_id' => $book->id,
            'control_number' => 'AUD-BOR-001',
            'status' => 'borrowed',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        Borrow::create([
            'user_id' => null,
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'borrowed_at' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'returned_at' => null,
            'role' => 'student',
            'origin' => 'personal',
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => now(),
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => true,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $response = $this->from(route('audit.summary', $session))->post(route('audit.status', $session), [
            'control_number' => $copy->control_number,
            'result_status' => AuditLog::RESULT_MISSING,
            'redirect_to' => route('audit.summary', $session),
        ]);

        $response->assertRedirect(route('audit.summary', $session));
        $response->assertSessionHasErrors('result_status');

        $copy->refresh();
        $this->assertSame('borrowed', $copy->status);
        $this->assertFalse((bool) $copy->is_lost_damaged);
    }

    public function test_replaced_creates_new_copy_and_blocks_duplicate_replacement(): void
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
            'control_number' => 'AUD-REP-001',
            'status' => 'available',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => now(),
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => false,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $response = $this->from(route('audit.summary', $session))->post(route('audit.status', $session), [
            'control_number' => $copy->control_number,
            'result_status' => AuditLog::RESULT_REPLACED,
            'replacement_control_number' => 'aud-new-001',
            'replacement_condition' => 'Good',
            'redirect_to' => route('audit.summary', $session),
        ]);

        $response->assertRedirect(route('audit.summary', $session));

        $copy->refresh();
        $this->assertSame('replaced', $copy->status);
        $this->assertTrue((bool) $copy->is_lost_damaged);

        $this->assertDatabaseHas('book_copies', [
            'book_id' => $book->id,
            'control_number' => 'AUD-NEW-001',
            'status' => 'available',
            'is_lost_damaged' => 0,
        ]);

        $dup = $this->from(route('audit.summary', $session))->post(route('audit.status', $session), [
            'control_number' => $copy->control_number,
            'result_status' => AuditLog::RESULT_REPLACED,
            'replacement_control_number' => 'AUD-NEW-002',
            'redirect_to' => route('audit.summary', $session),
        ]);

        $dup->assertRedirect(route('audit.summary', $session));
        $dup->assertSessionHasErrors('result_status');
    }

    public function test_damaged_creates_lost_damaged_item_pending_repair(): void
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
            'control_number' => 'AUD-DMG-001',
            'status' => 'available',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => now(),
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => false,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $response = $this->from(route('audit.summary', $session))->post(route('audit.status', $session), [
            'control_number' => $copy->control_number,
            'result_status' => AuditLog::RESULT_DAMAGED,
            'remarks' => 'Torn cover',
            'redirect_to' => route('audit.summary', $session),
        ]);

        $response->assertRedirect(route('audit.summary', $session));

        $this->assertDatabaseHas('book_copies', [
            'id' => $copy->id,
            'status' => 'damaged',
            'is_lost_damaged' => 1,
        ]);

        $this->assertDatabaseHas('lost_damaged_items', [
            'book_id' => $book->id,
            'type' => 'damaged',
            'copy_number' => $copy->control_number,
            'status' => 'active',
        ]);
    }

    public function test_missing_requires_lost_confirmation_then_creates_lost_damaged_item(): void
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
            'control_number' => 'AUD-MISS-CONF-001',
            'status' => 'available',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => now(),
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => false,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $this->from(route('audit.summary', $session))->post(route('audit.status', $session), [
            'control_number' => $copy->control_number,
            'result_status' => AuditLog::RESULT_MISSING,
            'redirect_to' => route('audit.summary', $session),
        ])->assertRedirect(route('audit.summary', $session));

        $copy->refresh();
        $this->assertSame('missing', $copy->status);

        $this->assertDatabaseMissing('lost_damaged_items', [
            'book_id' => $book->id,
            'type' => 'lost',
            'copy_number' => $copy->control_number,
            'status' => 'active',
        ]);

        $this->from(route('audit.summary', $session))->post(route('audit.confirmLost', $session), [
            'control_number' => $copy->control_number,
            'redirect_to' => route('audit.summary', $session),
        ])->assertRedirect(route('audit.summary', $session));

        $copy->refresh();
        $this->assertSame('lost', $copy->status);

        $this->assertDatabaseHas('lost_damaged_items', [
            'book_id' => $book->id,
            'type' => 'lost',
            'copy_number' => $copy->control_number,
            'status' => 'active',
        ]);
    }

    public function test_returning_found_lost_copy_clears_missing_status_in_open_audit_session(): void
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
            'control_number' => 'AUD-FOUND-001',
            'status' => 'available',
            'condition' => 'Good',
            'is_lost_damaged' => false,
        ]);

        $session = AuditSession::create([
            'school_year' => '2025-2026',
            'started_at' => now(),
            'created_by' => $admin->id,
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => false,
            'include_lost_damaged' => false,
            'notes' => null,
        ]);

        $this->from(route('audit.summary', $session))->post(route('audit.status', $session), [
            'control_number' => $copy->control_number,
            'result_status' => AuditLog::RESULT_MISSING,
            'redirect_to' => route('audit.summary', $session),
        ])->assertRedirect(route('audit.summary', $session));

        $this->from(route('audit.summary', $session))->post(route('audit.confirmLost', $session), [
            'control_number' => $copy->control_number,
            'redirect_to' => route('audit.summary', $session),
        ])->assertRedirect(route('audit.summary', $session));

        $item = LostDamagedItem::where('book_id', $book->id)
            ->where('type', 'lost')
            ->where('copy_number', $copy->control_number)
            ->where('status', 'active')
            ->firstOrFail();

        $this->post(route('books.lost-damage.return', $item))
            ->assertRedirect(route('books.lost-damage'));

        $latest = AuditLog::where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->where('control_number', $copy->control_number)
            ->latest('id')
            ->first();

        $this->assertNotNull($latest);
        $this->assertSame(AuditLog::RESULT_VERIFIED, $latest->result_status);
    }

    public function test_repairing_brand_new_damaged_copy_sets_condition_to_old(): void
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
            'available_copies' => 0,
            'status' => 'available',
        ]);

        $copy = BookCopy::create([
            'book_id' => $book->id,
            'control_number' => 'REPAIR-COND-001',
            'status' => 'damaged',
            'condition' => 'Brand New',
            'is_lost_damaged' => true,
        ]);

        $borrow = Borrow::create([
            'user_id' => $admin->id,
            'book_id' => $book->id,
            'book_copy_id' => $copy->id,
            'borrowed_at' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'returned_at' => null,
            'copy_number' => $copy->control_number,
            'remark' => 'Damage',
            'notes' => 'Torn pages',
            'role' => 'student',
            'origin' => 'personal',
        ]);

        $item = LostDamagedItem::create([
            'borrow_id' => $borrow->id,
            'book_id' => $book->id,
            'user_id' => $admin->id,
            'type' => 'damaged',
            'copy_number' => $copy->control_number,
            'remarks' => 'Torn pages',
            'penalty' => null,
            'due_date' => null,
            'status' => 'active',
            'role' => 'student',
            'origin' => 'personal',
        ]);

        $this->post(route('books.lost-damage.repaired', $item))
            ->assertRedirect(route('books.lost-damage'));

        $copy->refresh();
        $this->assertSame('available', $copy->status);
        $this->assertFalse((bool) $copy->is_lost_damaged);
        $this->assertSame('Old', $copy->condition);
    }
}
