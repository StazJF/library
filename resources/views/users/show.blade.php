@extends('layouts.app')

@section('content')

<div class="mb-3">
    <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Students
    </a>
    <a href="{{ route('users.print-user', $user->id) }}" class="btn btn-primary btn-sm" target="_blank">
        <i class="bi bi-printer"></i> Print
    </a>
</div>

<div class="row">
    <!-- User Details Section -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h4>User Details</h4>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $user->first_name }} {{ $user->last_name }}</p>
                <p><strong>Grade & Section:</strong> {{ $user->grade_section ?? '-' }}</p>
                <p><strong>LRN:</strong> {{ $user->lrn ?? '-' }}</p>
                <p><strong>Gender:</strong> {{ $user->gender ? ucfirst(strtolower($user->gender)) : '-' }}</p>
                <p><strong>Phone:</strong> {{ $user->phone_number ?? '-' }}</p>
                <p><strong>Address:</strong> {{ $user->address ?? '-' }}</p>
                <p><strong>Total Books Borrowed:</strong> {{ $totalBorrows ?? $user->borrows->count() }}</p>
            </div>
        </div>
    </div>

    <!-- Borrowing History Section -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Borrowing History</h5>
                    @php
                        $currentOrigin = $filterState['origin'] ?? 'all';
                        $currentStatus = $filterState['status'] ?? 'all';
                    @endphp
                    <form method="GET" action="{{ route('users.show', $user->id) }}" class="d-flex flex-wrap gap-2 align-items-center">
                        {{-- <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Borrow Type</span>
                            <select name="origin" class="form-select form-select-sm" style="width: 160px;">
                                <option value="" {{ $currentOrigin === 'all' ? 'selected' : '' }}>All</option>
                                <option value="personal" {{ $currentOrigin === 'personal' ? 'selected' : '' }}>Personal</option>
                                <option value="distribution" {{ $currentOrigin === 'distribution' ? 'selected' : '' }}>Distribution</option>
                            </select>
                        </div> --}}
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Book Status</span>
                            <select name="status" class="form-select form-select-sm" style="width: 160px;">
                                <option value="" {{ $currentStatus === 'all' ? 'selected' : '' }}>All</option>
                                <option value="lost" {{ $currentStatus === 'lost' ? 'selected' : '' }}>Lost</option>
                                <option value="damaged" {{ $currentStatus === 'damaged' ? 'selected' : '' }}>Damaged</option>
                                <option value="repaired" {{ $currentStatus === 'repaired' ? 'selected' : '' }}>Repaired</option>
                                <option value="found" {{ $currentStatus === 'found' ? 'selected' : '' }}>Found</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('users.show', $user->id) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </form>
                </div>
            </div>
            <div class="card-body">
                @php
                    $rows = $borrows ?? $user->borrows;
                @endphp
                @if($rows->count() > 0)
                @php
                    $today = \Carbon\Carbon::today();
                    // Penalty removed — using remarks instead
                @endphp
                <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                    <table class="table table-sm table-hover">
                        <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                            <tr>
                                <th style="width: 14%;">Book Title</th>
                                <th style="width: 10%;">Author</th>
                                <th style="width: 12%;">Control No.</th>
                                <th style="width: 11%;">Borrow Date</th>
                                <th style="width: 11%;">Due Date</th>
                                <th style="width: 11%;">Returned On</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 14%;">Remarks</th>
                                <th style="width: 17%;">Book Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $borrow)
                                @php
                                    $borrowDate = $borrow->borrowed_at;
                                    $dueDate    = $borrow->due_date;
                                    $returnedAt = $borrow->returned_at;

                                    // Overdue days only if today is after due date
                                    $overdueDays = 0;
                                    if ($dueDate && $today->gt($dueDate)) {
                                        $overdueDays = (int) ceil($today->diffInDays($dueDate));
                                    }

                                    $penalty = 0;
                                    // Prefer stored admin remark if present
                                    if (!empty($borrow->remark)) {
                                        $remark = $borrow->remark;
                                    } else {
                                        $remark = $overdueDays > 0 ? "{$overdueDays} day(s) overdue" : 'Good Standing';
                                    }

                                    $lossType = $borrow->getLossType();
                                    if (!$lossType) {
                                        if (($borrow->remark ?? '') === 'Lost') {
                                            $lossType = 'lost';
                                        } elseif (($borrow->remark ?? '') === 'Damage') {
                                            $lossType = 'damaged';
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $borrow->book?->title ?? 'Book not found' }}</td>
                                    <td>{{ $borrow->book?->author ?? '-' }}</td>
                                    <td>
                                        <div class="font-monospace">{{ method_exists($borrow, 'getCopyNumberDisplay') ? $borrow->getCopyNumberDisplay() : ($borrow->copy_number ?? $borrow->bookCopy?->control_number ?? '-') }}</div>
                                        <div class="small text-muted">Ctrl#: <span class="font-monospace">{{ method_exists($borrow, 'getControlNumberRaw') ? $borrow->getControlNumberRaw() : ($borrow->copy_number ?? $borrow->bookCopy?->control_number ?? '-') }}</span></div>
                                    </td>
                                    <td>{{ $borrowDate ? \Carbon\Carbon::parse($borrowDate)->format('F j, Y') : '-' }}</td>
                                    <td>{{ $dueDate ? \Carbon\Carbon::parse($dueDate)->format('F j, Y') : '-' }}</td>
                                    <td>{{ $returnedAt ? \Carbon\Carbon::parse($returnedAt)->format('F j, Y') : '-' }}</td>
                                    <td>
                                        <span style="color: {{ $borrow->returned_at ? '#198754' : '#ff9800' }}; font-weight: 500;">
                                            {{ $borrow->returned_at ? 'Returned' : 'Borrowed' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $lowerRemark = strtolower($remark);
                                            if (str_contains($lowerRemark, 'overdue') || $lowerRemark === 'lost' || $lowerRemark === 'damage') {
                                                $remarkColor = '#dc3545';
                                            } elseif ($lowerRemark === 'late return') {
                                                $remarkColor = '#ff9800';
                                            } else {
                                                $remarkColor = '#198754';
                                            }
                                        @endphp
                                        <span style="color: {{ $remarkColor }}; font-weight: 500;">{{ $remark }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $issueColor = '#6c757d';
                                            $issueIcon = '<i class="bi bi-info-circle me-1"></i>';
                                            $issueLabel = $lossType ? ucfirst($lossType) : '';
                                            if ($lossType === 'lost') {
                                                $issueColor = '#dc3545';
                                                $issueIcon = '<i class="bi bi-exclamation-triangle me-1"></i>';
                                            } elseif ($lossType === 'damaged') {
                                                $issueColor = '#ff9800';
                                                $issueIcon = '<i class="bi bi-tools me-1"></i>';
                                            } elseif ($lossType === 'repaired') {
                                                $issueColor = '#0dcaf0';
                                                $issueIcon = '<i class="bi bi-check-circle me-1"></i>';
                                            } elseif ($lossType === 'found') {
                                                $issueColor = '#198754';
                                                $issueIcon = '<i class="bi bi-check-circle me-1"></i>';
                                            }
                                        @endphp
                                        @if($lossType)
                                            <span style="color: {{ $issueColor }}; font-weight: 500;">{!! $issueIcon !!}{{ $issueLabel }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted">No borrowing history found.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
