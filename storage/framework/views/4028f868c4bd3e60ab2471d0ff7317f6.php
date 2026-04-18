<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="d-print-none d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-0">Audit Report</h3>
            <div class="text-muted small">SY <?php echo e($session->school_year); ?> • Session #<?php echo e($session->id); ?></div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('audit.summary', $session)); ?>" class="btn btn-outline-dark">Back</a>
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
    </style>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="d-flex align-items-start justify-content-between gap-3">
                <div>
                    <h4 class="report-title mb-1">Book Audit Report</h4>
                    <div class="text-muted">School Year: <span class="fw-semibold"><?php echo e($session->school_year); ?></span></div>
                </div>
                <div class="text-end">
                    <div class="text-muted small">Generated</div>
                    <div class="fw-semibold"><?php echo e(now()->format('M d, Y h:i A')); ?></div>
                </div>
            </div>

            <hr>

            <table class="table table-borderless report-meta mb-0">
                <tr>
                    <td class="text-muted" style="width: 160px;">Audit Start</td>
                    <td class="fw-semibold"><?php echo e($session->started_at?->format('M d, Y h:i A')); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Audit End</td>
                    <td class="fw-semibold"><?php echo e($session->ended_at ? $session->ended_at->format('M d, Y h:i A') : 'Not finalized'); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Prepared By</td>
                    <td class="fw-semibold"><?php echo e($session->creator?->name ?: ($session->creator?->email ?: 'N/A')); ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Scope</td>
                    <td class="fw-semibold">
                        <?php echo e($session->include_borrowed ? 'Includes borrowed copies' : 'Excludes borrowed copies'); ?>,
                        <?php echo e($session->include_lost_damaged ? 'Includes lost/damaged copies' : 'Excludes lost/damaged copies'); ?>

                    </td>
                </tr>
                <?php if($session->notes): ?>
                    <tr>
                        <td class="text-muted">Notes</td>
                        <td><?php echo e($session->notes); ?></td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Summary</div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6 col-md-3"><div class="text-muted small">Total in scope</div><div class="fs-5 fw-semibold"><?php echo e($summary['total_in_scope']); ?></div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Scanned</div><div class="fs-5 fw-semibold"><?php echo e($summary['scanned_total']); ?></div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Verified</div><div class="fs-5 fw-semibold"><?php echo e($summary['verified']); ?></div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Missing</div><div class="fs-5 fw-semibold"><?php echo e($summary['missing']); ?></div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Damaged</div><div class="fs-5 fw-semibold"><?php echo e($summary['damaged']); ?></div></div>
                <div class="col-6 col-md-3"><div class="text-muted small">Misplaced</div><div class="fs-5 fw-semibold"><?php echo e($summary['misplaced']); ?></div></div>
                
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
                    <?php $__empty_1 = true; $__currentLoopData = $missingCandidates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $copy): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($i + 1); ?></td>
                            <td class="fw-semibold"><?php echo e($copy->control_number); ?></td>
                            <td><?php echo e($copy->book?->title ?? 'Unknown'); ?></td>
                            <td class="text-muted small"><?php echo e($copy->book?->author ?? ''); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    <?php endif; ?>
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
                    <?php $__empty_1 = true; $__currentLoopData = $missing; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($i + 1); ?></td>
                            <td class="fw-semibold"><?php echo e($log->control_number); ?></td>
                            <td><?php echo e($log->bookCopy?->book?->title ?? 'Unknown'); ?></td>
                            <td class="text-muted small"><?php echo e($log->remarks ?? ''); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    <?php endif; ?>
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
                    <?php $__empty_1 = true; $__currentLoopData = $damaged; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($i + 1); ?></td>
                            <td class="fw-semibold"><?php echo e($log->control_number); ?></td>
                            <td><?php echo e($log->bookCopy?->book?->title ?? 'Unknown'); ?></td>
                            <td class="text-muted small"><?php echo e($log->remarks ?? ''); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">None</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
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
                    <?php $__empty_1 = true; $__currentLoopData = $misplaced; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><?php echo e($i + 1); ?></td>
                            <td class="fw-semibold"><?php echo e($log->control_number); ?></td>
                            <td><?php echo e($log->bookCopy?->book?->title ?? 'Unknown'); ?></td>
                            <td class="text-muted small"><?php echo e($log->location ?? ''); ?></td>
                            <td class="text-muted small"><?php echo e($log->remarks ?? ''); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">None</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    

    <div class="mt-4 small text-muted">
        <div class="fw-semibold">Recommendations</div>
        <ol class="mb-0">
            <li>Investigate missing copies; verify if checked-out, transferred, or mislabeled.</li>
            <li>Repair/replace damaged copies; document condition for compliance reporting.</li>
            <li>Resolve unknown control numbers (encode missing copies or fix barcode labels).</li>
            <li>Follow up overdue borrowers and document actions taken.</li>
        </ol>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jimmu\Herd\Library\resources\views/audit/report.blade.php ENDPATH**/ ?>