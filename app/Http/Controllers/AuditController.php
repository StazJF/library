<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\AuditSession;
use App\Models\BookCopy;
use App\Models\Borrow;
use App\Models\LostDamagedItem;
use App\Models\LostDamagedItemHistory;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuditController extends Controller
{
    private const AUTO_FINALIZE_MISSING_REMARK = 'Auto-marked missing on finalize';
    private const AUTO_FINALIZE_MISSING_UNDO_REMARK = 'Undo auto-missing from finalize';

    public function index()
    {
        $openSession = AuditSession::where('status', AuditSession::STATUS_OPEN)
            ->latest('started_at')
            ->first();

        $sessions = AuditSession::with('creator')
            ->latest('started_at')
            ->paginate(10);

        return view('audit.index', compact('openSession', 'sessions'));
    }

    public function create()
    {
        return view('audit.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_year' => ['required', 'string', 'max:9', 'regex:/^\d{4}-\d{4}$/'],
            'include_borrowed' => ['nullable', 'boolean'],
            'include_lost_damaged' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $session = AuditSession::create([
            'school_year' => $data['school_year'],
            'started_at' => now(),
            'created_by' => Auth::id(),
            'status' => AuditSession::STATUS_OPEN,
            'include_borrowed' => (bool) ($data['include_borrowed'] ?? false),
            'include_lost_damaged' => (bool) ($data['include_lost_damaged'] ?? false),
            'notes' => $data['notes'] ?? null,
        ]);

        // If this session includes borrowed copies, auto-mark active loans as BORROWED at audit start.
        // This keeps them out of "missing candidates" and makes reports/counts accurate without manual marking.
        $this->ensureBorrowedStatuses($session);

        AuditLog::create([
            'audit_session_id' => $session->id,
            'event_type' => AuditLog::EVENT_NOTE,
            'control_number' => '__SESSION__',
            'remarks' => 'Session started',
            'created_by' => Auth::id(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Started Audit Session',
            'target_type' => 'AuditSession',
            'target_id' => $session->id,
            'details' => "Started audit session for SY {$session->school_year}.",
        ]);

        return redirect()->route('audit.show', $session);
    }

    public function show(AuditSession $session)
    {
        $this->ensureCanAccessSession($session);

        // Last 10 audit events for quick feedback (inspect + status updates)
        $recentScans = AuditLog::where('audit_session_id', $session->id)
            ->whereIn('event_type', [AuditLog::EVENT_SCAN, AuditLog::EVENT_STATUS_SET])
            ->latest('id')
            ->with(['bookCopy.book'])
            ->take(10)
            ->get();

        $recentControlNumbers = $recentScans
            ->pluck('control_number')
            ->filter(fn ($v) => is_string($v) && trim($v) !== '')
            ->unique()
            ->values();

        $latestStatusByControlNumber = $this->getLatestStatusByControlNumber($session, $recentControlNumbers->all());

        $summary = $this->buildSessionSummary($session);

        return view('audit.scan', compact('session', 'recentScans', 'summary', 'latestStatusByControlNumber'));
    }

    public function scan(Request $request, AuditSession $session)
    {
        $this->ensureCanAccessSession($session);
        if (!$session->isOpen()) {
            return redirect()->route('audit.summary', $session)
                ->with('status', 'This audit session is finalized.');
        }

        $data = $request->validate([
            'control_number' => ['required', 'string', 'max:100'],
        ]);

        $controlNumber = $this->normalizeControlNumber($data['control_number']);

        $bookCopy = BookCopy::where('control_number', $controlNumber)->first();

        // Always append a SCAN event (audit trail).
        AuditLog::create([
            'audit_session_id' => $session->id,
            'event_type' => AuditLog::EVENT_SCAN,
            'control_number' => $controlNumber,
            'book_copy_id' => $bookCopy?->id,
            'created_by' => Auth::id(),
        ]);

        // If we already have a status for this control number in this session, treat as duplicate inspect.
        $hasAnyStatus = AuditLog::where('audit_session_id', $session->id)
            ->where('control_number', $controlNumber)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->exists();

        $message = null;
        $level = 'success';

        if (!$bookCopy) {
            $message = "Inspected control number '{$controlNumber}' not found in the database.";
            $level = 'warning';
        } else {
            if (!$hasAnyStatus) {
                // First time we’ve identified this copy in the session: mark VERIFIED by default.
                AuditLog::create([
                    'audit_session_id' => $session->id,
                    'event_type' => AuditLog::EVENT_STATUS_SET,
                    'control_number' => $controlNumber,
                    'book_copy_id' => $bookCopy->id,
                    'result_status' => AuditLog::RESULT_VERIFIED,
                    'created_by' => Auth::id(),
                ]);
                $message = "Verified: {$bookCopy->book?->title} (CN: {$controlNumber})";
            } else {
                $message = "Duplicate inspect: {$bookCopy->book?->title} (CN: {$controlNumber})";
                $level = 'info';
            }
        }

        return redirect()
            ->route('audit.show', $session)
            ->with('audit_scan_message', $message)
            ->with('audit_scan_level', $level)
            ->with('audit_last_control_number', $controlNumber);
    }

    public function setStatus(Request $request, AuditSession $session)
    {
        $this->ensureCanAccessSession($session);
        if (!$session->isOpen()) {
            return redirect()->route('audit.summary', $session)
                ->with('status', 'This audit session is finalized.');
        }

        $data = $request->validate([
            'control_number' => ['required', 'string', 'max:100'],
            'result_status' => ['required', Rule::in([
                AuditLog::RESULT_VERIFIED,
                AuditLog::RESULT_DAMAGED,
                AuditLog::RESULT_MISPLACED,
                AuditLog::RESULT_MISSING,
                AuditLog::RESULT_BORROWED,
                AuditLog::RESULT_REPLACED,
            ])],
            'location' => ['nullable', 'string', 'max:120'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'replacement_control_number' => [Rule::requiredIf(fn () => ($request->input('result_status') === AuditLog::RESULT_REPLACED)), 'nullable', 'string', 'max:100'],
            'replacement_acquisition_year' => ['nullable', 'integer', 'min:1900', 'max:' . (now()->year + 1)],
            'replacement_condition' => ['nullable', 'string', 'max:50'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        if (($data['result_status'] ?? null) === AuditLog::RESULT_BORROWED && !$this->auditLogsSupportBorrowedStatus()) {
            return $this->safeRedirect(
                $request,
                route('audit.show', $session),
                "Borrowed status isn't available yet. Run your database migrations to enable it.",
                'error'
            );
        }

        if (($data['result_status'] ?? null) === AuditLog::RESULT_REPLACED && !$this->auditLogsSupportReplacedStatus()) {
            return $this->safeRedirect(
                $request,
                route('audit.show', $session),
                "Replaced status isn't available yet. Run your database migrations to enable it.",
                'error'
            );
        }

        $controlNumber = $this->normalizeControlNumber($data['control_number']);

        $bookCopy = BookCopy::where('control_number', $controlNumber)->first();

        if (!$bookCopy && ($data['result_status'] ?? null) !== AuditLog::RESULT_VERIFIED) {
            return $this->safeRedirect(
                $request,
                route('audit.show', $session),
                "Cannot apply '{$data['result_status']}' because control number '{$controlNumber}' isn't linked to an inventory copy.",
                'error'
            );
        }

        DB::transaction(function () use ($session, $controlNumber, $bookCopy, $data) {
            if ($bookCopy) {
                $this->applyAuditActionToInventory($bookCopy->id, $data);
            }

            AuditLog::create([
                'audit_session_id' => $session->id,
                'event_type' => AuditLog::EVENT_STATUS_SET,
                'control_number' => $controlNumber,
                'book_copy_id' => $bookCopy?->id,
                'result_status' => $data['result_status'],
                'location' => $data['location'] ?? null,
                'remarks' => $this->buildAuditRemarks($data),
                'created_by' => Auth::id(),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Audit Action Applied',
                'target_type' => 'AuditSession',
                'target_id' => $session->id,
                'details' => "Applied {$data['result_status']} for control number {$controlNumber}.",
            ]);
        });

        return $this->safeRedirect(
            $request,
            route('audit.show', $session),
            "Status updated for {$controlNumber}."
        );
    }

    private function buildAuditRemarks(array $data): ?string
    {
        $remarks = trim((string) ($data['remarks'] ?? ''));

        if (($data['result_status'] ?? null) !== AuditLog::RESULT_REPLACED) {
            return $remarks !== '' ? $remarks : null;
        }

        $replacementCn = trim((string) ($data['replacement_control_number'] ?? ''));
        if ($replacementCn === '') {
            return $remarks !== '' ? $remarks : null;
        }

        $tag = "Replaced with {$replacementCn}";
        if ($remarks === '') {
            return $tag;
        }

        return "{$tag} | {$remarks}";
    }

    private function applyAuditActionToInventory(int $bookCopyId, array $data): void
    {
        $status = (string) ($data['result_status'] ?? '');
        if ($status === '') {
            return;
        }

        // Lock the copy row to keep inventory changes consistent within the transaction.
        $copy = BookCopy::whereKey($bookCopyId)->lockForUpdate()->first();
        if (!$copy) {
            return;
        }

        $hasActiveLoan = $copy->borrows()->whereNull('returned_at')->exists();
        $isBorrowedInInventory = $copy->status === 'borrowed';

        if ($status === AuditLog::RESULT_VERIFIED || $status === AuditLog::RESULT_MISPLACED) {
            return;
        }

        if ($status === AuditLog::RESULT_MISSING) {
            if ($isBorrowedInInventory || $hasActiveLoan) {
                throw ValidationException::withMessages([
                    'result_status' => 'Cannot mark as Missing while the copy is currently Borrowed (active loan exists).',
                ]);
            }

            $copy->update([
                'status' => 'missing',
                'is_lost_damaged' => true,
            ]);

            $this->syncParentBookCounts($copy);
            return;
        }

        if ($status === AuditLog::RESULT_DAMAGED) {
            $copy->update([
                'status' => 'damaged',
                'condition' => 'Damaged',
                'is_lost_damaged' => true,
            ]);

            $this->ensureLostDamagedPendingRepair($copy, $data);
            $this->syncParentBookCounts($copy);
            return;
        }

        if ($status === AuditLog::RESULT_BORROWED) {
            if (!$hasActiveLoan) {
                throw ValidationException::withMessages([
                    'result_status' => 'Cannot mark as Borrowed because no active loan/transaction exists for this copy.',
                ]);
            }

            $copy->update([
                'status' => 'borrowed',
                'is_lost_damaged' => false,
            ]);

            $this->syncParentBookCounts($copy);
            return;
        }

        if ($status === AuditLog::RESULT_REPLACED) {
            $replacementCn = $this->normalizeControlNumber((string) ($data['replacement_control_number'] ?? ''));
            if (trim($replacementCn) === '') {
                throw ValidationException::withMessages([
                    'replacement_control_number' => 'Replacement control number is required.',
                ]);
            }

            if (in_array($copy->status, ['replaced', 'archived'], true)) {
                throw ValidationException::withMessages([
                    'result_status' => 'This copy is already marked as Replaced/Archived. Duplicate replacements are not allowed.',
                ]);
            }

            $alreadyReplacedInAudit = AuditLog::where('book_copy_id', $copy->id)
                ->where('event_type', AuditLog::EVENT_STATUS_SET)
                ->where('result_status', AuditLog::RESULT_REPLACED)
                ->exists();
            if ($alreadyReplacedInAudit) {
                throw ValidationException::withMessages([
                    'result_status' => 'A replacement action was already recorded for this copy. Duplicate replacements are not allowed.',
                ]);
            }

            $replacementExists = BookCopy::withTrashed()
                ->where('control_number', $replacementCn)
                ->exists();
            if ($replacementExists) {
                throw ValidationException::withMessages([
                    'replacement_control_number' => 'Replacement control number already exists in the inventory.',
                ]);
            }

            $copy->update([
                'status' => 'replaced',
                'is_lost_damaged' => true,
            ]);

            BookCopy::create([
                'book_id' => $copy->book_id,
                'control_number' => $replacementCn,
                'acquisition_year' => $data['replacement_acquisition_year'] ?? null,
                'status' => 'available',
                'condition' => trim((string) ($data['replacement_condition'] ?? '')) ?: 'Good',
                'is_lost_damaged' => false,
            ]);

            $this->syncParentBookCounts($copy);
            return;
        }
    }

    private function ensureLostDamagedPendingRepair(BookCopy $copy, array $data): void
    {
        $controlNumber = trim((string) ($copy->control_number ?? ''));
        if ($controlNumber === '') {
            return;
        }

        $alreadyActive = LostDamagedItem::where('status', 'active')
            ->where('type', 'damaged')
            ->where('book_id', $copy->book_id)
            ->where('copy_number', $controlNumber)
            ->exists();

        if ($alreadyActive) {
            return;
        }

        // Create a non-active "borrow" record to satisfy lost_damaged_items.borrow_id FK,
        // without affecting active-loan availability (returned_at is set).
        $now = now();
        $borrow = Borrow::create([
            'user_id' => null,
            'book_id' => $copy->book_id,
            'book_copy_id' => $copy->id,
            'borrowed_at' => $now->toDateString(),
            'due_date' => $now->toDateString(),
            'returned_at' => $now,
            'return_status' => 'damaged_for_repair',
            'remark' => 'Damaged (audit)',
            'notes' => $data['remarks'] ?? null,
            'role' => 'audit',
            'origin' => 'inventory_audit',
            'copy_number' => $controlNumber,
            'created_by' => Auth::id(),
            'created_by_role' => Auth::user()?->role,
            'returned_by' => Auth::id(),
            'returned_by_role' => Auth::user()?->role,
        ]);

        $item = LostDamagedItem::create([
            'borrow_id' => $borrow->id,
            'book_id' => $copy->book_id,
            'user_id' => null,
            'type' => 'damaged',
            'copy_number' => $controlNumber,
            'remarks' => trim((string) ($data['remarks'] ?? '')) ?: 'Damaged during audit (pending repair).',
            'penalty' => null,
            'due_date' => null,
            'status' => 'active',
            'role' => 'audit',
            'origin' => 'inventory_audit',
        ]);

        LostDamagedItemHistory::create([
            'lost_damaged_item_id' => $item->id,
            'action' => 'pending',
            'remarks' => "Marked as damaged during audit; pending repair. Ctrl#: {$controlNumber}.",
            'created_by' => Auth::id(),
        ]);
    }

    private function ensureLostDamagedLostConfirmed(BookCopy $copy): void
    {
        $controlNumber = trim((string) ($copy->control_number ?? ''));
        if ($controlNumber === '') {
            return;
        }

        $alreadyActive = LostDamagedItem::where('status', 'active')
            ->where('type', 'lost')
            ->where('book_id', $copy->book_id)
            ->where('copy_number', $controlNumber)
            ->exists();

        if ($alreadyActive) {
            return;
        }

        $now = now();
        $borrow = Borrow::create([
            'user_id' => null,
            'book_id' => $copy->book_id,
            'book_copy_id' => $copy->id,
            'borrowed_at' => $now->toDateString(),
            'due_date' => $now->toDateString(),
            'returned_at' => $now,
            'return_status' => 'lost_and_found',
            'remark' => 'Lost (audit)',
            'notes' => 'Lost confirmed from audit missing status.',
            'role' => 'audit',
            'origin' => 'inventory_audit',
            'copy_number' => $controlNumber,
            'created_by' => Auth::id(),
            'created_by_role' => Auth::user()?->role,
            'returned_by' => Auth::id(),
            'returned_by_role' => Auth::user()?->role,
        ]);

        $item = LostDamagedItem::create([
            'borrow_id' => $borrow->id,
            'book_id' => $copy->book_id,
            'user_id' => null,
            'type' => 'lost',
            'copy_number' => $controlNumber,
            'remarks' => 'Lost confirmed from audit.',
            'penalty' => null,
            'due_date' => null,
            'status' => 'active',
            'role' => 'audit',
            'origin' => 'inventory_audit',
        ]);

        LostDamagedItemHistory::create([
            'lost_damaged_item_id' => $item->id,
            'action' => 'created',
            'remarks' => "Lost confirmed from audit missing status. Ctrl#: {$controlNumber}.",
            'created_by' => Auth::id(),
        ]);
    }

    private function syncParentBookCounts(BookCopy $copy): void
    {
        $book = $copy->book;
        if (!$book) {
            return;
        }

        $newCopiesCount = $book->copies()->count();
        $newAvailableCount = $book->copies()->available()->count();

        $book->update([
            'copies' => $newCopiesCount,
            'available_copies' => $newAvailableCount,
            'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
        ]);
    }

    public function summary(AuditSession $session)
    {
        $this->ensureCanAccessSession($session);

        $summary = $this->buildSessionSummary($session);
        $latestStatuses = $this->getLatestStatusLogs($session);
        $markedMissing = $latestStatuses->where('result_status', AuditLog::RESULT_MISSING)->values();
        $missingCandidates = $this->getMissingCandidatesQuery($session)
            ->paginate(50, ['*'], 'missingPage')
            ->withQueryString();
        $missingStatusByCn = $this->getLatestStatusByControlNumber(
            $session,
            $missingCandidates->getCollection()->pluck('control_number')->all()
        );
        $unknownAccessions = $this->getUnknownAccessions($session, limit: 200);
        $overdues = $this->getOverduesDuringAudit($session, limit: 200);

        return view('audit.summary', compact('session', 'summary', 'latestStatuses', 'markedMissing', 'missingCandidates', 'missingStatusByCn', 'unknownAccessions', 'overdues'));
    }

    public function confirmLost(Request $request, AuditSession $session)
    {
        $this->ensureCanAccessSession($session);

        $data = $request->validate([
            'control_number' => ['required', 'string', 'max:100'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $controlNumber = $this->normalizeControlNumber($data['control_number']);

        $bookCopy = BookCopy::where('control_number', $controlNumber)->first();
        if (!$bookCopy) {
            return $this->safeRedirect(
                $request,
                route('audit.summary', $session),
                "Control number '{$controlNumber}' isn't linked to an inventory copy.",
                'error'
            );
        }

        $latestStatus = $this->getLatestStatusByControlNumber($session, [$controlNumber])[$controlNumber] ?? null;
        if ($latestStatus !== AuditLog::RESULT_MISSING) {
            return $this->safeRedirect(
                $request,
                route('audit.summary', $session),
                'Lost confirmation is only allowed for copies currently marked as Missing in this audit session.',
                'error'
            );
        }

        DB::transaction(function () use ($session, $controlNumber, $bookCopy) {
            $copy = BookCopy::whereKey($bookCopy->id)->lockForUpdate()->first();
            if (!$copy) {
                return;
            }

            $hasActiveLoan = $copy->borrows()->whereNull('returned_at')->exists();
            if ($hasActiveLoan || $copy->status === 'borrowed') {
                throw ValidationException::withMessages([
                    'control_number' => 'Cannot confirm as Lost while the copy is currently Borrowed (active loan exists).',
                ]);
            }

            if ($copy->status !== 'lost') {
                $copy->update([
                    'status' => 'lost',
                    'is_lost_damaged' => true,
                ]);
            }

            $this->ensureLostDamagedLostConfirmed($copy);
            $this->syncParentBookCounts($copy);

            AuditLog::create([
                'audit_session_id' => $session->id,
                'event_type' => AuditLog::EVENT_NOTE,
                'control_number' => $controlNumber,
                'book_copy_id' => $copy->id,
                'remarks' => 'Lost confirmed (from Missing)',
                'created_by' => Auth::id(),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Audit Lost Confirmed',
                'target_type' => 'BookCopy',
                'target_id' => $copy->id,
                'details' => "Confirmed LOST for control number {$controlNumber} from audit missing status.",
            ]);
        });

        return $this->safeRedirect(
            $request,
            route('audit.summary', $session),
            "Lost confirmed for {$controlNumber}.",
            'status'
        );
    }

    public function returnMissing(Request $request, AuditSession $session)
    {
        $this->ensureCanAccessSession($session);

        $data = $request->validate([
            'control_number' => ['required', 'string', 'max:100'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $controlNumber = $this->normalizeControlNumber($data['control_number']);

        $bookCopy = BookCopy::where('control_number', $controlNumber)->first();
        if (!$bookCopy) {
            return $this->safeRedirect(
                $request,
                route('audit.summary', $session),
                "Control number '{$controlNumber}' isn't linked to an inventory copy.",
                'error'
            );
        }

        $latestStatus = $this->getLatestStatusByControlNumber($session, [$controlNumber])[$controlNumber] ?? null;
        if ($latestStatus !== AuditLog::RESULT_MISSING) {
            return $this->safeRedirect(
                $request,
                route('audit.summary', $session),
                'Return is only allowed for copies currently marked as Missing in this audit session.',
                'error'
            );
        }

        DB::transaction(function () use ($session, $controlNumber, $bookCopy) {
            $copy = BookCopy::whereKey($bookCopy->id)->lockForUpdate()->first();
            if (!$copy) {
                return;
            }

            $hasActiveLoan = $copy->borrows()->whereNull('returned_at')->exists();
            if ($hasActiveLoan || $copy->status === 'borrowed') {
                throw ValidationException::withMessages([
                    'control_number' => 'Cannot return while the copy is currently Borrowed (active loan exists).',
                ]);
            }

            if (in_array($copy->status, ['lost', 'replaced', 'archived'], true)) {
                throw ValidationException::withMessages([
                    'control_number' => "Cannot return this copy because it is already marked as {$copy->status}.",
                ]);
            }

            $copy->update([
                'status' => 'available',
                'is_lost_damaged' => false,
            ]);

            $this->syncParentBookCounts($copy);

            AuditLog::create([
                'audit_session_id' => $session->id,
                'event_type' => AuditLog::EVENT_STATUS_SET,
                'control_number' => $controlNumber,
                'book_copy_id' => $copy->id,
                'result_status' => AuditLog::RESULT_VERIFIED,
                'remarks' => 'Returned / found (from Missing)',
                'created_by' => Auth::id(),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Audit Missing Returned',
                'target_type' => 'BookCopy',
                'target_id' => $copy->id,
                'details' => "Returned/found copy restored to inventory for control number {$controlNumber} (from audit Missing).",
            ]);
        });

        return $this->safeRedirect(
            $request,
            route('audit.summary', $session),
            "Marked as returned for {$controlNumber}.",
            'status'
        );
    }

    private function safeRedirect(Request $request, string $fallbackUrl, string $flashMessage, string $flashKey = 'status')
    {
        $target = $request->input('redirect_to');
        if (is_string($target) && trim($target) !== '') {
            $parts = parse_url($target);
            $host = $parts['host'] ?? null;
            // Only allow same-host redirects to avoid open redirect issues.
            if ($host === null || strcasecmp($host, (string) $request->getHost()) === 0) {
                return redirect($target)->with($flashKey, $flashMessage);
            }
        }

        return redirect($fallbackUrl)->with($flashKey, $flashMessage);
    }

    public function finalize(Request $request, AuditSession $session)
    {
        $this->ensureCanAccessSession($session);
        if (!$session->isOpen()) {
            return redirect()->route('audit.summary', $session)
                ->with('status', 'This audit session is already finalized.');
        }

        DB::transaction(function () use ($session) {
            // Ensure borrowed copies are represented correctly before computing missing items.
            $this->ensureBorrowedStatuses($session);

            // Auto-mark all in-scope, unreviewed copies as MISSING.
            // This is limited to non-lost/damaged inventory items to avoid "undo" restoring
            // items that were already lost/damaged before this audit.
            $candidates = $this->getMissingCandidatesQuery($session)
                ->where('is_lost_damaged', false)
                ->get(['id', 'control_number']);

            foreach ($candidates as $candidate) {
                $data = [
                    'result_status' => AuditLog::RESULT_MISSING,
                ];

                try {
                    $this->applyAuditActionToInventory((int) $candidate->id, $data);
                } catch (ValidationException) {
                    // Skip copies that cannot be marked missing (e.g., currently borrowed with an active loan).
                    continue;
                }

                AuditLog::create([
                    'audit_session_id' => $session->id,
                    'event_type' => AuditLog::EVENT_STATUS_SET,
                    'control_number' => $candidate->control_number,
                    'book_copy_id' => (int) $candidate->id,
                    'result_status' => AuditLog::RESULT_MISSING,
                    'remarks' => self::AUTO_FINALIZE_MISSING_REMARK,
                    'created_by' => Auth::id(),
                ]);
            }

            $session->update([
                'status' => AuditSession::STATUS_FINALIZED,
                'ended_at' => now(),
            ]);

            AuditLog::create([
                'audit_session_id' => $session->id,
                'event_type' => AuditLog::EVENT_FINALIZE,
                'control_number' => '__SESSION__',
                'remarks' => 'Session finalized',
                'created_by' => Auth::id(),
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Finalized Audit Session',
                'target_type' => 'AuditSession',
                'target_id' => $session->id,
                'details' => "Finalized audit session for SY {$session->school_year}.",
            ]);
        });

        return redirect()->route('audit.summary', $session)
            ->with('status', 'Audit session finalized.');
    }

    public function undoAutoMissing(Request $request, AuditSession $session)
    {
        $this->ensureCanAccessSession($session);
        if ($session->isOpen()) {
            return redirect()->route('audit.summary', $session)
                ->with('error', 'Undo is only available after the audit session is finalized.');
        }

        $data = $request->validate([
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $latestStatuses = $this->getLatestStatusLogs($session);
        $autoMissing = $latestStatuses
            ->where('result_status', AuditLog::RESULT_MISSING)
            ->where('remarks', self::AUTO_FINALIZE_MISSING_REMARK)
            ->values();

        if ($autoMissing->count() === 0) {
            return $this->safeRedirect(
                $request,
                route('audit.summary', $session),
                'No auto-marked missing copies to undo.',
                'status'
            );
        }

        DB::transaction(function () use ($session, $autoMissing) {
            foreach ($autoMissing as $log) {
                $copyId = (int) ($log->book_copy_id ?? 0);
                if ($copyId <= 0) {
                    continue;
                }

                $copy = BookCopy::whereKey($copyId)->lockForUpdate()->first();
                if (!$copy) {
                    continue;
                }

                // Only undo if the inventory is still in the "missing" state.
                if ($copy->status !== 'missing') {
                    continue;
                }

                $hasActiveLoan = $copy->borrows()->whereNull('returned_at')->exists();
                if ($hasActiveLoan) {
                    continue;
                }

                $copy->update([
                    'status' => 'available',
                    'is_lost_damaged' => false,
                ]);

                $this->syncParentBookCounts($copy);

                AuditLog::create([
                    'audit_session_id' => $session->id,
                    'event_type' => AuditLog::EVENT_STATUS_SET,
                    'control_number' => (string) $copy->control_number,
                    'book_copy_id' => $copy->id,
                    'result_status' => AuditLog::RESULT_VERIFIED,
                    'remarks' => self::AUTO_FINALIZE_MISSING_UNDO_REMARK,
                    'created_by' => Auth::id(),
                ]);
            }

            AuditLog::create([
                'audit_session_id' => $session->id,
                'event_type' => AuditLog::EVENT_NOTE,
                'control_number' => '__SESSION__',
                'remarks' => 'Undo auto-missing from finalize executed',
                'created_by' => Auth::id(),
            ]);
        });

        return $this->safeRedirect(
            $request,
            route('audit.summary', $session),
            'Undo completed: auto-marked missing copies restored to Available.',
            'status'
        );
    }

    public function reopen(Request $request, AuditSession $session)
    {
        $this->ensureCanAccessSession($session);

        if ((Auth::user()->role ?? null) !== 'admin') {
            abort(403, 'Unauthorized');
        }

        $session->update([
            'status' => AuditSession::STATUS_OPEN,
            'ended_at' => null,
        ]);

        AuditLog::create([
            'audit_session_id' => $session->id,
            'event_type' => AuditLog::EVENT_NOTE,
            'control_number' => '__SESSION__',
            'remarks' => 'Session reopened by admin',
            'created_by' => Auth::id(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Reopened Audit Session',
            'target_type' => 'AuditSession',
            'target_id' => $session->id,
            'details' => "Reopened audit session for SY {$session->school_year}.",
        ]);

        return redirect()->route('audit.show', $session)
            ->with('status', 'Audit session reopened.');
    }

    public function report(AuditSession $session)
    {
        $this->ensureCanAccessSession($session);

        $summary = $this->buildSessionSummary($session);
        $missingCandidates = $this->getMissingCandidates($session);
        $unknownAccessions = $this->getUnknownAccessions($session);
        $overdues = $this->getOverduesDuringAudit($session);

        // Resolve latest statuses for detailed sections
        $latestStatuses = $this->getLatestStatusLogs($session);

        $damaged = $latestStatuses->where('result_status', AuditLog::RESULT_DAMAGED)->values();
        $misplaced = $latestStatuses->where('result_status', AuditLog::RESULT_MISPLACED)->values();
        $verified = $latestStatuses->where('result_status', AuditLog::RESULT_VERIFIED)->values();
        $missing = $latestStatuses->where('result_status', AuditLog::RESULT_MISSING)->values();
        $borrowed = $latestStatuses->where('result_status', AuditLog::RESULT_BORROWED)->values();
        $replaced = $latestStatuses->where('result_status', AuditLog::RESULT_REPLACED)->values();

        return view('audit.report', compact(
            'session',
            'summary',
            'missingCandidates',
            'unknownAccessions',
            'overdues',
            'verified',
            'missing',
            'damaged',
            'misplaced',
            'borrowed',
            'replaced',
        ));
    }

    private function normalizeControlNumber(string $value): string
    {
        $v = trim($value);
        // Normalize common scanner quirks: multiple spaces/newlines.
        $v = preg_replace('/\s+/', '', $v) ?? $v;
        return strtoupper($v);
    }

    private function ensureCanAccessSession(AuditSession $session): void
    {
        // Role checks are enforced at route level; keep this as a sanity guard.
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }
    }

    private function buildSessionSummary(AuditSession $session): array
    {
        // Keep borrowed copies (active loans) consistently represented as a distinct audit status.
        $this->ensureBorrowedStatuses($session);

        $latestStatuses = $this->getLatestStatusLogs($session);

        $counts = $latestStatuses
            ->groupBy('result_status')
            ->map(fn ($g) => $g->count())
            ->toArray();

        // "Inspected" should reflect items already processed in the audit flow.
        // This includes actual SCAN events and manual STATUS_SET actions (e.g., marking from Missing Candidates).
        $scannedTotal = AuditLog::where('audit_session_id', $session->id)
            ->whereIn('event_type', [AuditLog::EVENT_SCAN, AuditLog::EVENT_STATUS_SET])
            ->where('control_number', '!=', '__SESSION__')
            ->select('control_number')
            ->distinct()
            ->count();

        $unknownTotal = AuditLog::where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_SCAN)
            ->whereNull('book_copy_id')
            ->distinct()
            ->count('control_number');

        $overdueTotal = $this->getOverduesDuringAudit($session)->count();

        // Total copies in scope
        $totalInScope = $this->getScopedCopiesQuery($session)->count();

        return [
            'total_in_scope' => $totalInScope,
            'scanned_total' => $scannedTotal,
            'unknown_total' => $unknownTotal,
            'overdue_total' => $overdueTotal,
            'verified' => (int) ($counts[AuditLog::RESULT_VERIFIED] ?? 0),
            'missing' => (int) ($counts[AuditLog::RESULT_MISSING] ?? 0),
            'damaged' => (int) ($counts[AuditLog::RESULT_DAMAGED] ?? 0),
            'misplaced' => (int) ($counts[AuditLog::RESULT_MISPLACED] ?? 0),
            'borrowed' => (int) ($counts[AuditLog::RESULT_BORROWED] ?? 0),
            'replaced' => (int) ($counts[AuditLog::RESULT_REPLACED] ?? 0),
        ];
    }

    private function ensureBorrowedStatuses(AuditSession $session): void
    {
        // Don't mutate finalized sessions. Borrowed statuses should be captured while the session is OPEN.
        if (!$session->isOpen()) {
            return;
        }

        if (!$session->include_borrowed) {
            return;
        }

        if (!$this->auditLogsSupportBorrowedStatus()) {
            return;
        }

        $borrowedCopyIds = $this->getBorrowedCopyIdsAtAuditStart($session);
        if (count($borrowedCopyIds) === 0) {
            return;
        }

        // Only auto-mark copies that have not been reviewed yet in this session.
        $alreadyReviewed = AuditLog::where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->whereIn('book_copy_id', $borrowedCopyIds)
            ->pluck('book_copy_id')
            ->filter()
            ->map(fn ($v) => (int) $v)
            ->all();

        $alreadyReviewedMap = array_fill_keys($alreadyReviewed, true);
        $toInsertIds = array_values(array_filter(
            $borrowedCopyIds,
            fn ($id) => !isset($alreadyReviewedMap[(int) $id])
        ));

        if (count($toInsertIds) === 0) {
            return;
        }

        $copies = BookCopy::whereIn('id', $toInsertIds)->get(['id', 'control_number']);
        if ($copies->isEmpty()) {
            return;
        }

        $now = now();
        $createdBy = Auth::id();
        $rows = $copies->map(fn (BookCopy $copy) => [
            'audit_session_id' => $session->id,
            'event_type' => AuditLog::EVENT_STATUS_SET,
            'control_number' => $copy->control_number,
            'book_copy_id' => $copy->id,
            'result_status' => AuditLog::RESULT_BORROWED,
            'remarks' => 'Auto-marked as borrowed (active loan at audit start)',
            'created_by' => $createdBy,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        try {
            AuditLog::insert($rows);
        } catch (QueryException $e) {
            // If the DB enum hasn't been migrated yet, MySQL throws "Data truncated" when inserting a new enum value.
            // Treat this as a non-fatal environment mismatch so the UI doesn't 500 on GET pages.
            if (str_contains($e->getMessage(), "Data truncated for column 'result_status'")) {
                return;
            }

            throw $e;
        }
    }

    private function auditLogsSupportBorrowedStatus(): bool
    {
        $driver = DB::getDriverName();

        // SQLite "enum" is effectively stored as a string; other drivers may not enforce enum values the same way.
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return true;
        }

        try {
            $row = DB::selectOne("SHOW COLUMNS FROM audit_logs WHERE Field = 'result_status'");
            $type = is_object($row) ? ($row->Type ?? null) : (is_array($row) ? ($row['Type'] ?? null) : null);
            if (!is_string($type) || $type === '') {
                return false;
            }

            return str_contains(strtoupper($type), "'BORROWED'");
        } catch (\Throwable) {
            return false;
        }
    }

    private function auditLogsSupportReplacedStatus(): bool
    {
        $driver = DB::getDriverName();

        // SQLite "enum" is effectively stored as a string; other drivers may not enforce enum values the same way.
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return true;
        }

        try {
            $row = DB::selectOne("SHOW COLUMNS FROM audit_logs WHERE Field = 'result_status'");
            $type = is_object($row) ? ($row->Type ?? null) : (is_array($row) ? ($row['Type'] ?? null) : null);
            if (!is_string($type) || $type === '') {
                return false;
            }

            return str_contains(strtoupper($type), "'REPLACED'");
        } catch (\Throwable) {
            return false;
        }
    }

    private function getBorrowedCopyIdsAtAuditStart(AuditSession $session): array
    {
        $auditTs = $session->started_at ?? now();
        $auditDate = $auditTs->toDateString();

        return BookCopy::query()
            ->when(!$session->include_lost_damaged, fn ($q) => $q->where('is_lost_damaged', false))
            ->whereHas('borrows', function ($q) use ($auditDate, $auditTs) {
                $q->whereNotNull('book_copy_id')
                    ->whereDate('borrowed_at', '<=', $auditDate)
                    ->where(function ($q) use ($auditTs) {
                        $q->whereNull('returned_at')
                            ->orWhere('returned_at', '>', $auditTs);
                    });
            })
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    private function getLatestStatusLogs(AuditSession $session)
    {
        $latestIds = AuditLog::selectRaw('MAX(id) as id')
            ->where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->groupBy('control_number');

        return AuditLog::whereIn('id', $latestIds->pluck('id'))
            ->with(['bookCopy.book'])
            ->orderBy('control_number')
            ->get();
    }

    private function getLatestStatusByControlNumber(AuditSession $session, array $controlNumbers): array
    {
        $controlNumbers = array_values(array_unique(array_filter(array_map('strval', $controlNumbers))));
        if (count($controlNumbers) === 0) {
            return [];
        }

        $latestIds = AuditLog::selectRaw('MAX(id) as id')
            ->where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->whereIn('control_number', $controlNumbers)
            ->groupBy('control_number')
            ->pluck('id')
            ->all();

        if (count($latestIds) === 0) {
            return [];
        }

        return AuditLog::whereIn('id', $latestIds)
            ->get(['control_number', 'result_status'])
            ->pluck('result_status', 'control_number')
            ->toArray();
    }

    private function getScopedCopiesQuery(AuditSession $session)
    {
        $q = BookCopy::query();

        if (!$session->include_borrowed) {
            $auditTs = $session->started_at ?? now();
            $auditDate = $auditTs->toDateString();

            // Identify borrowed copies using active loan records (not just BookCopy.status).
            $q->whereDoesntHave('borrows', function ($q) use ($auditDate, $auditTs) {
                $q->whereNotNull('book_copy_id')
                    ->whereDate('borrowed_at', '<=', $auditDate)
                    ->where(function ($q) use ($auditTs) {
                        $q->whereNull('returned_at')
                            ->orWhere('returned_at', '>', $auditTs);
                    });
            });
        }

        if (!$session->include_lost_damaged) {
            $q->where('is_lost_damaged', false);
        }

        return $q;
    }

    private function getMissingCandidates(AuditSession $session, ?int $limit = null)
    {
        $q = $this->getMissingCandidatesQuery($session);

        if ($limit) {
            $q->limit($limit);
        }

        return $q->get();
    }

    private function getMissingCandidatesQuery(AuditSession $session)
    {
        // "Missing candidates" here means: copies in scope that have NOT been reviewed yet.
        // A copy is considered reviewed if it has any STATUS_SET event in this session
        // (scan auto-verifies by writing STATUS_SET VERIFIED, and manual marking also writes STATUS_SET).
        $reviewedCopyIds = AuditLog::where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->whereNotNull('book_copy_id')
            ->distinct()
            ->pluck('book_copy_id');

        return $this->getScopedCopiesQuery($session)
            ->whereNotIn('id', $reviewedCopyIds)
            ->with('book')
            ->orderBy('control_number');
    }

    private function getUnknownAccessions(AuditSession $session, ?int $limit = null)
    {
        $q = AuditLog::where('audit_session_id', $session->id)
            ->where('event_type', AuditLog::EVENT_SCAN)
            ->whereNull('book_copy_id')
            ->select('control_number', DB::raw('MAX(created_at) as last_seen'), DB::raw('COUNT(*) as scans'))
            ->groupBy('control_number')
            ->orderByDesc('last_seen');

        if ($limit) {
            $q->limit($limit);
        }

        return $q->get();
    }

    private function getOverduesDuringAudit(AuditSession $session, ?int $limit = null)
    {
        $auditDate = $session->started_at?->toDateString() ?? now()->toDateString();

        $q = Borrow::whereNull('returned_at')
            ->whereDate('due_date', '<', $auditDate)
            ->with(['book', 'bookCopy', 'student', 'teacher']);

        if ($limit) {
            $q->limit($limit);
        }

        return $q->get();
    }
}
