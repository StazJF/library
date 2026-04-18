<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h3 class="mb-0">Audit Scanning</h3>
            <div class="text-muted small">
                SY <?php echo e($session->school_year); ?>

                • Started <?php echo e($session->started_at?->format('M d, Y h:i A')); ?>

                • Status: <span class="badge bg-<?php echo e($session->status === 'OPEN' ? 'warning' : 'success'); ?>"><?php echo e($session->status); ?></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('audit.summary', $session)); ?>" class="btn btn-outline-dark">Summary</a>
            <a href="<?php echo e(route('audit.index')); ?>" class="btn btn-dark">All Sessions</a>
        </div>
    </div>

    <?php if(session('audit_scan_message')): ?>
        <?php $lvl = session('audit_scan_level', 'success'); ?>
        <div class="alert alert-<?php echo e($lvl); ?>"><?php echo e(session('audit_scan_message')); ?></div>
    <?php endif; ?>
    <?php if(session('status')): ?>
        <div class="alert alert-success"><?php echo e(session('status')); ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Scan / Input Control Number</div>
                <div class="card-body">
                    <?php if($session->status !== 'OPEN'): ?>
                        <div class="alert alert-info mb-0">
                            This session is finalized. You can view the summary and report.
                        </div>
                    <?php else: ?>
                        <form method="POST" action="<?php echo e(route('audit.scan', $session)); ?>">
                            <?php echo csrf_field(); ?>
                            <label class="form-label">Control Number (barcode)</label>
                            <input
                                id="controlNumberInput"
                                type="text"
                                name="control_number"
                                class="form-control form-control-lg <?php $__errorArgs = ['control_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                placeholder="Scan here..."
                                autocomplete="off"
                                autofocus
                            />
                            <?php $__errorArgs = ['control_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="invalid-feedback"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <div class="form-text">Tip: most barcode scanners type then press Enter automatically.</div>
                        </form>

                        <?php $lastCn = session('audit_last_control_number'); ?>
                        <?php if($lastCn): ?>
                            <hr>
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="fw-semibold">Last scanned</div>
                                <div class="text-muted small"><?php echo e($lastCn); ?></div>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <form method="POST" action="<?php echo e(route('audit.status', $session)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="redirect_to" value="<?php echo e(route('audit.show', $session)); ?>">
                                    <input type="hidden" name="control_number" value="<?php echo e($lastCn); ?>">
                                    <input type="hidden" name="result_status" value="VERIFIED">
                                    <button class="btn btn-sm btn-outline-success" type="submit">Mark Verified</button>
                                </form>

                                <form method="POST" action="<?php echo e(route('audit.status', $session)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="redirect_to" value="<?php echo e(route('audit.show', $session)); ?>">
                                    <input type="hidden" name="control_number" value="<?php echo e($lastCn); ?>">
                                    <input type="hidden" name="result_status" value="DAMAGED">
                                    <input type="hidden" name="remarks" value="Damaged during audit">
                                    <button class="btn btn-sm btn-outline-danger" type="submit">Mark Damaged</button>
                                </form>

                                <form method="POST" action="<?php echo e(route('audit.status', $session)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="redirect_to" value="<?php echo e(route('audit.show', $session)); ?>">
                                    <input type="hidden" name="control_number" value="<?php echo e($lastCn); ?>">
                                    <input type="hidden" name="result_status" value="MISPLACED">
                                    <input type="hidden" name="remarks" value="Misplaced during audit">
                                    <button class="btn btn-sm btn-outline-warning" type="submit">Mark Misplaced</button>
                                </form>

                                <form method="POST" action="<?php echo e(route('audit.status', $session)); ?>">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="redirect_to" value="<?php echo e(route('audit.show', $session)); ?>">
                                    <input type="hidden" name="control_number" value="<?php echo e($lastCn); ?>">
                                    <input type="hidden" name="result_status" value="MISSING">
                                    <input type="hidden" name="remarks" value="Marked missing manually">
                                    <button class="btn btn-sm btn-outline-dark" type="submit">Mark Missing</button>
                                </form>
                            </div>
                            <div class="form-text mt-2">
                                For detailed notes (damage description / found location), use the Summary page.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold">Live Summary</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6"><div class="text-muted small">Total in scope</div><div class="fs-5 fw-semibold"><?php echo e($summary['total_in_scope']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Scanned</div><div class="fs-5 fw-semibold"><?php echo e($summary['scanned_total']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Verified</div><div class="fs-5 fw-semibold"><?php echo e($summary['verified']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Missing (set)</div><div class="fs-5 fw-semibold"><?php echo e($summary['missing']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Damaged</div><div class="fs-5 fw-semibold"><?php echo e($summary['damaged']); ?></div></div>
                        <div class="col-6"><div class="text-muted small">Misplaced</div><div class="fs-5 fw-semibold"><?php echo e($summary['misplaced']); ?></div></div>
                        
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <a href="<?php echo e(route('audit.summary', $session)); ?>" class="btn btn-outline-dark btn-sm">View Details</a>
                        <a href="<?php echo e(route('audit.report', $session)); ?>" class="btn btn-dark btn-sm">Print Report</a>
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
                            <?php $__empty_1 = true; $__currentLoopData = $recentScans; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="text-muted small"><?php echo e($log->created_at?->format('h:i:s A')); ?></td>
                                    <td class="text-muted small">
                                        <?php
                                            $evt = $log->event_type ?? '';
                                            $evtBadge = match($evt) {
                                                'SCAN' => 'secondary',
                                                'STATUS_SET' => 'primary',
                                                default => 'light',
                                            };
                                        ?>
                                        <span class="badge bg-<?php echo e($evtBadge); ?>"><?php echo e($evt); ?></span>
                                    </td>
                                    <td class="fw-semibold"><?php echo e($log->control_number); ?></td>
                                    <td>
                                        <?php if($log->bookCopy && $log->bookCopy->book): ?>
                                            <div class="fw-semibold"><?php echo e($log->bookCopy->book->title); ?></div>
                                            <div class="text-muted small"><?php echo e($log->bookCopy->book->author); ?></div>
                                        <?php else: ?>
                                            <span class="text-muted">Not found in DB</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
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
                                        ?>
                                        <span class="badge bg-<?php echo e($badge); ?>"><?php echo e($label); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No activity yet.</td>
                                </tr>
                            <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jimmu\Herd\Library\resources\views/audit/scan.blade.php ENDPATH**/ ?>