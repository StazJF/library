<?php $__env->startSection('content'); ?>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>Database Backups</h1>
            <p class="text-muted small">A single backup file is maintained and automatically overwritten each time you create a new backup. Automated backups run on schedule via Windows Task Scheduler.</p>
        </div>
        <form id="backupForm" action="<?php echo e(route('utilities.backup')); ?>" method="POST" style="margin-bottom:0;">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn btn-dark">
                <i class="fas fa-plus"></i> Create New Backup
            </button>
        </form>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div id="backupAutoAlert" class="alert alert-success alert-dismissible fade" role="alert" style="display:none;">
        <i class="fas fa-bell"></i> <span data-alert-body></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if(count($backups) > 0): ?>
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle"></i> <strong>Current Backup:</strong> This is your latest database backup. It is automatically overwritten each time you create a new backup.
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Last Updated</th>
                                <th class="text-center" style="width: 200px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $backups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $backup): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr data-backup-name="<?php echo e($backup['name']); ?>">
                                    <td>
                                        <i class="fas fa-file-archive text-primary"></i> 
                                        <?php echo e($backup['name']); ?>

                                    </td>
                                    <td class="backup-size"><?php echo e(number_format($backup['size'] / 1024, 2)); ?> KB</td>
                                    <td>
                                        <small class="text-muted backup-date"><?php echo e($backup['date']); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo e(route('utilities.downloadBackup', $backup['name'])); ?>" class="btn btn-sm btn-primary" title="Download backup">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <form action="<?php echo e(route('utilities.deleteBackup', $backup['name'])); ?>" method="POST" style="display:inline;" class="delete-backup-form">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this backup? This action cannot be undone.');" title="Delete backup">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-warning"></i> No backup exists yet. Click "Create New Backup" above to create one now.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Backup Information Card -->
    <div class="card mt-4">
        <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-cog"></i> Backup Configuration</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="fw-bold">Manual Backups</h6>
                    <ul class="small text-muted">
                        <li>Created on-demand by clicking the button above</li>
                        <li>Stored as <code>database_backup.zip</code></li>
                        <li>Overwrites the previous backup</li>
                        <li>Always shows the latest backup state</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="fw-bold">Automated Backups</h6>
                    <ul class="small text-muted">
                        <li>Run via Windows Task Scheduler on a set schedule</li>
                        <li>Uses the same file: <code>database_backup.zip</code></li>
                        <li><strong>To set up:</strong> Double-click <code>backup-script.ps1</code> or run in PowerShell</li>
                        <li>Check logs in <code>storage/logs/</code> to verify it ran</li>
                    </ul>
                </div>
            </div>
            <hr>
            <div class="alert alert-warning mt-3 mb-0">
                <i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> The backup file overwrites previous backups. If you need to keep multiple versions, download and save copies with different names before creating new backups.
            </div>
        </div>
    </div>
</div>

<script>
    const backupForm = document.getElementById('backupForm');
    if (backupForm) {
        backupForm.addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating backup...';
        });
    }

    (function () {
        const statusUrl = "<?php echo e(route('utilities.backupStatus')); ?>";
        const alertEl = document.getElementById('backupAutoAlert');
        const alertBody = alertEl ? alertEl.querySelector('[data-alert-body]') : null;

        let lastMtime = null;
        let hadBackup = <?php echo e(count($backups) > 0 ? 'true' : 'false'); ?>;

        function setAlert(message) {
            if (!alertEl || !alertBody) return;

            alertBody.textContent = message;
            alertEl.style.display = 'block';
            alertEl.classList.add('show');

            window.clearTimeout(window.__backupAlertTimer);
            window.__backupAlertTimer = window.setTimeout(() => {
                alertEl.classList.remove('show');
                alertEl.style.display = 'none';
            }, 10000);
        }

        function updateRow(status) {
            const row = document.querySelector(`[data-backup-name="${status.name}"]`);
            if (!row) return false;

            const dateCell = row.querySelector('.backup-date');
            if (dateCell && status.modified_at_iso) {
                dateCell.textContent = new Date(status.modified_at_iso).toLocaleString();
            }

            const sizeCell = row.querySelector('.backup-size');
            if (sizeCell && typeof status.size === 'number') {
                sizeCell.textContent = (status.size / 1024).toFixed(2) + ' KB';
            }

            return true;
        }

        async function checkStatus() {
            try {
                const resp = await fetch(statusUrl, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store',
                });

                if (!resp.ok) return;
                const status = await resp.json();

                if (!status.exists) {
                    lastMtime = null;
                    hadBackup = false;
                    return;
                }

                if (lastMtime === null) {
                    lastMtime = status.modified_at_unix ?? null;
                    hadBackup = true;
                    updateRow(status);
                    return;
                }

                if ((status.modified_at_unix ?? null) !== lastMtime) {
                    const wasCreated = !hadBackup;
                    lastMtime = status.modified_at_unix ?? null;
                    hadBackup = true;

                    const when = status.modified_at_iso ? new Date(status.modified_at_iso).toLocaleString() : 'just now';
                    setAlert((wasCreated ? 'Backup created' : 'Backup updated') + ' (' + when + ').');

                    if (wasCreated) {
                        window.setTimeout(() => window.location.reload(), 1500);
                        return;
                    }

                    if (!updateRow(status)) {
                        window.setTimeout(() => window.location.reload(), 1500);
                    }
                }
            } catch (e) {
                // Ignore transient errors (offline, etc.)
            }
        }

        checkStatus();
        window.setInterval(checkStatus, 60000);
    })();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jimmu\Herd\library\resources\views/utilities/backups.blade.php ENDPATH**/ ?>