@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-print-none d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-0">Audit Report</h3>
            <div class="text-muted small">SY {{ $session->school_year }} • Session #{{ $session->id }}</div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('audit.summary', $session) }}" class="btn btn-outline-dark">Back</a>
            <button class="btn btn-dark" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print
            </button>
        </div>
    </div>

    <style>
        @media print {
            .sidebar, .topbar, .d-print-none { display: none !important; }
            .main-content { margin-left: 0 !important; }
            .content-wrapper { padding: 0 !important; }
            a[href]:after { content: "" !important; }
            .page-break { page-break-after: always; }
            .table { font-size: 12px; }
        }
        .report-meta td { padding: .15rem .4rem; }
        .report-title { letter-spacing: -0.4px; }
        .signature-block { margin-top: 30px; display: flex; justify-content: flex-end; break-inside: avoid; page-break-inside: avoid; }
        .signature-line { width: 260px; border-top: 1px solid #333; padding-top: 6px; text-align: center; font-size: 11px; color: #333; }
    </style>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <h4 class="report-title mb-1">Book Audit Report</h4>
                    <div class="text-muted">School Year: <span class="fw-semibold">{{ $session->school_year }}</span></div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">Generated</div>
                    <div class="fw-semibold">{{ now()->timezone(config('app.display_timezone'))->format('M d, Y h:i A') }}</div>
                </div>
            </div>

            <hr>

            <table class="table table-borderless report-meta mb-0">
                <tr>
                    <td class="text-muted" style="width: 160px;">Audit Start</td>
                    <td class="fw-semibold">{{ $session->started_at?->timezone(config('app.display_timezone'))->format('M d, Y h:i A') }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Audit End</td>
                    <td class="fw-semibold">{{ $session->ended_at ? $session->ended_at->format('M d, Y h:i A') : 'Not finalized' }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Prepared By</td>
                    <td class="fw-semibold">{{ $session->creator?->name ?: ($session->creator?->email ?: 'N/A') }}</td>
                </tr>
                <tr>
                    <td class="text-muted">Scope</td>
                    <td class="fw-semibold">
                        {{ $session->include_borrowed ? 'Includes borrowed copies' : 'Excludes borrowed copies' }},
                        {{ $session->include_lost_damaged ? 'Includes lost/damaged copies' : 'Excludes lost/damaged copies' }}
                    </td>
                </tr>
                @if($session->notes)
                    <tr>
                        <td class="text-muted">Notes</td>
                        <td>{{ $session->notes }}</td>
                    </tr>
                @endif
            </table>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Summary</div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-3"><div class="text-muted small">Total in scope</div><div class="fs-5 fw-semibold">{{ $summary['total_in_scope'] }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Inspected</div><div class="fs-5 fw-semibold">{{ $summary['scanned_total'] }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Verified</div><div class="fs-5 fw-semibold">{{ $summary['verified'] }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Missing</div><div class="fs-5 fw-semibold">{{ $summary['missing'] }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Damaged</div><div class="fs-5 fw-semibold">{{ $summary['damaged'] }}</div></div>
                {{-- <div class="col-6 col-md-3"><div class="text-muted small">Misplaced</div><div class="fs-5 fw-semibold">{{ $summary['misplaced'] }}</div></div> --}}
                <div class="col-6 col-md-3"><div class="text-muted small">Borrowed</div><div class="fs-5 fw-semibold">{{ $summary['borrowed'] ?? 0 }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Replaced</div><div class="fs-5 fw-semibold">{{ $summary['replaced'] ?? 0 }}</div></div>
                {{-- <div class="col-6 col-md-3"><div class="text-muted small">Unknown scans</div><div class="fs-5 fw-semibold">{{ $summary['unknown_total'] }}</div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Overdue</div><div class="fs-5 fw-semibold">{{ $summary['overdue_total'] }}</div></div> --}}
            </div>
        </div>
    </div>

    <div class="page-break"></div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Not Yet Reviewed (Candidates)</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Control #</th>
                        <th>Title</th>
                        <th>Author</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($missingCandidates as $i => $copy)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $copy->control_number }}</td>
                            <td>{{ $copy->book?->title ?? 'Unknown' }}</td>
                            <td class="text-muted small">{{ $copy->book?->author ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Marked Missing</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Control #</th>
                        <th>Title</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($missing as $i => $log)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $log->control_number }}</td>
                            <td>{{ $log->bookCopy?->book?->title ?? 'Unknown' }}</td>
                            <td class="text-muted small">{{ $log->remarks ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Replaced</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Control #</th>
                        <th>Title</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($replaced as $i => $log)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $log->control_number }}</td>
                            <td>{{ $log->bookCopy?->book?->title ?? 'Unknown' }}</td>
                            <td class="text-muted small">{{ $log->remarks ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Borrowed</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Control #</th>
                        <th>Title</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($borrowed as $i => $log)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $log->control_number }}</td>
                            <td>{{ $log->bookCopy?->book?->title ?? 'Unknown' }}</td>
                            <td class="text-muted small">{{ $log->remarks ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Damaged</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Control #</th>
                        <th>Title</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($damaged as $i => $log)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $log->control_number }}</td>
                            <td>{{ $log->bookCopy?->book?->title ?? 'Unknown' }}</td>
                            <td class="text-muted small">{{ $log->remarks ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Misplaced</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Control #</th>
                        <th>Title</th>
                        <th>Found Location</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($misplaced as $i => $log)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $log->control_number }}</td>
                            <td>{{ $log->bookCopy?->book?->title ?? 'Unknown' }}</td>
                            <td class="text-muted small">{{ $log->location ?? '' }}</td>
                            <td class="text-muted small">{{ $log->remarks ?? '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div> --}}

    {{-- <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Unknown Control Numbers (Inspected but Not in DB)</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Control #</th>
                        <th>Inspections</th>
                        <th>Last Seen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($unknownAccessions as $i => $u)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $u->control_number }}</td>
                            <td>{{ $u->scans }}</td>
                            <td class="text-muted small">{{ \Carbon\Carbon::parse($u->last_seen)->format('M d, Y h:i A') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Borrowed but Overdue During Audit</div>
        <div class="table-responsive">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Borrower</th>
                        <th>Book</th>
                        <th>Control #</th>
                        <th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overdues as $i => $b)
                        @php
                            $isTeacher = strtolower(trim((string) ($b->role ?? ''))) === 'teacher';
                            $borrower = $isTeacher ? ($b->teacher ?? null) : ($b->student ?? null);
                            $borrowerName = $isTeacher
                                ? (trim((string) ($borrower->name ?? '')) ?: 'Teacher')
                                : trim((string) (($borrower->first_name ?? '') . ' ' . ($borrower->last_name ?? '')));
                            $borrowerName = $borrowerName !== '' ? $borrowerName : 'Unknown';
                        @endphp
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $borrowerName }} <span class="text-muted small">({{ $isTeacher ? 'Teacher' : 'Student' }})</span></td>
                            <td>{{ $b->book?->title ?? 'Unknown' }}</td>
                            <td class="fw-semibold">{{ method_exists($b, 'getControlNumberRaw') ? $b->getControlNumberRaw() : ($b->bookCopy?->control_number ?? '-') }}</td>
                            <td class="text-muted small">{{ \Carbon\Carbon::parse($b->due_date)->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">None</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div> --}}

    {{-- Signature --}}
    <div class="signature-block">
        <div class="signature-line">Admin/Staff Signature</div>
    </div>

    <div class="mt-4 small text-muted">
        <div class="fw-semibold">Recommendations</div>
        <ol class="mb-0">
            <li>Investigate missing copies; verify if checked-out, transferred, or mislabeled.</li>
            <li>Repair/replace damaged copies; document condition for compliance reporting.</li>
            <li>Resolve unknown control numbers (encode missing copies or fix ctrl# labels).</li>
            <li>Follow up overdue borrowers and document actions taken.</li>
        </ol>
    </div>
</div>
@endsection
