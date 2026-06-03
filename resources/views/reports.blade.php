@extends('layouts.app')

@section('content')
<style>
    /* Status badge styling for new statuses */
    
        .container {
        max-width: 1900px;
        width: 100%;
    }
    .badge.bg-repaired {
        background-color: #198754 !important;
        color: white;
    }
    
    .badge.bg-found {
        background-color: #20c997 !important;
        color: white;
    }
    
    /* Status transition indicators */
    .status-indicator {
        font-size: 0.9rem;
        margin-left: 0.3rem;
        vertical-align: middle;
    }
    
    .status-indicator.damaged {
        color: #dc3545;
        title: "Damaged - For Repair";
    }
    
    .status-indicator.repaired {
        color: #198754;
    }
    
    .status-indicator.lost {
        color: #fd7e14;
    }
    
    .status-indicator.found {
        color: #20c997;
    }
    
    /* Table row highlight for lost/damaged items */
    tr:has(.status-indicator.damaged),
    tr:has(.status-indicator.lost) {
        background-color: #fff3cd;
    }
    
    /* Smooth transition for status changes */
    .badge {
        transition: all 0.2s ease;
    }

    /* Reports navigator */
    .reports-nav {
        position: sticky;
        top: 72px; /* sits below the app topbar */
        z-index: 1020;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(8px);
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0.65rem 0.75rem;
        box-shadow: 0 .25rem .75rem rgba(0,0,0,.05);
    }
    .reports-nav .nav-pills .nav-link {
        display: inline-flex;
        align-items: center;
        border-radius: 0.65rem;
        color: #111;
        font-weight: 600;
        padding: 0.45rem 0.8rem;
        transition: background-color 0.12s ease, color 0.12s ease, transform 0.12s ease;
    }
    .reports-nav .nav-pills .nav-link:not(.active):hover {
        background: #e0f2fe;
        color: #2563eb;
    }
    .reports-nav .nav-pills .nav-link:not(.active):active {
        transform: translateY(1px);
    }
    .reports-nav .nav-pills .nav-link:focus-visible {
        outline: 2px solid #111;
        outline-offset: 2px;
    }
    .reports-nav .nav-pills .nav-link.active {
        background: #111;
        color: #fff;
    }
    .report-section {
        scroll-margin-top: 140px; /* offset for sticky nav + topbar */
    }

    /* Unified hover effects for all sections */
    #report-overview > .row > .col-md-3 > div,
    #report-charts > .row > [class*="col-"] > div,
    #report-transactions .table tbody tr,
    #report-circulation .report-stat-card {
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
                    box-shadow 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
                    background-color 0.2s ease;
    }

    /* Overview stat cards hover */
    #report-overview > .row > .col-md-3:hover > div {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        background-color: #f5f5f5 !important;
    }

    /* Charts section cards hover */
    #report-charts > .row > [class*="col-"]:hover > div {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        background-color: #f5f5f5 !important;
    }

    /* Transactions table hover */
    #report-transactions .table tbody tr:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        background-color: #f5f5f5 !important;
    }

    /* Circulation stat cards hover - match Overview/Charts pattern */
    #report-circulation .report-stat-card {
        display: flex;
        flex-direction: column;
    }
    #report-circulation .col-md-2 a {
        display: block;
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), 
                    box-shadow 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    #report-circulation .col-md-2:hover a {
        transform: translateY(-4px);
    }
    #report-circulation .col-md-2:hover .report-stat-card {
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        background-color: #f5f5f5 !important;
    }
    #report-circulation .col-md-2:focus-visible .report-stat-card {
        outline: 2px solid #111;
        outline-offset: 2px;
    }

    /* Toggle Processed By column */
    body:not(.show-processed-by) .processed-by-col {
        display: none !important;
    }

    .reports-print-btn {
        white-space: nowrap;
    }
</style>
<div class="container pt-2 pb-5 mb-4">

    <div class="reports-nav mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-none d-lg-block">
                <ul class="nav nav-pills gap-2" id="reportsNavPills">
                    <li class="nav-item"><a class="nav-link active" href="#report-overview" data-report-target="overview">Overview</a></li>
                    <li class="nav-item"><a class="nav-link" href="#report-charts" data-report-target="charts">Charts</a></li>
                    <li class="nav-item"><a class="nav-link" href="#report-transactions" data-report-target="transactions">Transactions</a></li>
                    <li class="nav-item"><a class="nav-link" href="#report-circulation" data-report-target="circulation">Circulation</a></li>
                </ul>
            </div>
            <div class="d-lg-none w-100">
                <label class="form-label small text-muted mb-1">Jump to</label>
                <select class="form-select form-select-sm" id="reportsJumpSelect">
                    <option value="#report-overview">Overview</option>
                    <option value="#report-charts">Charts</option>
                    <option value="#report-transactions">Transactions</option>
                    <option value="#report-circulation">Circulation</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex flex-column">
                    <label class="form-label small text-muted mb-2 d-none d-md-block">Show</label>
                    <select class="form-select form-select-sm" id="reportsSectionFilter" style="min-width: 180px;">
                        <option value="all" selected>All Sections</option>
                        <option value="overview">Overview</option>
                        <option value="charts">Charts</option>
                        <option value="transactions">Transactions</option>
                        <option value="circulation">Circulation</option>
                    </select>
                </div>
                <a href="#report-overview" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-self-end">Top</a>
            </div>
        </div>
    </div>

    <div>
        <h1 class="h4 mb-2">Reports & Analytics</h1>
        <p class="text-muted mb-5">Library insights on transactions, circulation, engagement</p>
    </div>

    <div id="print-overview-charts">
    {{-- <div class="reports-nav mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-none d-lg-block">
                <ul class="nav nav-pills gap-2" id="reportsNavPills">
                    <li class="nav-item"><a class="nav-link active" href="#report-overview" data-report-target="overview">Overview</a></li>
                    <li class="nav-item"><a class="nav-link" href="#report-charts" data-report-target="charts">Charts</a></li>
                    <li class="nav-item"><a class="nav-link" href="#report-transactions" data-report-target="transactions">Transactions</a></li>
                    <li class="nav-item"><a class="nav-link" href="#report-circulation" data-report-target="circulation">Circulation</a></li>
                </ul>
            </div>
            <div class="d-lg-none w-100">
                <label class="form-label small text-muted mb-1">Jump to</label>
                <select class="form-select form-select-sm" id="reportsJumpSelect">
                    <option value="#report-overview">Overview</option>
                    <option value="#report-charts">Charts</option>
                    <option value="#report-transactions">Transactions</option>
                    <option value="#report-circulation">Circulation</option>
                </select>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="d-flex flex-column">
                    <label class="form-label small text-muted mb-2 d-none d-md-block">Show</label>
                    <select class="form-select form-select-sm" id="reportsSectionFilter" style="min-width: 180px;">
                        <option value="all" selected>All Sections</option>
                        <option value="overview">Overview</option>
                        <option value="charts">Charts</option>
                        <option value="transactions">Transactions</option>
                        <option value="circulation">Circulation</option>
                    </select>
                </div>
                <a href="#report-overview" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-self-end">Top</a>
            </div>
        </div>
    </div> --}}

    <section id="report-overview" class="report-section">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <h5 class="mb-0">Overview</h5>
        <button type="button" id="printOverviewChartsBtn1" class="btn btn-sm btn-outline-dark reports-print-btn">
            <i class="bi bi-printer me-1"></i>Print Overview + Charts
        </button>
    </div>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Total Transactions</div>
                <div class="h4 mb-0">{{ $totalTransactions ?? 0 }}</div>
                <div class="small text-muted">All time</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Students</div>
                <div class="h4 mb-0">{{ $totalStudents ?? 0 }}</div>
                <div class="small text-muted">Registered</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Teachers</div>
                <div class="h4 mb-0">{{ $totalTeachers ?? 0 }}</div>
                <div class="small text-muted">Registered</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Books in Circulation</div>
                <div class="h4 mb-0">{{ $booksInCirculation ?? 0 }}</div>
                <div class="small text-muted">Currently borrowed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Overdue Items</div>
                <div class="h4 mb-0 text-danger">{{ $overdueItems ?? 0 }}</div>
                <div class="small text-muted">Need attention</div>
            </div>
        </div>
    </div>
    </section>

    <section id="report-charts" class="report-section">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2 mt-4">
        <h5 class="mb-0">Charts</h5>
        {{-- <button type="button" id="printOverviewChartsBtn2" class="btn btn-sm btn-outline-dark reports-print-btn">
            <i class="bi bi-printer me-1"></i>Print Overview + Charts
        </button> --}}
    </div>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="p-3 rounded shadow-sm bg-white h-100">
                <h5 class="mb-3">Popular Books</h5>
                <div style="height:320px;">
                    <canvas id="popularBooksChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="p-3 rounded shadow-sm bg-white h-100">
                <h5 class="mb-3">Books by Category</h5>
                <div style="height:320px;">
                    <canvas id="booksCategoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    </section>

    </div>

    {{-- <div class="row g-3 mt-3">
        <div class="col-lg-12">
            <div class="p-3 rounded shadow-sm bg-white">
                <h5 class="mb-3">Monthly Activity</h5>
                <div style="height:240px;">
                    <canvas id="monthlyActivityChart"></canvas>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Detailed Transactions Section -->
    <section id="report-transactions" class="report-section">
    <div class="row g-3 mt-4">
        <div class="col-lg-12">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="mb-0">All Transactions</h5>
                        <button type="button" id="printTransactionsBtn" class="btn btn-sm btn-outline-dark reports-print-btn">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                    </div>
                    <div class="small text-muted">Total: {{ $totalTransactions }}</div>
                </div>

                <!-- Filter and Sort Controls -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <form id="filterForm" class="d-flex flex-wrap gap-2 align-items-end">
                            <div class="grow" style="min-width: 200px;">
                                <input type="text" id="remarksSearch" name="remarks" class="form-control form-control-sm" placeholder="Search status remarks..." value="{{ $remarksSearch ?? '' }}">
                            </div>

                            <select id="statusFilter" name="status" class="form-select form-select-sm" style="max-width:150px;">
                                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>All Status</option>
                                <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active (Borrowed)</option>
                                <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed (Returned)</option>
                            </select>

                            <select id="sortBy" name="sort" class="form-select form-select-sm" style="max-width:140px;">
                                <option value="borrowed_at" {{ $sortBy === 'borrowed_at' ? 'selected' : '' }}>Sort by Date Borrowed</option>
                                <option value="due_date" {{ $sortBy === 'due_date' ? 'selected' : '' }}>Sort by Due Date</option>
                                <option value="returned_at" {{ $sortBy === 'returned_at' ? 'selected' : '' }}>Sort by Return Date</option>
                                <option value="id" {{ $sortBy === 'id' ? 'selected' : '' }}>Sort by ID</option>
                            </select>

                            <select id="sortOrder" name="order" class="form-select form-select-sm" style="max-width:110px;">
                                <option value="desc" {{ $sortOrder === 'desc' ? 'selected' : '' }}>Newest First</option>
                                <option value="asc" {{ $sortOrder === 'asc' ? 'selected' : '' }}>Oldest First</option>
                            </select>

                            <button type="button" id="applyFilterBtn" class="btn btn-sm btn-outline-secondary">Apply</button>
                            <button type="button" id="clearFilterBtn" class="btn btn-sm btn-outline-secondary">Clear</button>
                            <div class="form-check form-switch ms-auto">
                                <input class="form-check-input" type="checkbox" role="switch" id="toggleProcessedBy">
                                <label class="form-check-label small text-muted" for="toggleProcessedBy">Show admin/staff responsible</label>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Txn ID</th>
                                <th style="width: 140px;">Borrower</th>
                                <th style="width: 180px;">Book Title</th>
                                <th style="width: 130px;">Control No.</th>
                                <th style="width: 100px;">Date Borrowed</th>
                                <th style="width: 100px;">Due Date</th>
                                <th class="processed-by-col" style="width: 170px;">Processed By</th>
                                <th style="width: 85px;">Type</th>
                                <th style="width: 100px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $transaction)
                                @php
                                    // Use the new transaction status from controller enrichment
                                    $statusLabel = $transaction->transaction_status_label ?? 'Unknown';
                                    $statusClass = \App\Models\Borrow::getStatusColor($transaction->transaction_status ?? '');
                                    $lossType = $transaction->transaction_loss_type;
                                    $isLostOrDamaged = $transaction->is_lost_or_damaged ?? false;
                                @endphp
                                <tr>
                                    <td><span class="badge bg-light text-dark">{{ $transaction->id }}</span></td>
                                    <td>
                                        <small title="{{ $transaction->borrower_type }}">{{ $transaction->borrower_name ?: 'Unknown' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $transaction->book->title ?? 'Deleted Book' }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $transaction->getControlNumberRaw() }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $transaction->borrowed_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $transaction->due_date->format('M d, Y') }}</small>
                                    </td>
                                    <td class="processed-by-col">
                                        <small>{{ $transaction->processed_by_display ?? '—' }}</small>
                                    </td>
                                    <td>
                                        @if(is_null($transaction->returned_at))
                                            <span class="badge bg-primary">Borrow</span>
                                        @else
                                            <span class="badge bg-secondary">Return</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                        <!-- Status transition icons and indicators -->
                                        @if($isLostOrDamaged)
                                            @if($lossType === 'damaged')
                                                <i class="bi bi-tools status-indicator damaged" title="Damaged - For Repair"></i>
                                            @elseif($lossType === 'repaired')
                                                <i class="bi bi-check-circle status-indicator repaired" title="Repaired"></i>
                                            @elseif($lossType === 'lost')
                                                <i class="bi bi-exclamation-triangle status-indicator lost" title="Lost"></i>
                                            @elseif($lossType === 'found')
                                                <i class="bi bi-search status-indicator found" title="Found"></i>
                                            @endif
                                        @elseif($transaction->return_status === 'late_return')
                                            <i class="bi bi-clock status-indicator" title="Late Return" style="color: #fd7e14;"></i>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-3">
                                        <small>No transactions found.</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Custom Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 gap-2">
                    <div class="text-muted small">
                        Showing {{ $transactions->firstItem() ?? 0 }} to {{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }} results
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            {{-- Previous Page Link --}}
                            @if ($transactions->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link" aria-label="Previous">
                                        <span aria-hidden="true">&lsaquo;</span> Previous
                                    </span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link" href="{{ $transactions->appends(request()->query())->previousPageUrl() }}" rel="prev" aria-label="Previous">
                                        <span aria-hidden="true">&lsaquo;</span> Previous
                                    </a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @foreach ($transactions->appends(request()->query())->getUrlRange(1, $transactions->lastPage()) as $page => $url)
                                @if ($page == $transactions->currentPage())
                                    <li class="page-item active" aria-current="page">
                                        <span class="page-link">
                                            {{ $page }}
                                        </span>
                                    </li>
                                @else
                                    <li class="page-item">
                                        <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                    </li>
                                @endif
                            @endforeach

                            {{-- Next Page Link --}}
                            @if ($transactions->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link" href="{{ $transactions->appends(request()->query())->nextPageUrl() }}" rel="next" aria-label="Next">
                                        Next <span aria-hidden="true">&rsaquo;</span>
                                    </a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link" aria-label="Next">
                                        Next <span aria-hidden="true">&rsaquo;</span>
                                    </span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    </section>

    <!-- Books Circulation Report Section -->
    <section id="report-circulation" class="report-section">
    <div class="row g-3 mt-4">
        <div class="col-lg-12">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-4">
                    <h5 class="mb-0">Books Circulation Report</h5>
                    <button type="button" id="printCirculationBtn" class="btn btn-sm btn-outline-dark reports-print-btn">
                        <i class="bi bi-printer me-1"></i>Print
                    </button>
                </div>

                <!-- Books Circulation Statistics Cards -->
                <div class="row g-3">
                    <div class="col-md-2">
                        <a href="{{ route('books.catalog') }}" class="text-decoration-none d-block">
                            <div class="p-3 rounded border-start border-4 border-primary bg-light report-stat-card">
                                <div class="small text-muted">Total Books</div>
                                <div class="h4 mb-0 text-primary">{{ $totalBooks ?? 0 }}</div>
                                <div class="small text-muted">In system</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('books.catalog') }}" class="text-decoration-none d-block">
                            <div class="p-3 rounded border-start border-4 border-success bg-light report-stat-card">
                                <div class="small text-muted">Available</div>
                                <div class="h4 mb-0 text-success">{{ $availableBooks ?? 0 }}</div>
                                <div class="small text-muted">Ready to borrow</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('borrow.return.index') }}" class="text-decoration-none d-block">
                            <div class="p-3 rounded border-start border-4 border-info bg-light report-stat-card">
                                <div class="small text-muted">Currently Borrowed</div>
                                <div class="h4 mb-0 text-info">{{ $borrowedBooks ?? 0 }}</div>
                                <div class="small text-muted">In circulation</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('books.lost-damage') }}" class="text-decoration-none d-block">
                            <div class="p-3 rounded border-start border-4 border-warning bg-light report-stat-card">
                                <div class="small text-muted">Damaged</div>
                                <div class="h4 mb-0 text-warning">{{ $damagedBooks ?? 0 }}</div>
                                <div class="small text-muted">Needs repair</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('books.lost-damage') }}" class="text-decoration-none d-block">
                            <div class="p-3 rounded border-start border-4 border-danger bg-light report-stat-card">
                                <div class="small text-muted">Lost</div>
                                <div class="h4 mb-0 text-danger">{{ $lostBooks ?? 0 }}</div>
                                <div class="small text-muted">Not recovered</div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('books.lost-damage') }}" class="text-decoration-none d-block">
                            <div class="p-3 rounded border-start border-4 border-secondary bg-light report-stat-card">
                                <div class="small text-muted">Repaired</div>
                                <div class="h4 mb-0 text-secondary">{{ $repairedBooks ?? 0 }}</div>
                                <div class="small text-muted">Back in stock</div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Circulation Summary -->
                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded bg-light">
                            <h6 class="text-muted mb-3">Circulation Status</h6>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small">Availability Rate</span>
                                    <span class="small font-weight-bold">
                                        @php
                                            $availabilityRate = ($totalBooks ?? 0) > 0 ? round((($availableBooks ?? 0) / ($totalBooks ?? 0)) * 100) : 0;
                                        @endphp
                                        {{ $availabilityRate }}%
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: {{ $availabilityRate }}%"></div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small">Borrow Rate</span>
                                    <span class="small font-weight-bold">
                                        @php
                                            $borrowRate = ($totalBooks ?? 0) > 0 ? round((($borrowedBooks ?? 0) / ($totalBooks ?? 0)) * 100) : 0;
                                        @endphp
                                        {{ $borrowRate }}%
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: {{ $borrowRate }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small">Problem Items</span>
                                    <span class="small font-weight-bold">
                                        @php
                                            $problemRate = ($totalBooks ?? 0) > 0 ? round(((($damagedBooks ?? 0) + ($lostBooks ?? 0)) / ($totalBooks ?? 0)) * 100) : 0;
                                        @endphp
                                        {{ $problemRate }}%
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" style="width: {{ $problemRate }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded bg-light">
                            <h6 class="text-muted mb-3">Quick Stats</h6>
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <small class="text-muted">Books in Good Condition</small>
                                    <div class="h5 mb-0">
                                        @php
                                            $goodCondition = ($availableBooks ?? 0) + ($borrowedBooks ?? 0);
                                        @endphp
                                        {{ $goodCondition }}
                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-muted">Problem Books</small>
                                    <div class="h5 mb-0 text-danger">
                                        @php
                                            $problemBooks = ($damagedBooks ?? 0) + ($lostBooks ?? 0);
                                        @endphp
                                        {{ $problemBooks }}
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Recovery Rate</small>
                                    <div class="h5 mb-0 text-success">
                                        @php
                                            $recoveryRate = (($damagedBooks ?? 0) + ($lostBooks ?? 0)) > 0 ? round((($repairedBooks ?? 0) / (($damagedBooks ?? 0) + ($lostBooks ?? 0))) * 100) : 0;
                                        @endphp
                                        {{ $recoveryRate }}%
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Turnover Status</small>
                                    <div class="h5 mb-0">
                                        @if($borrowRate > 60)
                                            <span class="badge bg-info">High</span>
                                        @elseif($borrowRate > 30)
                                            <span class="badge bg-warning">Medium</span>
                                        @else
                                            <span class="badge bg-success">Low</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </section>
</div>

<script>
    (function () {
        // Mobile jump select
        const jump = document.getElementById('reportsJumpSelect');
        if (jump) {
            jump.addEventListener('change', function () {
                const v = jump.value;
                if (v) location.hash = v;
            });
        }

        // Section show/hide filter
        const filter = document.getElementById('reportsSectionFilter');
        const sections = [
            { key: 'overview', el: document.getElementById('report-overview') },
            { key: 'charts', el: document.getElementById('report-charts') },
            { key: 'transactions', el: document.getElementById('report-transactions') },
            { key: 'circulation', el: document.getElementById('report-circulation') },
        ].filter(s => s.el);

        function applyFilter(value) {
            sections.forEach(s => {
                if (value === 'all' || s.key === value) {
                    s.el.classList.remove('d-none');
                } else {
                    s.el.classList.add('d-none');
                }
            });
        }

        if (filter) {
            filter.addEventListener('change', function () {
                applyFilter(filter.value);
            });
        }

        // Keep nav pills active based on scroll position (best-effort)
        const pills = document.querySelectorAll('#reportsNavPills a[data-report-target]');
        function setActive(targetKey) {
            pills.forEach(a => {
                a.classList.toggle('active', a.getAttribute('data-report-target') === targetKey);
            });
        }
        // Set active on click
        pills.forEach(pill => {
            pill.addEventListener('click', function() {
                setActive(this.getAttribute('data-report-target'));
            });
        });
        function onScroll() {
            // Only when all sections are visible; otherwise keep selection as-is.
            if (filter && filter.value !== 'all') return;
            const y = window.scrollY + 160;
            let current = 'overview';
            for (const s of sections) {
                const top = s.el.offsetTop;
                if (y >= top) current = s.key;
            }
            setActive(current);
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        // Initial state
        applyFilter('all');
    })();
</script>
@php
    // Monochrome defaults for a clean black & white dashboard look
    $popularLabelsSafe = $popularLabels ?? [];
    $popularDataSafe = $popularData ?? [];
    $popularColorsSafe = $popularColors ?? array_fill(0, max(1, count($popularDataSafe)), '#000000');

    $categoryLabelsSafe = $categoryLabels ?? [];
    $categoryDataSafe = $categoryData ?? [];
    // Black theme for horizontal bar chart
    $categoryColorsSafe = array_fill(0, max(1, count($categoryDataSafe)), '#000000');
    $categoryBorderColorsSafe = array_fill(0, max(1, count($categoryDataSafe)), '#111111');

    $monthlyLabelsSafe = $monthlyLabels ?? [];
    $monthlyDataSafe = $monthlyData ?? [];
@endphp

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Popular Books (bar)
    const popularCtx = document.getElementById('popularBooksChart')?.getContext('2d');
    if(popularCtx){
        new Chart(popularCtx, {
            type: 'bar',
            data: {
                labels: @json($popularLabelsSafe),
                datasets: [{
                    label: 'Borrow Count',
                    data: @json($popularDataSafe),
                    backgroundColor: @json($popularColorsSafe),
                    borderColor: @json(array_map(fn($c) => '#111111', $popularColorsSafe)),
                    borderRadius: 8,
                    maxBarThickness: 48,
                }]
            },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
        });
    }

    // Books by Category (horizontal bar)
    const catCtx = document.getElementById('booksCategoryChart')?.getContext('2d');
    if(catCtx){
        new Chart(catCtx, {
            type: 'bar',
            data: {
                labels: @json($categoryLabelsSafe),
                datasets: [{
                    label: 'Count',
                    data: @json($categoryDataSafe),
                    backgroundColor: @json($categoryColorsSafe),
                    borderColor: @json($categoryBorderColorsSafe),
                    borderRadius: 6
                }]
            },
            options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
        });
    }

    // // Monthly Activity (line)
    // const monthlyCtx = document.getElementById('monthlyActivityChart')?.getContext('2d');
    // if(monthlyCtx){
    //     new Chart(monthlyCtx, {
    //         type: 'line',
    //         data: {
    //             labels: @json($monthlyLabelsSafe),
    //             datasets: [{ label:'Activity', data:@json($monthlyDataSafe), borderColor:'#000000', backgroundColor:'rgba(0,0,0,0.06)', tension:0.3, fill:true }]
    //         },
    //         options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
    //     });
    // }
</script>

<script>
    // Processed By column toggle (persisted)
    (function () {
        const key = 'show_processed_by_column';
        const show = window.localStorage?.getItem(key) === '1';
        document.body.classList.toggle('show-processed-by', show);

        const toggle = document.getElementById('toggleProcessedBy');
        if (toggle) {
            toggle.checked = show;
            toggle.addEventListener('change', function () {
                const val = !!this.checked;
                document.body.classList.toggle('show-processed-by', val);
                try { window.localStorage?.setItem(key, val ? '1' : '0'); } catch (e) {}
            });
        }
    })();

    // AJAX-based filtering without page refresh
    function applyFilters() {
        const status = document.getElementById('statusFilter').value;
        const sort = document.getElementById('sortBy').value;
        const order = document.getElementById('sortOrder').value;
        const remarks = document.getElementById('remarksSearch').value;
        
        // Build URL with query parameters
        const url = new URL(window.location);
        url.searchParams.set('status', status);
        url.searchParams.set('sort', sort);
        url.searchParams.set('order', order);
        if (remarks) {
            url.searchParams.set('remarks', remarks);
        } else {
            url.searchParams.delete('remarks');
        }
        
        // Fetch filtered data
        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Parse the response HTML
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            // Extract and update the transactions table
            const newTable = doc.querySelector('table tbody');
            const newPagination = doc.querySelector('.d-flex.justify-content-between.align-items-center.mt-3.gap-2');
            
            if (newTable) {
                document.querySelector('table tbody').innerHTML = newTable.innerHTML;
            }
            
            if (newPagination) {
                document.querySelector('.d-flex.justify-content-between.align-items-center.mt-3.gap-2').innerHTML = newPagination.innerHTML;
                
                // Re-attach pagination click handlers
                attachPaginationHandlers();
            }
            
            // Update URL without page refresh
            window.history.replaceState(null, '', url.toString());
        })
        .catch(error => console.error('Filter error:', error));
    }

    document.getElementById('applyFilterBtn').addEventListener('click', function(e) {
        e.preventDefault();
        applyFilters();
    });

    // Clear filters
    document.getElementById('clearFilterBtn').addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('statusFilter').value = 'all';
        document.getElementById('sortBy').value = 'borrowed_at';
        document.getElementById('sortOrder').value = 'desc';
        document.getElementById('remarksSearch').value = '';
        
        // Build clean URL
        const url = new URL(window.location);
        url.searchParams.delete('status');
        url.searchParams.delete('sort');
        url.searchParams.delete('order');
        url.searchParams.delete('remarks');
        
        window.location.href = url.toString();
    });

    // Allow Enter key to trigger search in remarks field
    document.getElementById('remarksSearch').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            applyFilters();
        }
    });

    // Handle pagination links without page refresh
    function attachPaginationHandlers() {
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const url = this.href;
                
                // Fetch paginated data
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    const newTable = doc.querySelector('table tbody');
                    const newPagination = doc.querySelector('.d-flex.justify-content-between.align-items-center.mt-3.gap-2');
                    
                    if (newTable) {
                        document.querySelector('table tbody').innerHTML = newTable.innerHTML;
                    }
                    
                    if (newPagination) {
                        document.querySelector('.d-flex.justify-content-between.align-items-center.mt-3.gap-2').innerHTML = newPagination.innerHTML;
                        attachPaginationHandlers();
                    }
                    
                    window.history.replaceState(null, '', url);
                    
                    // Scroll to table
                    document.querySelector('table').scrollIntoView({ behavior: 'smooth' });
                })
                .catch(error => console.error('Pagination error:', error));
            });
        });
    }

    // Initialize pagination handlers on page load
    attachPaginationHandlers();

    function escapeHtml(text) {
        return String(text ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function cloneNodeForPrint(node) {
        const clone = node.cloneNode(true);

        const originalCanvases = node.querySelectorAll('canvas');
        const clonedCanvases = clone.querySelectorAll('canvas');

        originalCanvases.forEach((canvas, idx) => {
            const target = clonedCanvases[idx];
            if (!target) return;
            try {
                const img = document.createElement('img');
                img.src = canvas.toDataURL('image/png');
                img.alt = 'Chart';
                img.style.maxWidth = '100%';
                img.style.height = 'auto';
                target.replaceWith(img);
            } catch (e) {
                // If canvas is tainted or fails, keep it as-is.
            }
        });

        // Remove interactive controls inside print content
        clone.querySelectorAll('button, .btn, input, select, textarea').forEach(el => el.remove());

        return clone;
    }

    function openPrintWindow({ title, html, bodyClass = '' }) {
        const w = window.open('', '_blank');
        if (!w) {
            alert('Popup blocked. Please allow popups for this site to print reports.');
            return;
        }

        const now = new Date();
        const stamped = now.toLocaleString();

        w.document.open();
        w.document.write(`<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>${escapeHtml(title)}</title>
  <style>
    :root { --border: #d1d5db; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111; padding: 18px; }
    h1 { font-size: 18px; margin: 0 0 6px; }
    .meta { font-size: 12px; color: #555; margin: 0 0 12px; }
    .filters { font-size: 12px; margin: 0 0 12px; padding: 10px; border: 1px solid var(--border); border-radius: 8px; }
    .filters b { display: inline-block; min-width: 140px; }

    /* Lightweight layout helpers (Bootstrap-like) for print */
    a { color: inherit; text-decoration: none; }
    .d-flex { display: flex !important; }
    .justify-content-between { justify-content: space-between !important; }
    .align-items-center { align-items: center !important; }
    .gap-2 { gap: 8px !important; }
    .gap-3 { gap: 12px !important; }
    .mb-0 { margin-bottom: 0 !important; }
    .mb-1 { margin-bottom: 4px !important; }
    .mb-2 { margin-bottom: 8px !important; }
    .mb-3 { margin-bottom: 12px !important; }
    .mb-4 { margin-bottom: 16px !important; }
    .mt-3 { margin-top: 12px !important; }
    .mt-4 { margin-top: 16px !important; }
    .p-3 { padding: 12px !important; }
    .rounded { border-radius: 10px !important; }
    .shadow-sm { box-shadow: 0 1px 2px rgba(0,0,0,.08) !important; }
    .bg-white { background: #fff !important; }
    .bg-light { background: #f3f4f6 !important; }
    .text-muted { color: #6b7280 !important; }
    .text-primary { color: #1d4ed8 !important; }
    .text-success { color: #15803d !important; }
    .text-info { color: #0369a1 !important; }
    .text-warning { color: #b45309 !important; }
    .text-danger { color: #b91c1c !important; }
    .text-secondary { color: #4b5563 !important; }

    .row { display: flex; flex-wrap: wrap; }
    .g-3 { gap: 12px; }
    /* Default to full width; override via "col-*" rules below */
    [class*="col-"] { flex: 0 0 auto; width: 100%; }
    .col-6 { width: calc(50% - 6px); }
    .col-md-2 { width: calc(16.6667% - 10px); }
    .col-md-6 { width: calc(50% - 6px); }
    .col-lg-12 { width: 100%; }
    @media (max-width: 900px) {
      .col-md-2 { width: calc(33.3333% - 8px); }
    }

    .border-start { border-left: 4px solid var(--border) !important; }
    .border-4 { border-left-width: 4px !important; }
    .border-primary { border-left-color: #1d4ed8 !important; }
    .border-success { border-left-color: #16a34a !important; }
    .border-info { border-left-color: #0284c7 !important; }
    .border-warning { border-left-color: #f59e0b !important; }
    .border-danger { border-left-color: #ef4444 !important; }
    .border-secondary { border-left-color: #6b7280 !important; }

    .h4 { font-size: 22px; margin: 4px 0; font-weight: 700; }
    .h5 { font-size: 16px; margin: 0; font-weight: 700; }
    .h6 { font-size: 13px; margin: 0; font-weight: 700; }
    small, .small { font-size: 12px; }

    .progress { height: 8px; background: #e5e7eb; border-radius: 999px; overflow: hidden; }
    .progress-bar { height: 100%; background: #111; }
    .progress-bar.bg-success { background: #16a34a; }
    .progress-bar.bg-info { background: #0284c7; }
    .progress-bar.bg-danger { background: #ef4444; }

    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid var(--border); padding: 6px 8px; vertical-align: top; }
    th { background: #f3f4f6; text-align: left; }
    .badge { border: 1px solid var(--border); padding: 2px 6px; border-radius: 999px; font-size: 12px; display: inline-block; }
    .processed-by-col { display: table-cell; }
    body.hide-processed-by .processed-by-col { display: none !important; }
    @media print {
      @page { margin: 12mm; }
      a[href]:after { content: ""; }
    }
  </style>
</head>
<body class="${escapeHtml(bodyClass)}">
  <h1>${escapeHtml(title)}</h1>
  <div class="meta">Generated: ${escapeHtml(stamped)}</div>
  ${html}
</body>
</html>`);
        w.document.close();

        // Print after render (best-effort)
        const triggerPrint = () => {
            try { w.focus(); } catch (e) {}
            try { w.print(); } catch (e) {}
        };
        try {
            w.onload = () => w.setTimeout(triggerPrint, 50);
        } catch (e) {}
        w.setTimeout(triggerPrint, 400);
    }

    function printOverviewAndCharts() {
        const wrap = document.getElementById('print-overview-charts');
        if (!wrap) return;

        const content = cloneNodeForPrint(wrap);
        openPrintWindow({
            title: 'Reports - Overview & Charts',
            html: content.innerHTML,
        });
    }

    function printTransactions() {
        const section = document.getElementById('report-transactions');
        const table = section?.querySelector('table');
        if (!section || !table) return;

        const statusEl = document.getElementById('statusFilter');
        const sortEl = document.getElementById('sortBy');
        const orderEl = document.getElementById('sortOrder');
        const remarksEl = document.getElementById('remarksSearch');
        const processedToggle = document.getElementById('toggleProcessedBy');

        const statusText = statusEl?.options?.[statusEl.selectedIndex]?.text ?? '';
        const sortText = sortEl?.options?.[sortEl.selectedIndex]?.text ?? '';
        const orderText = orderEl?.options?.[orderEl.selectedIndex]?.text ?? '';
        const remarksText = remarksEl?.value ?? '';
        const showProcessedBy = !!processedToggle?.checked;

        const tableClone = cloneNodeForPrint(table);

        openPrintWindow({
            title: 'Reports - Transactions',
            bodyClass: showProcessedBy ? '' : 'hide-processed-by',
            html: `
              <div class="filters">
                <div><b>Status</b> ${escapeHtml(statusText || 'All')}</div>
                <div><b>Sort</b> ${escapeHtml(sortText || '—')} (${escapeHtml(orderText || '')})</div>
                <div><b>Remarks search</b> ${escapeHtml(remarksText || '—')}</div>
                <div><b>Show processed by</b> ${showProcessedBy ? 'Yes' : 'No'}</div>
                <div><b>Source</b> ${escapeHtml(window.location.href)}</div>
              </div>
              ${tableClone.outerHTML}
            `,
        });
    }

    function printCirculation() {
        const section = document.getElementById('report-circulation');
        if (!section) return;

        const content = cloneNodeForPrint(section);
        openPrintWindow({
            title: 'Reports - Circulation',
            html: content.innerHTML,
        });
    }

    document.getElementById('printOverviewChartsBtn1')?.addEventListener('click', function (e) {
        e.preventDefault();
        printOverviewAndCharts();
    });
    document.getElementById('printOverviewChartsBtn2')?.addEventListener('click', function (e) {
        e.preventDefault();
        printOverviewAndCharts();
    });
    document.getElementById('printTransactionsBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        printTransactions();
    });
    document.getElementById('printCirculationBtn')?.addEventListener('click', function (e) {
        e.preventDefault();
        printCirculation();
    });
</script>

@endsection
