<?php $__env->startSection('content'); ?>
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
</style>
<div class="container py-4">
    <h1 class="h4 mb-5">Reports & Analytics</h1>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Total Transactions</div>
                <div class="h4 mb-0"><?php echo e($totalTransactions ?? 0); ?></div>
                <div class="small text-muted">All time</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Students</div>
                <div class="h4 mb-0"><?php echo e($totalStudents ?? 0); ?></div>
                <div class="small text-muted">Registered</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Teachers</div>
                <div class="h4 mb-0"><?php echo e($totalTeachers ?? 0); ?></div>
                <div class="small text-muted">Registered</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Books in Circulation</div>
                <div class="h4 mb-0"><?php echo e($booksInCirculation ?? 0); ?></div>
                <div class="small text-muted">Currently borrowed</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="small text-muted">Overdue Items</div>
                <div class="h4 mb-0 text-danger"><?php echo e($overdueItems ?? 0); ?></div>
                <div class="small text-muted">Need attention</div>
            </div>
        </div>
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

    

    <!-- Detailed Transactions Section -->
    <div class="row g-3 mt-4">
        <div class="col-lg-12">
            <div class="p-3 rounded shadow-sm bg-white">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">All Transactions</h5>
                    <div class="small text-muted">Total: <?php echo e($totalTransactions); ?></div>
                </div>

                <!-- Filter and Sort Controls -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <form id="filterForm" class="d-flex gap-2">
                            <select id="statusFilter" name="status" class="form-select form-select-sm" style="max-width:150px;">
                                <option value="all" <?php echo e($statusFilter === 'all' ? 'selected' : ''); ?>>All Status</option>
                                <option value="active" <?php echo e($statusFilter === 'active' ? 'selected' : ''); ?>>Active (Borrowed)</option>
                                <option value="completed" <?php echo e($statusFilter === 'completed' ? 'selected' : ''); ?>>Completed (Returned)</option>
                            </select>

                            <select id="sortBy" name="sort" class="form-select form-select-sm" style="max-width:140px;">
                                <option value="borrowed_at" <?php echo e($sortBy === 'borrowed_at' ? 'selected' : ''); ?>>Sort by Date Borrowed</option>
                                <option value="due_date" <?php echo e($sortBy === 'due_date' ? 'selected' : ''); ?>>Sort by Due Date</option>
                                <option value="returned_at" <?php echo e($sortBy === 'returned_at' ? 'selected' : ''); ?>>Sort by Return Date</option>
                                <option value="id" <?php echo e($sortBy === 'id' ? 'selected' : ''); ?>>Sort by ID</option>
                            </select>

                            <select id="sortOrder" name="order" class="form-select form-select-sm" style="max-width:110px;">
                                <option value="desc" <?php echo e($sortOrder === 'desc' ? 'selected' : ''); ?>>Newest First</option>
                                <option value="asc" <?php echo e($sortOrder === 'asc' ? 'selected' : ''); ?>>Oldest First</option>
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
                            <?php $__empty_1 = true; $__currentLoopData = $transactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $transaction): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    // Use the new transaction status from controller enrichment
                                    $statusLabel = $transaction->transaction_status_label ?? 'Unknown';
                                    $statusClass = \App\Models\Borrow::getStatusColor($transaction->transaction_status ?? '');
                                    $lossType = $transaction->transaction_loss_type;
                                    $isLostOrDamaged = $transaction->is_lost_or_damaged ?? false;
                                ?>
                                <tr>
                                    <td><span class="badge bg-light text-dark"><?php echo e($transaction->id); ?></span></td>
                                    <td>
                                        <small title="<?php echo e($transaction->borrower_type); ?>"><?php echo e($transaction->borrower_name ?: 'Unknown'); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo e($transaction->book->title ?? 'Deleted Book'); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo e($transaction->borrowed_at->format('M d, Y')); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo e($transaction->due_date->format('M d, Y')); ?></small>
                                    </td>
                                    <td>
                                        <?php if(is_null($transaction->returned_at)): ?>
                                            <span class="badge bg-primary">Borrow</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Return</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo e($statusClass); ?>"><?php echo e($statusLabel); ?></span>
                                        <!-- Status transition icons and indicators -->
                                        <?php if($isLostOrDamaged): ?>
                                            <?php if($lossType === 'damaged'): ?>
                                                <i class="bi bi-tools status-indicator damaged" title="Damaged - For Repair"></i>
                                            <?php elseif($lossType === 'repaired'): ?>
                                                <i class="bi bi-check-circle status-indicator repaired" title="Repaired"></i>
                                            <?php elseif($lossType === 'lost'): ?>
                                                <i class="bi bi-exclamation-triangle status-indicator lost" title="Lost"></i>
                                            <?php elseif($lossType === 'found'): ?>
                                                <i class="bi bi-search status-indicator found" title="Found"></i>
                                            <?php endif; ?>
                                        <?php elseif($transaction->return_status === 'late_return'): ?>
                                            <i class="bi bi-clock status-indicator" title="Late Return" style="color: #fd7e14;"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        <small>No transactions found.</small>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Custom Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3 gap-2">
                    <div class="text-muted small">
                        Showing <?php echo e($transactions->firstItem() ?? 0); ?> to <?php echo e($transactions->lastItem() ?? 0); ?> of <?php echo e($transactions->total()); ?> results
                    </div>
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm mb-0">
                            
                            <?php if($transactions->onFirstPage()): ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-label="Previous">
                                        <span aria-hidden="true">&lsaquo;</span> Previous
                                    </span>
                                </li>
                            <?php else: ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo e($transactions->appends(request()->query())->previousPageUrl()); ?>" rel="prev" aria-label="Previous">
                                        <span aria-hidden="true">&lsaquo;</span> Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            
                            <?php $__currentLoopData = $transactions->getUrlRange(1, $transactions->lastPage()); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $page => $url): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($page == $transactions->currentPage()): ?>
                                    <li class="page-item active" aria-current="page">
                                        <span class="page-link">
                                            <?php echo e($page); ?>

                                        </span>
                                    </li>
                                <?php else: ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?php echo e($url); ?>"><?php echo e($page); ?></a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                            
                            <?php if($transactions->hasMorePages()): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?php echo e($transactions->appends(request()->query())->nextPageUrl()); ?>" rel="next" aria-label="Next">
                                        Next <span aria-hidden="true">&rsaquo;</span>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="page-item disabled">
                                    <span class="page-link" aria-label="Next">
                                        Next <span aria-hidden="true">&rsaquo;</span>
                                    </span>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Books Circulation Report Section -->
    <div class="row g-3 mt-4">
        <div class="col-lg-12">
            <div class="p-3 rounded shadow-sm bg-white">
                <h5 class="mb-4">Books Circulation Report</h5>

                <!-- Books Circulation Statistics Cards -->
                <div class="row g-3">
                    <div class="col-md-2">
                        <div class="p-3 rounded border-start border-4 border-primary bg-light">
                            <div class="small text-muted">Total Books</div>
                            <div class="h4 mb-0 text-primary"><?php echo e($totalBooks ?? 0); ?></div>
                            <div class="small text-muted">In system</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 rounded border-start border-4 border-success bg-light">
                            <div class="small text-muted">Available</div>
                            <div class="h4 mb-0 text-success"><?php echo e($availableBooks ?? 0); ?></div>
                            <div class="small text-muted">Ready to borrow</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 rounded border-start border-4 border-info bg-light">
                            <div class="small text-muted">Currently Borrowed</div>
                            <div class="h4 mb-0 text-info"><?php echo e($borrowedBooks ?? 0); ?></div>
                            <div class="small text-muted">In circulation</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 rounded border-start border-4 border-warning bg-light">
                            <div class="small text-muted">Damaged</div>
                            <div class="h4 mb-0 text-warning"><?php echo e($damagedBooks ?? 0); ?></div>
                            <div class="small text-muted">Needs repair</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 rounded border-start border-4 border-danger bg-light">
                            <div class="small text-muted">Lost</div>
                            <div class="h4 mb-0 text-danger"><?php echo e($lostBooks ?? 0); ?></div>
                            <div class="small text-muted">Not recovered</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-3 rounded border-start border-4 border-secondary bg-light">
                            <div class="small text-muted">Repaired</div>
                            <div class="h4 mb-0 text-secondary"><?php echo e($repairedBooks ?? 0); ?></div>
                            <div class="small text-muted">Back in stock</div>
                        </div>
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
                                        <?php
                                            $availabilityRate = ($totalBooks ?? 0) > 0 ? round((($availableBooks ?? 0) / ($totalBooks ?? 0)) * 100) : 0;
                                        ?>
                                        <?php echo e($availabilityRate); ?>%
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo e($availabilityRate); ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-2">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small">Borrow Rate</span>
                                    <span class="small font-weight-bold">
                                        <?php
                                            $borrowRate = ($totalBooks ?? 0) > 0 ? round((($borrowedBooks ?? 0) / ($totalBooks ?? 0)) * 100) : 0;
                                        ?>
                                        <?php echo e($borrowRate); ?>%
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" style="width: <?php echo e($borrowRate); ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small">Problem Items</span>
                                    <span class="small font-weight-bold">
                                        <?php
                                            $problemRate = ($totalBooks ?? 0) > 0 ? round(((($damagedBooks ?? 0) + ($lostBooks ?? 0)) / ($totalBooks ?? 0)) * 100) : 0;
                                        ?>
                                        <?php echo e($problemRate); ?>%
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-danger" style="width: <?php echo e($problemRate); ?>%"></div>
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
                                        <?php
                                            $goodCondition = ($availableBooks ?? 0) + ($borrowedBooks ?? 0);
                                        ?>
                                        <?php echo e($goodCondition); ?>

                                    </div>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-muted">Problem Books</small>
                                    <div class="h5 mb-0 text-danger">
                                        <?php
                                            $problemBooks = ($damagedBooks ?? 0) + ($lostBooks ?? 0);
                                        ?>
                                        <?php echo e($problemBooks); ?>

                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Recovery Rate</small>
                                    <div class="h5 mb-0 text-success">
                                        <?php
                                            $recoveryRate = (($damagedBooks ?? 0) + ($lostBooks ?? 0)) > 0 ? round((($repairedBooks ?? 0) / (($damagedBooks ?? 0) + ($lostBooks ?? 0))) * 100) : 0;
                                        ?>
                                        <?php echo e($recoveryRate); ?>%
                                    </div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Turnover Status</small>
                                    <div class="h5 mb-0">
                                        <?php if($borrowRate > 60): ?>
                                            <span class="badge bg-info">High</span>
                                        <?php elseif($borrowRate > 30): ?>
                                            <span class="badge bg-warning">Medium</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Low</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
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
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Popular Books (bar)
    const popularCtx = document.getElementById('popularBooksChart')?.getContext('2d');
    if(popularCtx){
        new Chart(popularCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($popularLabelsSafe, 15, 512) ?>,
                datasets: [{
                    label: 'Borrow Count',
                    data: <?php echo json_encode($popularDataSafe, 15, 512) ?>,
                    backgroundColor: <?php echo json_encode($popularColorsSafe, 15, 512) ?>,
                    borderColor: <?php echo json_encode(array_map(fn($c) => '#111111', $popularColorsSafe), 512) ?>,
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
                labels: <?php echo json_encode($categoryLabelsSafe, 15, 512) ?>,
                datasets: [{
                    label: 'Count',
                    data: <?php echo json_encode($categoryDataSafe, 15, 512) ?>,
                    backgroundColor: <?php echo json_encode($categoryColorsSafe, 15, 512) ?>,
                    borderColor: <?php echo json_encode($categoryBorderColorsSafe, 15, 512) ?>,
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
    //             labels: <?php echo json_encode($monthlyLabelsSafe, 15, 512) ?>,
    //             datasets: [{ label:'Activity', data:<?php echo json_encode($monthlyDataSafe, 15, 512) ?>, borderColor:'#000000', backgroundColor:'rgba(0,0,0,0.06)', tension:0.3, fill:true }]
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

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jimmu\Herd\library\resources\views/reports.blade.php ENDPATH**/ ?>