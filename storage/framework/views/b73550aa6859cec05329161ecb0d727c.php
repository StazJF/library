<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-0">Audit Summary</h3>
            <div class="text-muted small">
                SY <?php echo e($session->school_year); ?>

                • Started <?php echo e($session->started_at?->format('M d, Y h:i A')); ?>

                <?php if($session->ended_at): ?>
                    • Ended <?php echo e($session->ended_at?->format('M d, Y h:i A')); ?>

                <?php endif; ?>
                • Status: <span class="badge bg-<?php echo e($session->status === 'OPEN' ? 'warning' : 'success'); ?>"><?php echo e($session->status); ?></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('audit.show', $session)); ?>" class="btn btn-outline-dark">Scanning</a>
            <a href="<?php echo e(route('audit.report', $session)); ?>" class="btn btn-dark">Print Report</a>
        </div>
    </div>

    <?php if(session('status')): ?>
        <div class="alert alert-success"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Manual Status Update</div>
                <div class="card-body">
                    <?php if($session->status !== 'OPEN'): ?>
                        <div class="alert alert-info mb-0">Session is finalized. Re-open (admin) to edit statuses.</div>
                    <?php else: ?>
                        <form method="POST" action="<?php echo e(route('audit.status', $session)); ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="redirect_to" value="<?php echo e(url()->full()); ?>">
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Control Number</label>
                                <input type="text" name="control_number" class="form-control" placeholder="e.g. CN000123" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Status</label>
                                <select name="result_status" class="form-select" required>
                                    <option value="VERIFIED">Verified</option>
                                    <option value="DAMAGED">Damaged</option>
                                    <option value="MISPLACED">Misplaced</option>
                                    <option value="MISSING">Missing</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small text-muted mb-1">Found Location (optional)</label>
                                <input type="text" name="location" class="form-control" placeholder="e.g. G10 Room Shelf A">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted mb-1">Remarks (optional)</label>
                                <input type="text" name="remarks" class="form-control" placeholder="e.g. torn cover, missing pages">
                            </div>
                            <button class="btn btn-outline-dark w-100" type="submit">
                                <i class="fas fa-save me-2"></i>Save Status
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Totals</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="text-muted small">Total in scope</div><div class="fs-5 fw-semibold"><?php echo e($summary['total_in_scope']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Scanned</div><div class="fs-5 fw-semibold"><?php echo e($summary['scanned_total']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Verified</div><div class="fs-5 fw-semibold"><?php echo e($summary['verified']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Missing</div><div class="fs-5 fw-semibold"><?php echo e($summary['missing']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Damaged</div><div class="fs-5 fw-semibold"><?php echo e($summary['damaged']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Misplaced</div><div class="fs-5 fw-semibold"><?php echo e($summary['misplaced']); ?></div></div>
                        
                    </div>

                    <hr>

                    <?php if($session->status === 'OPEN'): ?>
                        <form method="POST" action="<?php echo e(route('audit.finalize', $session)); ?>" onsubmit="return confirm('Finalize this audit session? This will compute missing items based on books not scanned.');">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-lock me-2"></i>Finalize Audit
                            </button>
                        </form>
                    <?php else: ?>
                        <?php if((auth()->user()->role ?? null) === 'admin'): ?>
                            <form method="POST" action="<?php echo e(route('audit.reopen', $session)); ?>" onsubmit="return confirm('Re-open this audit session for additional scanning?');">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-outline-dark w-100">
                                    <i class="fas fa-unlock me-2"></i>Re-open (Admin)
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">This session is finalized.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Recommendations</div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li>Review missing candidates and confirm if any are currently borrowed or transferred.</li>
                        <li>Encode or correct any “unknown” control numbers (barcode/label issues).</li>
                        <li>Repair/replace damaged copies and update condition tracking.</li>
                        <li>Follow up overdue borrowers based on school policy.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
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
                            <?php $__empty_1 = true; $__currentLoopData = $missingCandidates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $copy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                    $current = $missingStatusByCn[$copy->control_number] ?? null;
                                    $badge = match($current) {
                                        'VERIFIED' => 'success',
                                        'DAMAGED' => 'danger',
                                        'MISPLACED' => 'warning',
                                        'MISSING' => 'dark',
                                        default => 'secondary',
                                    };
                                ?>
                                <tr>
                                    <td class="fw-semibold"><?php echo e($copy->control_number); ?></td>
                                    <td><?php echo e($copy->book?->title ?? 'Unknown'); ?></td>
                                    <td class="text-end">
                                        <?php if($session->status === 'OPEN'): ?>
                                            <form method="POST" action="<?php echo e(route('audit.status', $session)); ?>" class="d-inline audit-status-form">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="control_number" value="<?php echo e($copy->control_number); ?>">
                                                <input type="hidden" name="result_status" value="<?php echo e($current ?? 'MISSING'); ?>">
                                                <input type="hidden" name="remarks" value="Set from missing candidates">
                                                <input type="hidden" name="redirect_to" value="<?php echo e(url()->full()); ?>">

                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-<?php echo e($current === 'VERIFIED' ? 'success' : ($current === 'DAMAGED' ? 'danger' : ($current === 'MISPLACED' ? 'warning' : ($current === 'MISSING' ? 'dark' : 'secondary')))); ?> dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <?php echo e($current ?? 'UNMARKED'); ?>

                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><button class="dropdown-item <?php echo e($current === 'VERIFIED' ? 'active' : ''); ?>" type="button" data-set-audit-status="VERIFIED">Verified</button></li>
                                                        <li><button class="dropdown-item <?php echo e($current === 'MISSING' ? 'active' : ''); ?>" type="button" data-set-audit-status="MISSING">Missing</button></li>
                                                        <li><button class="dropdown-item <?php echo e($current === 'DAMAGED' ? 'active' : ''); ?>" type="button" data-set-audit-status="DAMAGED">Damaged</button></li>
                                                        <li><button class="dropdown-item <?php echo e($current === 'MISPLACED' ? 'active' : ''); ?>" type="button" data-set-audit-status="MISPLACED">Misplaced</button></li>
                                                    </ul>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">Finalized</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">No missing candidates (based on current scope and scans).</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="text-muted small">
                            <?php if(method_exists($missingCandidates, 'total')): ?>
                                Showing <?php echo e($missingCandidates->firstItem() ?? 0); ?> to <?php echo e($missingCandidates->lastItem() ?? 0); ?>

                                of <?php echo e($missingCandidates->total()); ?> items.
                            <?php else: ?>
                                Showing <?php echo e(count($missingCandidates)); ?> items.
                            <?php endif; ?>
                        </div>
                        <?php if(method_exists($missingCandidates, 'links')): ?>
                            <div>
                                <?php echo e($missingCandidates->links()); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-muted small mt-1">Print report for the full list.</div>
                </div>
            </div>

            

            
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jimmu\Herd\Library\resources\views/audit/summary.blade.php ENDPATH**/ ?>