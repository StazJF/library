@extends('layouts.app')

@section('content')
<style>
    /* Status badge styling for new statuses */
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
        transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), 
                    box-shadow 0.2s cubic-bezier(0.34, 1.56, 0.64, 1),
                    background-color 0.2s ease;
    }
    #report-circulation .col-md-2:hover .report-stat-card {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
        background-color: #f5f5f5 !important;
    }
    #report-circulation .col-md-2:focus-visible .report-stat-card {
        outline: 2px solid #111;
        outline-offset: 2px;
    }
</style>
<div class="container py-4">
    <h1 class="h4 mb-5">Reports & Analytics</h1>

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

    <section id="report-overview" class="report-section">
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
                    <h5 class="mb-0">All Transactions</h5>
                    <div class="small text-muted">Total: {{ $totalTransactions }}</div>
                </div>

                <!-- Filter and Sort Controls -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form id="filterForm" class="d-flex gap-2">
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
                                <th style="width: 100px;">Date Borrowed</th>
                                <th style="width: 100px;">Due Date</th>
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
                                        <small>{{ $transaction->borrowed_at->format('M d, Y') }}</small>
                                    </td>
                                    <td>
                                        <small>{{ $transaction->due_date->format('M d, Y') }}</small>
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
                                    <td colspan="7" class="text-center text-muted py-3">
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
                            @foreach ($transactions->getUrlRange(1, $transactions->lastPage()) as $page => $url)
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
                <h5 class="mb-4">Books Circulation Report</h5>

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
    // AJAX-based filtering without page refresh
    document.getElementById('applyFilterBtn').addEventListener('click', function(e) {
        e.preventDefault();
        
        const status = document.getElementById('statusFilter').value;
        const sort = document.getElementById('sortBy').value;
        const order = document.getElementById('sortOrder').value;
        
        // Build URL with query parameters
        const url = new URL(window.location);
        url.searchParams.set('status', status);
        url.searchParams.set('sort', sort);
        url.searchParams.set('order', order);
        
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
</script>

@endsection
