<?php $__env->startSection('content'); ?>
<div class="container-fluid">
<?php ($displayTimezone = config('app.display_timezone', 'Asia/Manila')); ?>

    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold">Lost & Damaged</h3>
            <p class="text-muted mb-0">Track items marked lost or damaged.</p>
        </div>

        <a href="<?php echo e(route('books.lost-damage')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-clockwise"></i> Refresh
        </a>
    </div>


    
    <div class="row mb-4">

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Lost</p>
                    <h4 class="fw-bold"><?php echo e($lostCount ?? 0); ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Damaged</p>
                    <h4 class="fw-bold"><?php echo e($damagedCount ?? 0); ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted mb-1">Total</p>
                    <h4 class="fw-bold"><?php echo e($totalCount ?? 0); ?></h4>
                </div>
            </div>
        </div>

    </div>


    
    <div class="card shadow-sm mb-4">

        <div class="card-body">

            <h5 class="fw-bold">Active lost & damaged</h5>
            <p class="text-muted small">
                Items currently marked lost or damaged. Actions live here; search and filters apply to both tables.
            </p>

            
            <div class="mb-3">
                <form method="GET" action="<?php echo e(route('books.lost-damage')); ?>" id="searchForm">
                    <div class="row g-2 mb-3">
                        <div class="col-md-2">
                            <input type="text" name="ctrl_number" class="form-control form-control-sm"
                                   placeholder="Search Ctrl#..."
                                   value="<?php echo e($ctrlNumberSearch ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="book" class="form-control form-control-sm"
                                   placeholder="Search Book Title..."
                                   value="<?php echo e($bookSearch ?? ''); ?>">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="borrower" class="form-control form-control-sm"
                                   placeholder="Search Borrower..."
                                   value="<?php echo e($borrowerSearch ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="date" name="borrowed_date" class="form-control form-control-sm"
                                   value="<?php echo e($borrowedDateSearch ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="bi bi-search me-1"></i>Search
                            </button>
                        </div>
                    </div>
                    <?php if($filterType): ?>
                        <input type="hidden" name="type" value="<?php echo e($filterType); ?>">
                    <?php endif; ?>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div></div>

                <div>
                    <a href="<?php echo e(route('books.lost-damage')); ?>" class="btn btn-sm <?php echo e(!$filterType ? 'btn-primary' : 'btn-light'); ?>">All</a>
                    <a href="<?php echo e(route('books.lost-damage', ['type' => 'lost', 'ctrl_number' => $ctrlNumberSearch ?? '', 'book' => $bookSearch ?? '', 'borrower' => $borrowerSearch ?? '', 'borrowed_date' => $borrowedDateSearch ?? ''])); ?>" class="btn btn-sm <?php echo e($filterType === 'lost' ? 'btn-primary' : 'btn-light'); ?>">Lost</a>
                    <a href="<?php echo e(route('books.lost-damage', ['type' => 'damaged', 'ctrl_number' => $ctrlNumberSearch ?? '', 'book' => $bookSearch ?? '', 'borrower' => $borrowerSearch ?? '', 'borrowed_date' => $borrowedDateSearch ?? ''])); ?>" class="btn btn-sm <?php echo e($filterType === 'damaged' ? 'btn-primary' : 'btn-light'); ?>">Damaged</a>
                </div>

            </div>


            
            <div class="table-responsive">

                <table class="table align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Ctrl Number</th>
                            <th>Book</th>
                            <th>Borrower</th>
                            <th>Advisory Class</th>
                            <th>Borrowed Date</th>
                            <th>Due Date</th>
                            <th>Date Reported</th>
                            <th>Note</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php $__empty_1 = true; $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>

                        <tr>

                            <td>
                                <?php if($record->type == 'lost'): ?>
                                    <span class="badge bg-danger">Lost</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Damaged</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="fw-semibold"><?php echo e($record->borrow?->copy_number ?? $record->copy_number ?? 'N/A'); ?></div>
                            </td>

                            <td>
                                <div class="fw-semibold"><?php echo e($record->book?->title ?? 'Unknown'); ?></div>
                                <small class="text-muted">
                                    ISBN: <?php echo e($record->book?->isbn ?? 'N/A'); ?>

                                </small>
                            </td>

                            <td>
                                <div><?php echo e($record->borrower_name ?? 'Unknown'); ?></div>
                                <small class="text-muted">
                                    LRN: <?php echo e($record->borrower_lrn ?? 'N/A'); ?>

                                </small>
                            </td>

                            <td>
	                                <?php if(($record->borrow?->role ?? '') === 'teacher' && ($record->borrow?->origin ?? '') === 'distribution' && (($record->borrow?->advisory_grade ?? null) || ($record->borrow?->advisory_section ?? null))): ?>
	                                    Grade <?php echo e($record->borrow?->advisory_grade ?? '-'); ?> <?php echo e($record->borrow?->advisory_section ?? ''); ?>

	                                <?php else: ?>
	                                    <span class="text-muted">—</span>
	                                <?php endif; ?>
                            </td>

                            <td><?php echo e($record->borrow?->borrowed_at ? $record->borrow->borrowed_at->format('M d, Y') : '—'); ?></td>

                            <td><?php echo e($record->due_date ? $record->due_date->format('M d, Y') : '—'); ?></td>

                            <td>
                                <?php echo e($record->created_at->timezone($displayTimezone)->format('M d, Y h:i A')); ?>

                            </td>

                            <td>
                                <?php ($note = trim((string) ($record->remarks ?? $record->borrow?->notes ?? ''))); ?>
                                <?php if($note !== ''): ?>
                                    <div class="small text-muted" style="white-space: pre-wrap;"><?php echo e($note); ?></div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <?php if($record->type === 'damaged'): ?>
                                        <form action="<?php echo e(route('books.lost-damage.repaired', $record->id)); ?>"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Mark this item as repaired?');">
                                            <?php echo csrf_field(); ?>
                                            <button class="btn btn-outline-info" type="submit" title="Mark as repaired">
                                                <i class="bi bi-wrench me-1"></i>Repaired
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if($record->type !== 'damaged'): ?>
                                        <form action="<?php echo e(route('books.lost-damage.return', $record->id)); ?>"
                                              method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Mark this item as returned?');">
                                            <?php echo csrf_field(); ?>
                                            <button class="btn btn-outline-success" type="submit" title="Mark as returned">
                                                <i class="bi bi-check-circle me-1"></i>Returned
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>

                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>

                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                No lost or damaged items found
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>
    </div>


    
    <div class="card shadow-sm">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-1">History logs</h5>
                    <p class="text-muted small mb-0">
                        Recent lost/damaged actions (read-only).
                    </p>
                </div>
            </div>

            <div id="lostDamageHistoryContainer">
                <?php echo $__env->make('books.partials.lost-damage-history', ['history' => $history], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>

        </div>
    </div>


</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    (function () {
        const containerId = 'lostDamageHistoryContainer';

        async function loadHistoryPage(url, pushState = true) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const u = new URL(url, window.location.origin);
            u.searchParams.set('history_only', '1');

            container.setAttribute('aria-busy', 'true');
            try {
                const res = await fetch(u.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                if (!res.ok) throw new Error('Request failed');
                container.innerHTML = await res.text();

                if (pushState) {
                    u.searchParams.delete('history_only');
                    window.history.pushState({ history_page: u.searchParams.get('history_page') }, '', u.toString());
                }
            } catch (e) {
                window.location.href = url;
            } finally {
                container.removeAttribute('aria-busy');
            }
        }

        document.addEventListener('click', function (e) {
            const link = e.target.closest('#' + containerId + ' .pagination a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href) return;

            e.preventDefault();
            loadHistoryPage(href);
        });

        window.addEventListener('popstate', function () {
            const u = new URL(window.location.href);
            if (u.searchParams.has('history_page')) {
                loadHistoryPage(u.toString(), false);
            }
        });
    })();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\user\Herd\library\resources\views/books/lost-damage.blade.php ENDPATH**/ ?>