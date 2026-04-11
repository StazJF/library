<?php $__env->startSection('content'); ?>

<div class="mb-3">
    <a href="<?php echo e(route('users.index')); ?>" class="btn btn-secondary btn-sm">
        <i class="bi bi-arrow-left"></i> Back to Students
    </a>
    <a href="<?php echo e(route('users.print-user', $user->id)); ?>" class="btn btn-primary btn-sm" target="_blank">
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
                <p><strong>Name:</strong> <?php echo e($user->first_name); ?> <?php echo e($user->last_name); ?></p>
                <p><strong>Grade & Section:</strong> <?php echo e($user->grade_section ?? '-'); ?></p>
                <p><strong>LRN:</strong> <?php echo e($user->lrn ?? '-'); ?></p>
                <p><strong>Gender:</strong> <?php echo e($user->gender ? ucfirst(strtolower($user->gender)) : '-'); ?></p>
                <p><strong>Phone:</strong> <?php echo e($user->phone_number ?? '-'); ?></p>
                <p><strong>Address:</strong> <?php echo e($user->address ?? '-'); ?></p>
                <p><strong>Total Books Borrowed:</strong> <?php echo e($totalBorrows ?? $user->borrows->count()); ?></p>
            </div>
        </div>
    </div>

    <!-- Borrowing History Section -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <h5 class="mb-0">Borrowing History</h5>
                    <?php
                        $currentOrigin = $filterState['origin'] ?? 'all';
                        $currentStatus = $filterState['status'] ?? 'all';
                    ?>
                    <form method="GET" action="<?php echo e(route('users.show', $user->id)); ?>" class="d-flex flex-wrap gap-2 align-items-center">
                        
                        <div class="d-flex align-items-center gap-2">
                            <span class="small text-muted">Book Status</span>
                            <select name="status" class="form-select form-select-sm" style="width: 160px;">
                                <option value="" <?php echo e($currentStatus === 'all' ? 'selected' : ''); ?>>All</option>
                                <option value="lost" <?php echo e($currentStatus === 'lost' ? 'selected' : ''); ?>>Lost</option>
                                <option value="damaged" <?php echo e($currentStatus === 'damaged' ? 'selected' : ''); ?>>Damaged</option>
                                <option value="repaired" <?php echo e($currentStatus === 'repaired' ? 'selected' : ''); ?>>Repaired</option>
                                <option value="found" <?php echo e($currentStatus === 'found' ? 'selected' : ''); ?>>Found</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-sm btn-dark">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="<?php echo e(route('users.show', $user->id)); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <?php
                    $rows = $borrows ?? $user->borrows;
                ?>
                <?php if($rows->count() > 0): ?>
                <?php
                    $today = \Carbon\Carbon::today();
                    // Penalty removed — using remarks instead
                ?>
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Control No.</th>
                                <th>Borrow Date</th>
                                <th>Due Date</th>
                                <th>Returned On</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Book Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $borrow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
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
                                ?>
                                <tr>
                                    <td><?php echo e($borrow->book?->title ?? 'Book not found'); ?></td>
                                    <td><?php echo e($borrow->book?->author ?? '-'); ?></td>
                                    <td>
                                        <div class="font-monospace"><?php echo e(method_exists($borrow, 'getCopyNumberDisplay') ? $borrow->getCopyNumberDisplay() : ($borrow->copy_number ?? $borrow->bookCopy?->control_number ?? '-')); ?></div>
                                        <div class="small text-muted">Ctrl#: <span class="font-monospace"><?php echo e(method_exists($borrow, 'getControlNumberRaw') ? $borrow->getControlNumberRaw() : ($borrow->copy_number ?? $borrow->bookCopy?->control_number ?? '-')); ?></span></div>
                                    </td>
                                    <td><?php echo e($borrowDate ? \Carbon\Carbon::parse($borrowDate)->format('F j, Y') : '-'); ?></td>
                                    <td><?php echo e($dueDate ? \Carbon\Carbon::parse($dueDate)->format('F j, Y') : '-'); ?></td>
                                    <td><?php echo e($returnedAt ? \Carbon\Carbon::parse($returnedAt)->format('F j, Y') : '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo e($borrow->returned_at ? 'success' : 'warning'); ?>">
                                            <?php echo e($borrow->returned_at ? 'Returned' : 'Borrowed'); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $lowerRemark = strtolower($remark);
                                            if (str_contains($lowerRemark, 'overdue') || $lowerRemark === 'lost' || $lowerRemark === 'damage') {
                                                $rc = 'bg-danger';
                                            } elseif ($lowerRemark === 'late return') {
                                                $rc = 'bg-warning';
                                            } else {
                                                $rc = 'bg-success';
                                            }
                                        ?>
                                        <span class="badge <?php echo e($rc); ?>"><?php echo e($remark); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $issueBadge = 'bg-secondary';
                                            $issueIcon = '<i class="bi bi-info-circle me-1"></i>';
                                            $issueLabel = $lossType ? ucfirst($lossType) : '';
                                            if ($lossType === 'lost') {
                                                $issueBadge = 'bg-danger';
                                                $issueIcon = '<i class="bi bi-exclamation-triangle me-1"></i>';
                                            } elseif ($lossType === 'damaged') {
                                                $issueBadge = 'bg-warning text-dark';
                                                $issueIcon = '<i class="bi bi-tools me-1"></i>';
                                            } elseif ($lossType === 'repaired') {
                                                $issueBadge = 'bg-info text-white';
                                                $issueIcon = '<i class="bi bi-check-circle me-1"></i>';
                                            } elseif ($lossType === 'found') {
                                                $issueBadge = 'bg-success';
                                                $issueIcon = '<i class="bi bi-check-circle me-1"></i>';
                                            }
                                        ?>
                                        <?php if($lossType): ?>
                                            <span class="badge <?php echo e($issueBadge); ?>"><?php echo $issueIcon; ?><?php echo e($issueLabel); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted">No borrowing history found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\user\Herd\library\resources\views/users/show.blade.php ENDPATH**/ ?>