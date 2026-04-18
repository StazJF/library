<?php $__env->startSection('content'); ?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0" style="color:#111;">Edit Teacher</h2>
        <a href="<?php echo e(route('teachers.index')); ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Teachers
        </a>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form method="POST" action="<?php echo e(route('teachers.update', $teacher->id)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo e(old('name', $teacher->name)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo e(old('email', $teacher->email)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?php echo e(old('employee_id', $teacher->employee_id)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="rank_position" class="form-label">Rank/Position <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rank_position" name="rank_position" value="<?php echo e(old('rank_position', $teacher->rank_position)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male" <?php echo e(old('gender', $teacher->gender) == 'male' ? 'selected' : ''); ?>>Male</option>
                            <option value="female" <?php echo e(old('gender', $teacher->gender) == 'female' ? 'selected' : ''); ?>>Female</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo e(old('address', $teacher->address)); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo e(old('phone_number', $teacher->phone_number)); ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Teacher</button>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\user\Herd\library\resources\views/users/edit_teacher.blade.php ENDPATH**/ ?>