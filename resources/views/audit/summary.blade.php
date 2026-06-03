@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-0">Audit Summary</h3>
            <div class="text-muted small">
                SY {{ $session->school_year }}
                • Started {{ $session->started_at?->timezone(config('app.display_timezone'))->format('M d, Y h:i A') }}
                @if($session->ended_at)
                    • Ended {{ $session->ended_at?->timezone(config('app.display_timezone'))->format('M d, Y h:i A') }}
                @endif
                • Status: <span class="badge bg-{{ $session->status === 'OPEN' ? 'warning' : 'success' }}">{{ $session->status }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('audit.show', $session) }}" class="btn btn-outline-dark">Inspecting</a>
            <a href="{{ route('audit.report', $session) }}" class="btn btn-dark">Print Report</a>
        </div>
    </div>

    @php $isAdmin = (auth()->user()->role ?? null) === 'admin'; @endphp

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Admin Audit Actions</div>
                <div class="card-body">
                    @if($session->status !== 'OPEN')
                        <div class="alert alert-info mb-0">Session is finalized. Re-open (admin) to edit statuses.</div>
                    @elseif(!$isAdmin)
                        <div class="alert alert-info mb-0">Final audit actions are restricted to Admin accounts.</div>
                    @else
                        <form method="POST" action="{{ route('audit.status', $session) }}">
                            @csrf
                            <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Control Number</label>
                                <input type="text" name="control_number" class="form-control" placeholder="e.g. 001-001" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Action Remark</label>
                                <select name="result_status" class="form-select" required>
                                    <option value="VERIFIED">Verified</option>
                                    <option value="DAMAGED">Damaged</option>
                                    {{-- <option value="MISPLACED">Misplaced</option> --}}
                                    <option value="MISSING">Missing</option>
                                    <option value="BORROWED">Borrowed</option>
                                    {{-- <option value="REPLACED">Replaced</option> --}}
                                </select>
                            </div>
                            {{-- <div class="row g-2 mb-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted mb-1">Replacement Control # (for Replaced)</label>
                                    <input type="text" name="replacement_control_number" class="form-control" placeholder="e.g. 001-999">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Acq. Year</label>
                                    <input type="number" name="replacement_acquisition_year" class="form-control" placeholder="2026">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted mb-1">Condition</label>
                                    <input type="text" name="replacement_condition" class="form-control" placeholder="Good">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Found Location (optional)</label>
                                <input type="text" name="location" class="form-control" placeholder="e.g. G10 Room Shelf A">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted mb-1">Remarks (optional)</label>
                                <input type="text" name="remarks" class="form-control" placeholder="e.g. torn cover, missing pages">
                            </div> --}}
                            <button class="btn btn-outline-dark w-100" type="submit">
                                <i class="fas fa-save me-2"></i>Apply Action
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Totals</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="text-muted small">Total in scope</div><div class="fs-5 fw-semibold">{{ $summary['total_in_scope'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Inspected</div><div class="fs-5 fw-semibold">{{ $summary['scanned_total'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Verified</div><div class="fs-5 fw-semibold">{{ $summary['verified'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Missing</div><div class="fs-5 fw-semibold">{{ $summary['missing'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Damaged</div><div class="fs-5 fw-semibold">{{ $summary['damaged'] }}</div></div>
                        {{-- <div class="col-6"><div class="text-muted small">Misplaced</div><div class="fs-5 fw-semibold">{{ $summary['misplaced'] }}</div></div> --}}
                        <div class="col-6"><div class="text-muted small">Borrowed</div><div class="fs-5 fw-semibold">{{ $summary['borrowed'] ?? 0 }}</div></div>
                        <div class="col-6"><div class="text-muted small">Replaced</div><div class="fs-5 fw-semibold">{{ $summary['replaced'] ?? 0 }}</div></div>
                        {{-- <div class="col-6"><div class="text-muted small">Unknown scans</div><div class="fs-5 fw-semibold">{{ $summary['unknown_total'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Overdue</div><div class="fs-5 fw-semibold">{{ $summary['overdue_total'] }}</div></div> --}}
                    </div>

                    <hr>

                    @if($session->status === 'OPEN')
                        <form method="POST" action="{{ route('audit.finalize', $session) }}" onsubmit="return confirm('Finalize this audit session? This will compute missing items based on books not inspected.');">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-lock me-2"></i>Finalize Audit
                            </button>
                        </form>
                    @else
                        @php
                            $autoMissingToUndo = ($latestStatuses ?? collect())
                                ->where('result_status', 'MISSING')
                                ->where('remarks', 'Auto-marked missing on finalize')
                                ->count();
                        @endphp

                        @if($autoMissingToUndo > 0)
                            <form method="POST" action="{{ route('audit.undo-auto-missing', $session) }}" class="mb-2" onsubmit="return confirm('Undo auto-marked missing copies from finalize?');">
                                @csrf
                                <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                                <button type="submit" class="btn btn-outline-warning w-100">
                                    <i class="fas fa-undo me-2"></i>Undo Auto-Missing ({{ $autoMissingToUndo }})
                                </button>
                            </form>
                        @endif

                        @if((auth()->user()->role ?? null) === 'admin')
                            <form method="POST" action="{{ route('audit.reopen', $session) }}" onsubmit="return confirm('Re-open this audit session for additional inspecting?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-dark w-100">
                                    <i class="fas fa-unlock me-2"></i>Re-open (Admin)
                                </button>
                            </form>
                        @else
                            <div class="alert alert-info mb-0">This session is finalized.</div>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Recommendations</div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Review missing candidates and confirm if any are currently borrowed or transferred.</li>
                        <li>Encode or correct any “unknown” control numbers (label issues).</li>
                        <li>Repair/replace damaged copies and update condition tracking.</li>
                        <li>Follow up overdue borrowers based on school policy.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Marked Missing (Lost Confirmation)</div>
                <div class="card-body p-0">
                    @if(($markedMissing ?? collect())->count() === 0)
                        <div class="p-3 text-muted">No copies are currently marked as Missing.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Control #</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($markedMissing as $log)
                                        @php
                                            $copy = $log->bookCopy;
                                            $isLostConfirmed = $copy && ($copy->status ?? null) === 'lost';
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $log->control_number }}</td>
                                            <td>{{ $copy?->book?->title ?? 'Unknown' }}</td>
                                            <td>
                                                @if($isLostConfirmed)
                                                    <span class="badge bg-danger">LOST (confirmed)</span>
                                                @else
                                                    <span class="badge bg-dark">MISSING</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($isAdmin && !$isLostConfirmed && $copy)
                                                    <form method="POST" action="{{ route('audit.confirmLost', $session) }}" class="d-inline" onsubmit="return confirm('Confirm this copy as LOST? This will send it to Lost & Damaged Books.');">
                                                        @csrf
                                                        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                                                        <input type="hidden" name="control_number" value="{{ $log->control_number }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Confirm Lost</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('audit.returnMissing', $session) }}" class="d-inline" onsubmit="return confirm('Mark this copy as RETURNED / FOUND and restore it to available inventory?');">
                                                        @csrf
                                                        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                                                        <input type="hidden" name="control_number" value="{{ $log->control_number }}">
                                                        <button type="submit" class="btn btn-sm btn-outline-success ms-1">Returned</button>
                                                    </form>
                                                @elseif(!$isAdmin && !$isLostConfirmed)
                                                    <span class="text-muted small">Admin only</span>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Missing Candidates </div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Control #</th>
                                <th>Title</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($missingCandidates as $copy)
                                @php
                                    $current = $missingStatusByCn[$copy->control_number] ?? null;
                                    $badge = match($current) {
                                        'VERIFIED' => 'success',
                                        'DAMAGED' => 'danger',
                                        'MISPLACED' => 'warning',
                                        'MISSING' => 'dark',
                                        'BORROWED' => 'info',
                                        'REPLACED' => 'secondary',
                                        default => 'secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $copy->control_number }}</td>
                                    <td>{{ $copy->book?->title ?? 'Unknown' }}</td>
                                    <td class="text-end">
                                        @if($session->status === 'OPEN' && $isAdmin)
                                            <form method="POST" action="{{ route('audit.status', $session) }}" class="d-inline audit-status-form">
                                                @csrf
                                                <input type="hidden" name="control_number" value="{{ $copy->control_number }}">
                                                <input type="hidden" name="result_status" value="{{ $current ?? 'MISSING' }}">
                                                <input type="hidden" name="remarks" value="Set from missing candidates">
                                                <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-{{ $current === 'VERIFIED' ? 'success' : ($current === 'DAMAGED' ? 'danger' : ($current === 'MISPLACED' ? 'warning' : ($current === 'MISSING' ? 'dark' : ($current === 'BORROWED' ? 'info' : 'secondary')))) }} dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        {{ $current ?? 'UNMARKED' }}
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><button class="dropdown-item {{ $current === 'VERIFIED' ? 'active' : '' }}" type="button" data-set-audit-status="VERIFIED">Verified</button></li>
                                                        <li><button class="dropdown-item {{ $current === 'MISSING' ? 'active' : '' }}" type="button" data-set-audit-status="MISSING">Missing</button></li>
                                                        <li><button class="dropdown-item {{ $current === 'DAMAGED' ? 'active' : '' }}" type="button" data-set-audit-status="DAMAGED">Damaged</button></li>
                                                        {{-- <li><button class="dropdown-item {{ $current === 'MISPLACED' ? 'active' : '' }}" type="button" data-set-audit-status="MISPLACED">Misplaced</button></li> --}}
                                                        <li><button class="dropdown-item {{ $current === 'BORROWED' ? 'active' : '' }}" type="button" data-set-audit-status="BORROWED">Borrowed</button></li>
                                                    </ul>
                                                </div>
                                            </form>
                                        @else
                                            @php
                                                $label = $current ?? 'UNMARKED';
                                                $badge = match($label) {
                                                    'VERIFIED' => 'success',
                                                    'DAMAGED' => 'danger',
                                                    // 'MISPLACED' => 'warning',
                                                    'MISSING' => 'dark',
                                                    'BORROWED' => 'info',
                                                    'REPLACED' => 'secondary',
                                                    default => 'secondary',
                                                };
                                            @endphp
                                            <span class="badge bg-{{ $badge }}">{{ $label }}</span>
                                            @if($session->status === 'OPEN' && !$isAdmin)
                                                <div class="text-muted small">Admin only</div>
                                            @elseif($session->status !== 'OPEN')
                                                <div class="text-muted small">Finalized</div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No missing candidates (based on current scope and scans).</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="text-muted small">
                            @if(method_exists($missingCandidates, 'total'))
                                Showing {{ $missingCandidates->firstItem() ?? 0 }} to {{ $missingCandidates->lastItem() ?? 0 }}
                                of {{ $missingCandidates->total() }} items.
                            @else
                                Showing {{ count($missingCandidates) }} items.
                            @endif
                        </div>
                        @if(method_exists($missingCandidates, 'links'))
                            <div>
                                {{ $missingCandidates->links() }}
                            </div>
                        @endif
                    </div>
                    <div class="text-muted small mt-1">Print report for the full list.</div>
                </div>
            </div>

            {{-- <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Unknown Control Numbers (Inspected but Not in DB)</div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Control #</th>
                                <th>Inspections</th>
                                <th>Last Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($unknownAccessions as $u)
                                <tr>
                                    <td class="fw-semibold">{{ $u->control_number }}</td>
                                    <td>{{ $u->scans }}</td>
                                    <td class="text-muted small">{{ \Carbon\Carbon::parse($u->last_seen)->format('M d, Y h:i A') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-4">No unknown control numbers inspected.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-muted small">
                    Showing up to 200 items.
                </div>
            </div> --}}

            {{-- <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Borrowed but Overdue During Audit</div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>Book</th>
                                <th>Control #</th>
                                <th>Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($overdues as $b)
                                @php
                                    $isTeacher = strtolower(trim((string) ($b->role ?? ''))) === 'teacher';
                                    $borrower = $isTeacher ? ($b->teacher ?? null) : ($b->student ?? null);
                                    $borrowerName = $isTeacher
                                        ? (trim((string) ($borrower->name ?? '')) ?: 'Teacher')
                                        : trim((string) (($borrower->first_name ?? '') . ' ' . ($borrower->last_name ?? '')));
                                    $borrowerName = $borrowerName !== '' ? $borrowerName : 'Unknown';
                                @endphp
                                <tr>
                                    <td>{{ $borrowerName }} <span class="text-muted small">({{ $isTeacher ? 'Teacher' : 'Student' }})</span></td>
                                    <td>{{ $b->book?->title ?? 'Unknown' }}</td>
                                    <td class="fw-semibold">{{ method_exists($b, 'getControlNumberRaw') ? $b->getControlNumberRaw() : ($b->bookCopy?->control_number ?? '-') }}</td>
                                    <td class="text-muted small">{{ \Carbon\Carbon::parse($b->due_date)->format('M d, Y') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">No overdue borrows at audit date.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-muted small">
                    Showing up to 200 items.
                </div>
            </div> --}}
        </div>
    </div>
</div>

<script>
    (function () {
        document.querySelectorAll('.audit-status-form [data-set-audit-status]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.closest('form');
                if (!form) return;
                const input = form.querySelector('input[name=\"result_status\"]');
                if (!input) return;
                input.value = btn.getAttribute('data-set-audit-status');
                form.submit();
            });
        });
    })();
</script>
@endsection
