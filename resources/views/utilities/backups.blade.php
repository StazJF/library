@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-0" style="color:#111;">Database Backups</h1>
            <p class="text-muted small">A single backup file is maintained and automatically overwritten each time you create a new backup. Automated backups run on schedule via Windows Task Scheduler.</p>
        </div>
        <form id="backupForm" action="{{ route('utilities.backup') }}" method="POST" style="margin-bottom:0;">
            @csrf
            <button type="submit" class="btn btn-dark">
                <i class="fas fa-plus"></i> Create New Backup
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div id="backupAutoAlert" class="alert alert-success alert-dismissible fade" role="alert" style="display:none;">
        <i class="fas fa-bell"></i> <span data-alert-body></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="card">
        <div class="card-body">
            @if(config('backup.password'))
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-lock"></i>
                    <strong>Password-protected ZIP:</strong> Windows File Explorer often cannot extract encrypted ZIP files.
                    Use 7-Zip or WinRAR to extract (you will be prompted for the password).
                </div>
            @endif
            @if(config('backup.secure_export_dir'))
                <div class="alert alert-secondary mb-3">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Secure copy:</strong> The server also overwrites a copy at
                    <code>{{ config('backup.secure_export_dir') }}</code>.
                    (Your browser download location is controlled by the browser.)
                </div>
            @endif
            @if(count($backups) > 0)
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
                            @foreach($backups as $backup)
                                <tr data-backup-name="{{ $backup['name'] }}" data-download-url="{{ route('utilities.downloadBackup', $backup['name']) }}">
                                    <td>
                                        <i class="fas fa-file-archive text-primary"></i> 
                                        {{ $backup['name'] }}
                                    </td>
                                    <td class="backup-size">{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                                    <td>
                                        <small class="text-muted backup-date">{{ $backup['date'] }}</small>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('utilities.downloadBackup', $backup['name']) }}" class="btn btn-sm btn-primary" title="Download backup" data-download-link>
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <form action="{{ route('utilities.deleteBackup', $backup['name']) }}" method="POST" style="display:inline;" class="delete-backup-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this backup? This action cannot be undone.');" title="Delete backup">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-warning"></i> No backup exists yet. Click "Create New Backup" above to create one now.
                </div>
            @endif
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
        const statusUrl = "{{ route('utilities.backupStatus') }}";
        const downloadBaseUrl = "{{ url('utilities/download-backup') }}/";
        const alertEl = document.getElementById('backupAutoAlert');
        const alertBody = alertEl ? alertEl.querySelector('[data-alert-body]') : null;

        let lastMtime = (() => {
            const raw = window.localStorage.getItem('backup_last_mtime');
            const n = raw ? Number(raw) : NaN;
            return Number.isFinite(n) ? n : null;
        })();
        let hadBackup = {{ count($backups) > 0 ? 'true' : 'false' }};

        function shouldAutoDownload() {
            // Default: enabled (so scheduled backups get pulled down automatically)
            return window.localStorage.getItem('backup_auto_download') !== '0';
        }

        function triggerDownload(status) {
            if (!shouldAutoDownload()) return;
            if (!status || !status.name) return;

            const v = status.modified_at_unix ? String(status.modified_at_unix) : String(Date.now());
            const url = downloadBaseUrl + encodeURIComponent(status.name) + '?v=' + encodeURIComponent(v);

            const a = document.createElement('a');
            a.href = url;
            a.rel = 'noopener';
            a.style.display = 'none';
            document.body.appendChild(a);
            a.click();
            a.remove();
        }

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

            const dl = row.querySelector('[data-download-link]');
            if (dl && status.modified_at_unix) {
                const base = row.getAttribute('data-download-url') || (downloadBaseUrl + encodeURIComponent(status.name));
                dl.href = base + '?v=' + encodeURIComponent(String(status.modified_at_unix));
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
                    window.localStorage.removeItem('backup_last_mtime');
                    hadBackup = false;
                    return;
                }

                if (lastMtime === null) {
                    lastMtime = status.modified_at_unix ?? null;
                    if (lastMtime !== null) {
                        window.localStorage.setItem('backup_last_mtime', String(lastMtime));
                    }
                    hadBackup = true;
                    updateRow(status);
                    return;
                }

                if ((status.modified_at_unix ?? null) !== lastMtime) {
                    const wasCreated = !hadBackup;
                    lastMtime = status.modified_at_unix ?? null;
                    if (lastMtime !== null) {
                        window.localStorage.setItem('backup_last_mtime', String(lastMtime));
                    }
                    hadBackup = true;

                    const when = status.modified_at_iso ? new Date(status.modified_at_iso).toLocaleString() : 'just now';
                    setAlert((wasCreated ? 'Backup created' : 'Backup updated') + ' (' + when + ').' + (shouldAutoDownload() ? ' Downloading…' : ''));

                    triggerDownload(status);

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
@endsection
