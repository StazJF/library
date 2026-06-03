@extends('layouts.app')

@section('content')
<div class="container-fluid p-0">
    <div class="d-flex align-items-center mb-4 gap-3 px-4 pt-3">
        <a href="{{ route('teachers.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Teachers
        </a>
        <h1 class="h3 mb-0">Borrow History - {{ $teacher->name }}</h1>
    </div>

    <div class="row mb-4 mx-0 px-4">
        <div class="col-md-12 ps-0 pe-0">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Name:</strong> {{ $teacher->name }}
                            </p>
                            <p class="mb-2">
                                <strong>Email:</strong> {{ $teacher->email }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Gender:</strong> {{ ucfirst($teacher->gender) }}
                            </p>
                            <p class="mb-2">
                                <strong>Phone:</strong> {{ $teacher->phone_number }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mx-4">
        <div class="card-header bg-white text-black">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-book me-2"></i>All Borrow History
                </h5>
                @php
                    $currentOrigin = $filterState['origin'] ?? 'all';
                    $originParam = $currentOrigin === 'all' ? null : $currentOrigin;
                    $currentStatus = $filterState['status'] ?? 'all';
                    $statusParam = $currentStatus === 'all' ? null : $currentStatus;
                @endphp
                <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
                    <form method="GET" action="{{ route('teachers.borrow-history', ['teacher' => $teacher]) }}" class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Borrow Type</span>
                            <select name="origin" class="form-select form-select-sm" style="width: 170px;">
                                <option value="" {{ $currentOrigin === 'all' ? 'selected' : '' }}>All</option>
                                <option value="personal" {{ $currentOrigin === 'personal' ? 'selected' : '' }}>Personal</option>
                                <option value="distribution" {{ $currentOrigin === 'distribution' ? 'selected' : '' }}>Distribution</option>
                            </select>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Book Status</span>
                            <select name="status" class="form-select form-select-sm" style="width: 170px;">
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

                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher]) }}" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </form>

                    <div class="btn-group" role="group" aria-label="Borrow Type Filter">
                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher]) }}"
                           class="btn btn-sm {{ $currentOrigin === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-list me-1"></i>All
                        </a>
                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher, 'origin' => 'personal', 'status' => $statusParam]) }}"
                           class="btn btn-sm {{ $currentOrigin === 'personal' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-person-check me-1"></i>Personal
                        </a>
                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher, 'origin' => 'distribution', 'status' => $statusParam]) }}"
                           class="btn btn-sm {{ $currentOrigin === 'distribution' ? 'btn-primary' : 'btn-outline-primary' }}">
                            <i class="bi bi-box-seam me-1"></i>Distribution
                        </a>
                    </div>

                    <div class="btn-group" role="group" aria-label="Book Status Filter">
                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher, 'origin' => $originParam]) }}"
                           class="btn btn-sm {{ $currentStatus === 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                            Status: All
                        </a>
                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher, 'origin' => $originParam, 'status' => 'lost']) }}"
                           class="btn btn-sm {{ $currentStatus === 'lost' ? 'btn-danger' : 'btn-outline-danger' }}">
                            Lost @if(($statusCounts['lost'] ?? 0) > 0) <span class="badge bg-light text-danger ms-1">{{ $statusCounts['lost'] }}</span> @endif
                        </a>
                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher, 'origin' => $originParam, 'status' => 'damaged']) }}"
                           class="btn btn-sm {{ $currentStatus === 'damaged' ? 'btn-warning' : 'btn-outline-warning' }}">
                            Damaged @if(($statusCounts['damaged'] ?? 0) > 0) <span class="badge bg-light text-dark ms-1">{{ $statusCounts['damaged'] }}</span> @endif
                        </a>
                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher, 'origin' => $originParam, 'status' => 'repaired']) }}"
                           class="btn btn-sm {{ $currentStatus === 'repaired' ? 'btn-info' : 'btn-outline-info' }}">
                            Repaired @if(($statusCounts['repaired'] ?? 0) > 0) <span class="badge bg-light text-info ms-1">{{ $statusCounts['repaired'] }}</span> @endif
                        </a>
                        <a href="{{ route('teachers.borrow-history', ['teacher' => $teacher, 'origin' => $originParam, 'status' => 'found']) }}"
                           class="btn btn-sm {{ $currentStatus === 'found' ? 'btn-success' : 'btn-outline-success' }}">
                            Found @if(($statusCounts['found'] ?? 0) > 0) <span class="badge bg-light text-success ms-1">{{ $statusCounts['found'] }}</span> @endif
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($borrows->count() > 0)
                @if(($filterState['status'] ?? 'all') !== 'all' && ($statusCounts['issues'] ?? 0) > 0)
                    <div class="alert alert-warning m-3 mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <strong><i class="bi bi-exclamation-circle me-1"></i>Lost:</strong> {{ $statusCounts['lost'] ?? 0 }}
                            </div>
                            <div class="col-md-3">
                                <strong><i class="bi bi-tools me-1"></i>Damaged:</strong> {{ $statusCounts['damaged'] ?? 0 }}
                            </div>
                            <div class="col-md-3">
                                <strong><i class="bi bi-check-circle me-1"></i>Repaired:</strong> {{ $statusCounts['repaired'] ?? 0 }}
                            </div>
                            <div class="col-md-3">
                                <strong><i class="bi bi-search me-1"></i>Found:</strong> {{ $statusCounts['found'] ?? 0 }}
                            </div>
                        </div>
                    </div>
                @endif
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 fw-semibold" style="width: 40px;">#</th>
                                <th class="border-0 fw-semibold">Book Title</th>
                                <th class="border-0 fw-semibold">Author</th>
                                <th class="border-0 fw-semibold">ISBN</th>
                                <th class="border-0 fw-semibold">Advisory Class</th>
                                <th class="border-0 fw-semibold">Control No.</th>
                                <th class="border-0 fw-semibold">Borrowed On</th>
                                <th class="border-0 fw-semibold">Due Date</th>
                                <th class="border-0 fw-semibold">Returned On</th>
                                <th class="border-0 fw-semibold">Status</th>
                                <th class="border-0 fw-semibold">Book Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($borrows as $index => $borrow)
                                @php
                                    $bookTitle = $borrow->book ? $borrow->book->title : 'Book not found';
                                    $bookAuthor = $borrow->book ? ($borrow->book->author ?? 'N/A') : 'N/A';
                                    $bookIsbn = $borrow->book ? ($borrow->book->isbn ?? 'N/A') : 'N/A';
                                    $borrowedAt = $borrow->borrowed_at ? \Carbon\Carbon::parse($borrow->borrowed_at)->format('M d, Y') : 'N/A';
                                    $dueDate = $borrow->due_date ? \Carbon\Carbon::parse($borrow->due_date)->format('M d, Y') : 'N/A';
                                    $returnedAt = $borrow->returned_at ? \Carbon\Carbon::parse($borrow->returned_at)->format('M d, Y') : '-';
                                    $status = $borrow->returned_at ? 'Returned' : 'Active';
                                    $statusBadgeClass = $borrow->returned_at ? 'bg-success' : 'bg-warning';
                                    $remark = $borrow->remark ?? '-';
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong>{{ $bookTitle }}</strong>
                                    </td>
                                    <td>{{ $bookAuthor }}</td>
                                    <td>
                                        <small class="text-muted">{{ $bookIsbn }}</small>
                                    </td>
                                    <td>
                                        @if(($borrow->origin ?? '') === 'distribution' && ($borrow->advisory_grade || $borrow->advisory_section))
                                            <span>Grade {{ $borrow->advisory_grade ?? '-' }} {{ $borrow->advisory_section ?? '' }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="font-monospace">{{ $borrow->getCopyNumberDisplay() }}</div>
                                        <div class="small text-muted">Ctrl#: <span class="font-monospace">{{ $borrow->getControlNumberRaw() }}</span></div>
                                    </td>
                                    <td>{{ $borrowedAt }}</td>
                                    <td>{{ $dueDate }}</td>
                                    <td>{{ $returnedAt }}</td>
                                    <td>
                                        <span class="badge {{ $statusBadgeClass }}">
                                            {{ $status }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $lossType = $borrow->getLossType(); // lost, damaged, repaired, found (based on latest history when available)
                                            if (!$lossType) {
                                                if (($borrow->remark ?? '') === 'Lost') {
                                                    $lossType = 'lost';
                                                } elseif (($borrow->remark ?? '') === 'Damage') {
                                                    $lossType = 'damaged';
                                                }
                                            }

                                            $remarkBadgeClass = 'bg-secondary';
                                            $remarkIcon = '<i class="bi bi-info-circle me-1"></i>';
                                            $displayRemark = $lossType ? ucfirst($lossType) : '';

                                            if ($lossType === 'lost') {
                                                $remarkBadgeClass = 'bg-danger';
                                                $remarkIcon = '<i class="bi bi-exclamation-triangle me-1"></i>';
                                            } elseif ($lossType === 'damaged') {
                                                $remarkBadgeClass = 'bg-warning text-dark';
                                                $remarkIcon = '<i class="bi bi-tools me-1"></i>';
                                            } elseif ($lossType === 'repaired') {
                                                $remarkBadgeClass = 'bg-info text-white';
                                                $remarkIcon = '<i class="bi bi-check-circle me-1"></i>';
                                            } elseif ($lossType === 'found') {
                                                $remarkBadgeClass = 'bg-success';
                                                $remarkIcon = '<i class="bi bi-check-circle me-1"></i>';
                                            }
                                        @endphp
                                        @if($lossType)
                                            <span class="badge {{ $remarkBadgeClass }}">
                                                {!! $remarkIcon !!}{{ $displayRemark }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-0">
                                <strong>Total Borrowed:</strong> {{ $borrows->count() }}
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-0">
                                <strong>Active:</strong> {{ $borrows->whereNull('returned_at')->count() }}
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-0">
                                <strong>Returned:</strong> {{ $borrows->whereNotNull('returned_at')->count() }}
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <p>No borrow history found for this teacher.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    body {
        padding-left: 0;
        padding-right: 0;
    }
</style>
@endsection
