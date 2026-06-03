<?php $__env->startSection('content'); ?>
<div class="container-fluid p-0">
    <div class="d-flex justify-content-between align-items-center mb-4 px-4 pt-4">
        <h2 class="fw-bold mb-0" style="color:#111;">Teacher Details</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('teachers.edit', $teacher->id)); ?>" class="btn btn-dark">
                <i class="bi bi-pencil me-2"></i>Edit
            </a>
            <a href="<?php echo e(route('teachers.index')); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Teachers
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mx-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8 mb-4">
                    <h6 class="text-muted mb-2">Name</h6>
                    <p class="fw-semibold"><?php echo e($teacher->name); ?></p>
                </div>
                <div class="col-md-6 mb-4">
    <h6 class="text-muted mb-2">Email</h6>
    <p class="text-dark"><?php echo e($teacher->email); ?></p>
</div>
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Employee ID</h6>
                    <p class="fw-semibold"><?php echo e($teacher->employee_id); ?></p>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Rank/Position</h6>
                    <p class="fw-semibold"><?php echo e($teacher->rank_position); ?></p>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Gender</h6>
                    <p><span class=""><?php echo e(ucfirst($teacher->gender)); ?></span></p>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Address</h6>
                    <p class="fw-semibold"><?php echo e($teacher->address); ?></p>
                </div>
               <div class="col-md-6 mb-4">
    <h6 class="text-muted mb-2">Phone Number</h6>
    <p class="text-dark"><?php echo e($teacher->phone_number); ?></p>
</div>
            </div>

            <hr>

            <div class="mt-4">
                <h5 class="fw-bold mb-3">Borrow History</h5>
                <?php
                    $currentOrigin = $filterState['origin'] ?? 'all';
                    $currentStatus = $filterState['status'] ?? 'all';
                ?>
                <form method="GET" action="<?php echo e(route('teachers.show', $teacher->id)); ?>" class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Borrow Type</span>
                        <select name="origin" class="form-select form-select-sm" style="width: 170px;">
                            <option value="" <?php echo e($currentOrigin === 'all' ? 'selected' : ''); ?>>All</option>
                            <option value="personal" <?php echo e($currentOrigin === 'personal' ? 'selected' : ''); ?>>Personal</option>
                            <option value="distribution" <?php echo e($currentOrigin === 'distribution' ? 'selected' : ''); ?>>Distribution</option>
                        </select>
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <span class="small text-muted">Book Status</span>
                        <select name="status" class="form-select form-select-sm" style="width: 170px;">
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
                    <a href="<?php echo e(route('teachers.show', $teacher->id)); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>

                    <?php if(isset($statusCounts) && is_array($statusCounts)): ?>
                        <div class="ms-auto d-flex flex-wrap gap-2">
                            <span class="badge text-danger">Lost: <?php echo e($statusCounts['lost'] ?? 0); ?></span>
                            <span class="badge text-warning text-yellow">Damaged: <?php echo e($statusCounts['damaged'] ?? 0); ?></span>
                            <span class="badge text-info text-blue">Repaired: <?php echo e($statusCounts['repaired'] ?? 0); ?></span>
                            <span class="badge text-success">Found: <?php echo e($statusCounts['found'] ?? 0); ?></span>
                        </div>
                    <?php endif; ?>
                </form>
                <?php
                    $allBorrows = $teacher->borrows;
                ?>
                <?php if($allBorrows->count() > 0): ?>
                    <div style="max-height: 600px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; width: 100%;">
                        <table class="table table-sm table-hover mb-0">
                            <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                                <tr>
                                    <th style="width: 18%;">Title</th>
                                    <th style="width: 12%;">Author</th>
                                    <th style="width: 10%;">ISBN</th>
                                    <th style="width: 12%;">Advisory Class</th>
                                    <th style="width: 14%;">Control No.</th>
                                    <th style="width: 11%;">Borrowed At</th>
                                    <th style="width: 11%;">Due Date</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 14%;">Issue Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $allBorrows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $borrow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $bookTitle = $borrow->book ? $borrow->book->title : 'Book not found';
                                        $bookAuthor = $borrow->book ? ($borrow->book->author ?? 'N/A') : 'N/A';
                                        $bookIsbn = $borrow->book ? ($borrow->book->isbn ?? 'N/A') : 'N/A';
                                        $borrowedAt = $borrow->borrowed_at ? \Carbon\Carbon::parse($borrow->borrowed_at)->format('M d, Y') : 'N/A';
                                        $dueDate = $borrow->due_date ? \Carbon\Carbon::parse($borrow->due_date)->format('M d, Y') : 'N/A';
                                        $status = $borrow->returned_at ? 'Returned' : 'Active';
                                        $copyNumberDisplay = method_exists($borrow, 'getCopyNumberDisplay') ? $borrow->getCopyNumberDisplay() : ($borrow->copy_number ?? $borrow->bookCopy?->control_number ?? '-');
                                        $controlNumberRaw = method_exists($borrow, 'getControlNumberRaw') ? $borrow->getControlNumberRaw() : ($borrow->copy_number ?? $borrow->bookCopy?->control_number ?? '-');
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
                                        <td><?php echo e($bookTitle); ?></td>
                                        <td><?php echo e($bookAuthor); ?></td>
                                        <td><?php echo e($bookIsbn); ?></td>
                                        <td>
                                            <?php if(($borrow->origin ?? '') === 'distribution' && ($borrow->advisory_grade || $borrow->advisory_section)): ?>
                                                <span>Grade <?php echo e($borrow->advisory_grade ?? '-'); ?> <?php echo e($borrow->advisory_section ?? ''); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="font-monospace"><?php echo e($copyNumberDisplay); ?></div>
                                            <div class="small text-muted">Ctrl#: <span class="font-monospace"><?php echo e($controlNumberRaw); ?></span></div>
                                        </td>
                                        <td><?php echo e($borrowedAt); ?></td>
                                        <td><?php echo e($dueDate); ?></td>
                                        <td>
                                            <?php if($status === 'Returned'): ?>
                                                <span style="color: #198754; font-weight: 500;">Returned</span>
                                            <?php else: ?>
                                                <span style="color: #0c63e4; font-weight: 500;">Active</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                                $issueColor = '#6c757d';
                                                $issueIcon = '<i class="bi bi-info-circle me-1"></i>';
                                                $issueLabel = $lossType ? ucfirst($lossType) : '';
                                                if ($lossType === 'lost') {
                                                    $issueColor = '#dc3545';
                                                    $issueIcon = '<i class="bi bi-exclamation-triangle me-1"></i>';
                                                } elseif ($lossType === 'damaged') {
                                                    $issueColor = '#ff9800';
                                                    $issueIcon = '<i class="bi bi-tools me-1"></i>';
                                                } elseif ($lossType === 'repaired') {
                                                    $issueColor = '#0dcaf0';
                                                    $issueIcon = '<i class="bi bi-check-circle me-1"></i>';
                                                } elseif ($lossType === 'found') {
                                                    $issueColor = '#198754';
                                                    $issueIcon = '<i class="bi bi-check-circle me-1"></i>';
                                                }
                                            ?>
                                            <?php if($lossType): ?>
                                                <span style="color: <?php echo e($issueColor); ?>; font-weight: 500;"><?php echo $issueIcon; ?><?php echo e($issueLabel); ?></span>
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
                    <div class="text-muted">No borrow history.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    body {
        padding-left: 0;
        padding-right: 0;
    }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\user\Herd\library\resources\views/users/show_teacher.blade.php ENDPATH**/ ?>