<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\AuditSession;
use App\Models\BookCopy;
use App\Models\Borrow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuditController extends Controller
{
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

        // Last 10 audit events for quick feedback (scan + status updates)
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

        // If we already have a status for this control number in this session, treat as duplicate scan.
        $hasAnyStatus = AuditLog::where('audit_session_id', $session->id)
            ->where('control_number', $controlNumber)
            ->where('event_type', AuditLog::EVENT_STATUS_SET)
            ->exists();

        $message = null;
        $level = 'success';

        if (!$bookCopy) {
            $message = "Scanned control number '{$controlNumber}' not found in the database.";
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
                $message = "Duplicate scan: {$bookCopy->book?->title} (CN: {$controlNumber})";
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
            ])],
            'location' => ['nullable', 'string', 'max:120'],
            'remarks' => ['nullable', 'string', 'max:255'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $controlNumber = $this->normalizeControlNumber($data['control_number']);

        $bookCopy = BookCopy::where('control_number', $controlNumber)->first();

        AuditLog::create([
            'audit_session_id' => $session->id,
            'event_type' => AuditLog::EVENT_STATUS_SET,
            'control_number' => $controlNumber,
            'book_copy_id' => $bookCopy?->id,
            'result_status' => $data['result_status'],
            'location' => $data['location'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => Auth::id(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Audit Status Set',
            'target_type' => 'AuditSession',
            'target_id' => $session->id,
            'details' => "Set {$data['result_status']} for control number {$controlNumber}.",
        ]);

        return $this->safeRedirect(
            $request,
            route('audit.show', $session),
            "Status updated for {$controlNumber}."
        );
    }

    public function summary(AuditSession $session)
    {
        $this->ensureCanAccessSession($session);

        $summary = $this->buildSessionSummary($session);
        $missingCandidates = $this->getMissingCandidatesQuery($session)
            ->paginate(50, ['*'], 'missingPage')
            ->withQueryString();
        $missingStatusByCn = $this->getLatestStatusByControlNumber(
            $session,
            $missingCandidates->getCollection()->pluck('control_number')->all()
        );
        $unknownAccessions = $this->getUnknownAccessions($session, limit: 200);
        $overdues = $this->getOverduesDuringAudit($session, limit: 200);

        return view('audit.summary', compact('session', 'summary', 'missingCandidates', 'missingStatusByCn', 'unknownAccessions', 'overdues'));
    }

    private function safeRedirect(Request $request, string $fallbackUrl, string $flashMessage)
    {
        $target = $request->input('redirect_to');
        if (is_string($target) && trim($target) !== '') {
            $parts = parse_url($target);
            $host = $parts['host'] ?? null;
            // Only allow same-host redirects to avoid open redirect issues.
            if ($host === null || strcasecmp($host, (string) $request->getHost()) === 0) {
                return redirect($target)->with('status', $flashMessage);
            }
        }

        return redirect($fallbackUrl)->with('status', $flashMessage);
    }

    public function finalize(Request $request, AuditSession $session)
    {
        $this->ensureCanAccessSession($session);
        if (!$session->isOpen()) {
            return redirect()->route('audit.summary', $session)
                ->with('status', 'This audit session is already finalized.');
        }

        DB::transaction(function () use ($session) {
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
        $latestStatuses = $this->getLatestStatusLogs($session);

        $counts = $latestStatuses
            ->groupBy('result_status')
            ->map(fn ($g) => $g->count())
            ->toArray();

        // "Scanned" should reflect items already processed in the audit flow.
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
        ];
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
            $q->where('status', '!=', 'borrowed');
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
