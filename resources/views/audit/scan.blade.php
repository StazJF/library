@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-0">Audit Scanning</h3>
            <div class="text-muted small">
                SY {{ $session->school_year }}
                • Started {{ $session->started_at?->format('M d, Y h:i A') }}
                • Status: <span class="badge bg-{{ $session->status === 'OPEN' ? 'warning' : 'success' }}">{{ $session->status }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('audit.summary', $session) }}" class="btn btn-outline-dark">Summary</a>
            <a href="{{ route('audit.index') }}" class="btn btn-dark">All Sessions</a>
        </div>
    </div>

    @if (session('audit_scan_message'))
        @php $lvl = session('audit_scan_level', 'success'); @endphp
        <div class="alert alert-{{ $lvl }}">{{ session('audit_scan_message') }}</div>
    @endif
    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Scan / Input Control Number</div>
                <div class="card-body">
                    @if($session->status !== 'OPEN')
                        <div class="alert alert-info mb-0">
                            This session is finalized. You can view the summary and report.
                        </div>
                    @else
                        <form method="POST" action="{{ route('audit.scan', $session) }}">
                            @csrf
                            <label class="form-label">Control Number (barcode)</label>
                            <input
                                id="controlNumberInput"
                                type="text"
                                name="control_number"
                                class="form-control form-control-lg @error('control_number') is-invalid @enderror"
                                placeholder="Scan here..."
                                autocomplete="off"
                                autofocus
                            />
                            @error('control_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Tip: most barcode scanners type then press Enter automatically.</div>
                        </form>

                        @php $lastCn = session('audit_last_control_number'); @endphp
                        @if($lastCn)
                            <hr>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="fw-semibold">Last scanned</div>
                                <div class="text-muted small">{{ $lastCn }}</div>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('audit.status', $session) }}">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ route('audit.show', $session) }}">
                                    <input type="hidden" name="control_number" value="{{ $lastCn }}">
                                    <input type="hidden" name="result_status" value="VERIFIED">
                                    <button class="btn btn-sm btn-outline-success" type="submit">Mark Verified</button>
                                </form>

                                <form method="POST" action="{{ route('audit.status', $session) }}">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ route('audit.show', $session) }}">
                                    <input type="hidden" name="control_number" value="{{ $lastCn }}">
                                    <input type="hidden" name="result_status" value="DAMAGED">
                                    <input type="hidden" name="remarks" value="Damaged during audit">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Mark Damaged</button>
                                </form>

                                <form method="POST" action="{{ route('audit.status', $session) }}">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ route('audit.show', $session) }}">
                                    <input type="hidden" name="control_number" value="{{ $lastCn }}">
                                    <input type="hidden" name="result_status" value="MISPLACED">
                                    <input type="hidden" name="remarks" value="Misplaced during audit">
                                    <button class="btn btn-sm btn-outline-warning" type="submit">Mark Misplaced</button>
                                </form>

                                <form method="POST" action="{{ route('audit.status', $session) }}">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ route('audit.show', $session) }}">
                                    <input type="hidden" name="control_number" value="{{ $lastCn }}">
                                    <input type="hidden" name="result_status" value="MISSING">
                                    <input type="hidden" name="remarks" value="Marked missing manually">
                                    <button class="btn btn-sm btn-outline-dark" type="submit">Mark Missing</button>
                                </form>
                            </div>
                            <div class="form-text mt-2">
                                For detailed notes (damage description / found location), use the Summary page.
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Live Summary</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="text-muted small">Total in scope</div><div class="fs-5 fw-semibold">{{ $summary['total_in_scope'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Scanned</div><div class="fs-5 fw-semibold">{{ $summary['scanned_total'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Verified</div><div class="fs-5 fw-semibold">{{ $summary['verified'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Missing (set)</div><div class="fs-5 fw-semibold">{{ $summary['missing'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Damaged</div><div class="fs-5 fw-semibold">{{ $summary['damaged'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Misplaced</div><div class="fs-5 fw-semibold">{{ $summary['misplaced'] }}</div></div>
                        {{-- <div class="col-6"><div class="text-muted small">Unknown scans</div><div class="fs-5 fw-semibold">{{ $summary['unknown_total'] }}</div></div>
                        <div class="col-6"><div class="text-muted small">Overdue borrows</div><div class="fs-5 fw-semibold">{{ $summary['overdue_total'] }}</div></div> --}}
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <a href="{{ route('audit.summary', $session) }}" class="btn btn-outline-dark btn-sm">View Details</a>
                        <a href="{{ route('audit.report', $session) }}" class="btn btn-dark btn-sm">Print Report</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Recent Activity</div>
                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Event</th>
                                <th>Control #</th>
                                <th>Book</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentScans as $log)
                                <tr>
                                    <td class="text-muted small">{{ $log->created_at?->format('h:i:s A') }}</td>
                                    <td class="text-muted small">
                                        @php
                                            $evt = $log->event_type ?? '';
                                            $evtBadge = match($evt) {
                                                'SCAN' => 'secondary',
                                                'STATUS_SET' => 'primary',
                                                default => 'light',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $evtBadge }}">{{ $evt }}</span>
                                    </td>
                                    <td class="fw-semibold">{{ $log->control_number }}</td>
                                    <td>
                                        @if($log->bookCopy && $log->bookCopy->book)
                                            <div class="fw-semibold">{{ $log->bookCopy->book->title }}</div>
                                            <div class="text-muted small">{{ $log->bookCopy->book->author }}</div>
                                        @else
                                            <span class="text-muted">Not found in DB</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $status = $latestStatusByControlNumber[$log->control_number] ?? null;
                                            // If this row is a STATUS_SET event, prefer the exact status set on this event.
                                            $label = ($log->event_type === 'STATUS_SET' && $log->result_status)
                                                ? $log->result_status
                                                : ($status ?: (!$log->book_copy_id ? 'UNKNOWN' : 'SCANNED'));
                                            $badge = match($label) {
                                                'VERIFIED' => 'success',
                                                'DAMAGED' => 'danger',
                                                'MISPLACED' => 'warning',
                                                'MISSING' => 'dark',
                                                'UNKNOWN' => 'warning',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ $label }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No activity yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Keep the scan input focused so barcode scanning is fast.
    (function () {
        const el = document.getElementById('controlNumberInput');
        if (!el) return;
        el.focus();
        el.addEventListener('blur', () => setTimeout(() => el.focus(), 50));
    })();
</script>
@endsection
