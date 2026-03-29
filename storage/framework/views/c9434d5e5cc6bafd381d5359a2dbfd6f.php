

<?php $__env->startSection('content'); ?>
<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4 gap-3">
        <a href="<?php echo e(route('teachers.index')); ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Teachers
        </a>
        <h1 class="h3 mb-0">Borrow History - <?php echo e($teacher->name); ?></h1>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Name:</strong> <?php echo e($teacher->name); ?>

                            </p>
                            <p class="mb-2">
                                <strong>Email:</strong> <?php echo e($teacher->email); ?>

                            </p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Gender:</strong> <?php echo e(ucfirst($teacher->gender)); ?>

                            </p>
                            <p class="mb-2">
                                <strong>Phone:</strong> <?php echo e($teacher->phone_number); ?>

                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white text-black">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-book me-2"></i>All Borrow History
                </h5>
                <div class="btn-group" role="group">
                    <a href="<?php echo e(route('teachers.borrow-history', $teacher)); ?>" 
                       class="btn btn-sm <?php echo e(!isset($filter) || $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'); ?>">
                        <i class="bi bi-list me-1"></i>All
                    </a>
                    <a href="<?php echo e(route('teachers.borrow-history', ['teacher' => $teacher, 'filter' => 'personal'])); ?>" 
                       class="btn btn-sm <?php echo e(isset($filter) && $filter === 'personal' ? 'btn-primary' : 'btn-outline-primary'); ?>">
                        <i class="bi bi-person-check me-1"></i>Personal
                    </a>
                    <a href="<?php echo e(route('teachers.borrow-history', ['teacher' => $teacher, 'filter' => 'distribution'])); ?>" 
                       class="btn btn-sm <?php echo e(isset($filter) && $filter === 'distribution' ? 'btn-primary' : 'btn-outline-primary'); ?>">
                        <i class="bi bi-box-seam me-1"></i>Distribution
                    </a>
                    <a href="<?php echo e(route('teachers.borrow-history', ['teacher' => $teacher, 'filter' => 'damaged'])); ?>" 
                       class="btn btn-sm <?php echo e(isset($filter) && $filter === 'damaged' ? 'btn-danger' : 'btn-outline-danger'); ?> d-flex align-items-center gap-2"
                       title="Lost: <?php echo e($damagedCounts['lost']); ?> | Damaged: <?php echo e($damagedCounts['damaged']); ?> | Repaired: <?php echo e($damagedCounts['repaired']); ?>">
                        <i class="bi bi-exclamation-triangle me-1"></i>Lost/Damaged/Repaired
                        <?php if($damagedCounts['total'] > 0): ?>
                            <span class="badge bg-light text-danger"><?php echo e($damagedCounts['total']); ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if($borrows->count() > 0): ?>
                <?php if(isset($filter) && $filter === 'damaged' && $damagedCounts['total'] > 0): ?>
                    <div class="alert alert-warning m-3 mb-0">
                        <div class="row">
                            <div class="col-md-3">
                                <strong><i class="bi bi-exclamation-circle me-1"></i>Lost & Found:</strong> <?php echo e($damagedCounts['lost']); ?>

                            </div>
                            <div class="col-md-3">
                                <strong><i class="bi bi-tools me-1"></i>Damaged (Awaiting Repair):</strong> <?php echo e($damagedCounts['damaged']); ?>

                            </div>
                            <div class="col-md-3">
                                <strong><i class="bi bi-check-circle me-1"></i>Repaired:</strong> <?php echo e($damagedCounts['repaired']); ?>

                            </div>
                            <div class="col-md-3">
                                <strong><i class="bi bi-basket me-1"></i>Total Issues:</strong> <?php echo e($damagedCounts['total']); ?>

                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="border-0 fw-semibold" style="width: 40px;">#</th>
                                <th class="border-0 fw-semibold">Book Title</th>
                                <th class="border-0 fw-semibold">Author</th>
                                <th class="border-0 fw-semibold">ISBN</th>
                                <th class="border-0 fw-semibold">Borrowed On</th>
                                <th class="border-0 fw-semibold">Due Date</th>
                                <th class="border-0 fw-semibold">Returned On</th>
                                <th class="border-0 fw-semibold">Status</th>
                                <th class="border-0 fw-semibold">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $borrows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $borrow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $bookTitle = $borrow->book ? $borrow->book->title : 'Book not found';
                                    $bookAuthor = $borrow->book ? ($borrow->book->author ?? 'N/A') : 'N/A';
                                    $bookIsbn = $borrow->book ? ($borrow->book->isbn ?? 'N/A') : 'N/A';
                                    $borrowedAt = $borrow->borrowed_at ? \Carbon\Carbon::parse($borrow->borrowed_at)->format('M d, Y') : 'N/A';
                                    $dueDate = $borrow->due_date ? \Carbon\Carbon::parse($borrow->due_date)->format('M d, Y') : 'N/A';
                                    $returnedAt = $borrow->returned_at ? \Carbon\Carbon::parse($borrow->returned_at)->format('M d, Y') : '-';
                                    $status = $borrow->returned_at ? 'Returned' : 'Active';
                                    $statusBadgeClass = $borrow->returned_at ? 'bg-success' : 'bg-warning';
                                    $remark = $borrow->remark ?? '-';
                                ?>
                                <tr>
                                    <td><?php echo e($index + 1); ?></td>
                                    <td>
                                        <strong><?php echo e($bookTitle); ?></strong>
                                    </td>
                                    <td><?php echo e($bookAuthor); ?></td>
                                    <td>
                                        <small class="text-muted"><?php echo e($bookIsbn); ?></small>
                                    </td>
                                    <td><?php echo e($borrowedAt); ?></td>
                                    <td><?php echo e($dueDate); ?></td>
                                    <td><?php echo e($returnedAt); ?></td>
                                    <td>
                                        <span class="badge <?php echo e($statusBadgeClass); ?>">
                                            <?php echo e($status); ?>

                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                            $remarkBadgeClass = 'bg-secondary';
                                            $remarkIcon = '';
                                            $displayRemark = '';
                                            $shouldDisplay = false;
                                            
                                            // Check if there's a LostDamagedItem record
                                            $ldi = $borrow->lostDamagedItem;
                                            if ($ldi && $ldi->user_id === $teacher->id && $ldi->role === 'teacher') {
                                                // Only show lost items if they have been found
                                                if (strtolower($ldi->type) === 'lost' && strtolower($ldi->status) === 'found') {
                                                    $shouldDisplay = true;
                                                    $remarkBadgeClass = 'bg-success';
                                                    $remarkIcon = '<i class="bi bi-check-circle me-1"></i>';
                                                    $displayRemark = 'Lost & Found';
                                                } 
                                                // Only show damaged items if they have been repaired
                                                elseif (strtolower($ldi->type) === 'damaged' && strtolower($ldi->status) === 'repaired') {
                                                    $shouldDisplay = true;
                                                    $remarkBadgeClass = 'bg-info text-white';
                                                    $remarkIcon = '<i class="bi bi-check-circle me-1"></i>';
                                                    $displayRemark = 'Repaired';
                                                }
                                            }
                                        ?>
                                        <?php if($shouldDisplay): ?>
                                            <span class="badge <?php echo e($remarkBadgeClass); ?>">
                                                <?php echo $remarkIcon; ?><?php echo e($displayRemark); ?>

                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-4">
                            <p class="mb-0">
                                <strong>Total Borrowed:</strong> <?php echo e($borrows->count()); ?>

                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-0">
                                <strong>Active:</strong> <?php echo e($borrows->whereNull('returned_at')->count()); ?>

                            </p>
                        </div>
                        <div class="col-md-4">
                            <p class="mb-0">
                                <strong>Returned:</strong> <?php echo e($borrows->whereNotNull('returned_at')->count()); ?>

                            </p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <p>No borrow history found for this teacher.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\user\Herd\library\resources\views/users/teacher-borrow-history.blade.php ENDPATH**/ ?>