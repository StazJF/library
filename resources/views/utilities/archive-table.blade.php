@php
    $labels = [
        'book' => ['singular' => 'Book', 'plural' => 'Books'],
        'book_copy' => ['singular' => 'Book Copy', 'plural' => 'Book Copies'],
        'student' => ['singular' => 'Student', 'plural' => 'Students'],
        'teacher' => ['singular' => 'Teacher', 'plural' => 'Teachers'],
        'staff' => ['singular' => 'Staff', 'plural' => 'Staff'],
    ];
    $labelSingular = $labels[$type]['singular'] ?? ucfirst(str_replace('_', ' ', (string) $type));
    $labelPlural = $labels[$type]['plural'] ?? ($labelSingular . 's');
    $colspan = match($type) {
        'book' => 8,
        'book_copy' => 9,
        'student' => 7,
        'teacher' => 6,
        'staff' => 5,
        default => 7
    };
@endphp

@if($items->count() > 0)
    <style>
        .archive-table table tr[data-href]:hover {
            background-color: #e0e7ff !important;
            cursor: pointer;
        }
    </style>
    <div class="archive-table" data-type="{{ $type }}">
    {{-- Search Form for archive table --}}
    <form class="row g-2 mb-3" method="GET" action="{{ url()->current() }}">
        <div class="col-auto" style="flex:1 1 320px;">
            <input type="search" name="q" class="form-control form-control-sm" placeholder="Search {{ $labelPlural }} by keyword..." value="{{ request('q') }}">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Search</button>
        </div>
    </form>

    <div class="mb-3 d-flex flex-wrap gap-2">
        <form action="{{ route('utilities.restoreAll', $type) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore all {{ $labelPlural }}? This will restore all deleted records.');">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-success btn-sm d-flex align-items-center gap-1" style="border-radius:0.375rem;">
                <i class="bi bi-arrow-clockwise"></i> <span>Restore All</span>
            </button>
        </form>
        <form action="{{ route('utilities.deleteAll', $type) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete all {{ $labelPlural }}? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm d-flex align-items-center gap-1" style="border-radius:0.375rem;">
                <i class="bi bi-trash"></i> <span>Delete All</span>
            </button>
        </form>
    </div>

    <div class="table-responsive rounded shadow-sm border">
    <table class="table align-middle mb-0" style="background:#fff;">
        <thead style="background:#f3f4f6;">
            <tr>
                <th style="width:40px;">
                    <input type="checkbox" id="selectAllArchive-{{ $type }}" aria-label="Select all">
                </th>
                @if($type === 'book')
                    <th>Title</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Ctrl #</th>
                    <th>Condition</th>
                @elseif($type === 'book_copy')
                    <th>Book</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Control #</th>
                    <th>Year</th>
                    <th>Condition</th>
                    <th>Status</th>
                @elseif($type === 'student')
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Grade/Section</th>
                    <th>Email</th>
                @elseif($type === 'teacher')
                    <th>Name</th>
                    <th>Email</th>
                    <th>Remarks</th>
                @elseif($type === 'staff')
                    <th>Email</th>
                    <th>Role</th>
                @endif
                <th>Deleted At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr @if($type === 'book' && !empty($item->call_number)) data-href="#" @endif>
                    <td>
                        <input type="checkbox" class="archive-checkbox" data-restore-url="{{ route('utilities.restore', [$type, $item->id ?? $item->id]) }}" data-delete-url="{{ route('utilities.delete', [$type, $item->id ?? $item->id]) }}" aria-label="Select {{ $labelSingular }}">
                    </td>
                    @if($type === 'book')
                        @php
                            $deletedCopiesForCtrl = $item->deletedCopies ?? collect();
                            $ctrlBase = $item->call_number;
                            if ((!$ctrlBase || trim((string) $ctrlBase) === '') && $deletedCopiesForCtrl->count() > 0) {
                                $firstCtrl = (string) ($deletedCopiesForCtrl->first()->control_number ?? '');
                                $ctrlBase = trim(explode('-', $firstCtrl, 2)[0] ?? '');
                            }
                        @endphp
                        <td>{{ $item->title ?? 'N/A' }}</td>
                        <td>{{ $item->author ?? 'N/A' }}</td>
                        <td>{{ $item->isbn ?? 'N/A' }}</td>
                        <td>{{ $ctrlBase && $ctrlBase !== '' ? $ctrlBase : 'N/A' }}</td>
                        <td>{{ $item->condition && $item->condition !== '' ? $item->condition : 'N/A' }}</td>
                    @elseif($type === 'book_copy')
                        <td>{{ $item->book?->title ?? 'Unknown' }}</td>
                        <td>{{ $item->book?->author ?? 'Unknown' }}</td>
                        <td>{{ $item->book?->isbn ?? 'N/A' }}</td>
                        <td>{{ $item->control_number ?? 'N/A' }}</td>
                        <td>{{ $item->acquisition_year ?? 'N/A' }}</td>
                        <td>{{ $item->condition ?? 'N/A' }}</td>
                        <td>{{ $item->status ?? 'N/A' }}</td>
                    @elseif($type === 'student')
                        <td>{{ $item->first_name ?? 'N/A' }}</td>
                        <td>{{ $item->last_name ?? 'N/A' }}</td>
                        <td>{{ $item->grade_section ?? 'N/A' }}</td>
                        <td>{{ $item->email ?? 'N/A' }}</td>
                    @elseif($type === 'teacher')
                        <td>{{ $item->name ?? (trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? '')) ?: 'N/A') }}</td>
                        <td>{{ $item->email ?? 'N/A' }}</td>
                        <td>
                            @php
                                $remark = $item->remark ?? 'N/A';
                                $lower = strtolower($remark);
                                $remarkColor = (str_contains($lower, 'lost') || str_contains($lower, 'overdue') || str_contains($lower, 'damage')) ? 'text-danger' : 'text-success';
                            @endphp
                            <span class="fw-semibold {{ $remarkColor }}">{{ $remark }}</span>
                        </td>
                    @elseif($type === 'staff')
                        <td>{{ $item->email ?? 'N/A' }}</td>
                        <td>{{ ucfirst($item->role ?? 'N/A') }}</td>
                    @endif
                    <td>
                        {{ $item->deleted_at ? $item->deleted_at->format('M d, Y H:i') : 'N/A' }}
                    </td>
                    <td class="d-flex gap-1">
                        <!-- Restore Button -->
                        <form action="{{ route('utilities.restore', [$type, $item->id ?? $item->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to restore this {{ $labelSingular }}?');">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-primary btn-sm" style="border-radius:0.375rem;" title="Restore">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </form>

                        <!-- Delete Permanently Button -->
                        <form action="{{ route('utilities.delete', [$type, $item->id ?? $item->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to permanently delete this {{ $labelSingular }}? This cannot be undone.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm" style="border-radius:0.375rem;" title="Delete Permanently">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                @if($type === 'book')
                    @php
                        $deletedCopies = $item->deletedCopies ?? collect();
                        $copiesId = 'deleted-copies-' . $item->id;
                    @endphp
                    <tr class="table-light">
                        <td colspan="{{ $colspan }}">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary toggle-copies-btn" data-target="{{ $copiesId }}" style="border-radius:0.375rem; padding: 0.25rem 0.5rem;">
                                    <i class="bi bi-chevron-right"></i>
                                    <span class="fw-semibold">Deleted Book Copies ({{ $deletedCopies->count() }})</span>
                                </button>
                            </div>

                            <div id="{{ $copiesId }}" class="deleted-copies-container" style="display: none; margin-top: 0.75rem;">
                                @if($deletedCopies->count() > 0)
                                    <div class="table-responsive mt-2">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width:140px;">Control #</th>
                                                    <th style="width:110px;">Year</th>
                                                    <th style="width:140px;">Condition</th>
                                                    <th style="width:110px;">Status</th>
                                                    <th style="width:170px;">Deleted At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($deletedCopies as $copy)
                                                    <tr>
                                                        <td>{{ $copy->control_number ?? 'N/A' }}</td>
                                                        <td>{{ $copy->acquisition_year ?? 'N/A' }}</td>
                                                        <td>{{ $copy->condition ?? 'N/A' }}</td>
                                                        <td>{{ $copy->status ?? 'N/A' }}</td>
                                                        <td>{{ $copy->deleted_at ? $copy->deleted_at->format('M d, Y H:i') : 'N/A' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-muted small mt-2">No deleted copies found for this book.</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-3">
        @php
            $pageName = match($type) {
                'book' => 'book_page',
                'book_copy' => 'book_copy_page',
                'student' => 'student_page',
                'teacher' => 'teacher_page',
                'staff' => 'staff_page',
                default => 'page'
            };
        @endphp
        {{ $items->appends(request()->query())->links() }}
    </div>
    
    {{-- Bulk action buttons --}}
    <div class="d-flex gap-2 mt-3">
        <button id="restoreSelectedBtn-{{ $type }}" type="button" class="btn btn-sm btn-primary" style="display:none;">Restore Selected</button>
        <button id="deleteSelectedBtn-{{ $type }}" type="button" class="btn btn-sm btn-danger" style="display:none;">Delete Selected</button>
    </div>

    <script>
        (function(){
            const container = document.querySelector('.archive-table[data-type="{{ $type }}"]');
            if(!container) return;
            const selectAll = container.querySelector('#selectAllArchive-{{ $type }}');
            const checkboxes = Array.from(container.querySelectorAll('input.archive-checkbox'));
            const restoreBtn = container.querySelector('#restoreSelectedBtn-{{ $type }}');
            const deleteBtn = container.querySelector('#deleteSelectedBtn-{{ $type }}');

            // Toggle functionality for deleted book copies (scoped per table to avoid double-toggling)
            container.addEventListener('click', function(e) {
                const toggleBtn = e.target.closest('.toggle-copies-btn');
                if (!toggleBtn) return;

                e.preventDefault();
                const targetId = toggleBtn.dataset.target;
                const target = targetId ? document.getElementById(targetId) : null;
                const icon = toggleBtn.querySelector('i');

                if (!target || !icon) return;

                const isHidden = target.style.display === 'none';
                target.style.display = isHidden ? 'block' : 'none';
                icon.classList.toggle('bi-chevron-right', !isHidden);
                icon.classList.toggle('bi-chevron-down', isHidden);
            });

            function updateBulkButtons(){
                const checked = checkboxes.filter(cb => cb.checked);
                const count = checked.length;
                if(count > 0){
                    restoreBtn.style.display = 'inline-block';
                    deleteBtn.style.display = 'inline-block';
                } else {
                    restoreBtn.style.display = 'none';
                    deleteBtn.style.display = 'none';
                }
            }

            if(selectAll){
                selectAll.addEventListener('change', function(){
                    const isChecked = this.checked;
                    checkboxes.forEach(cb => cb.checked = isChecked);
                    updateBulkButtons();
                });
            }

            checkboxes.forEach(cb => cb.addEventListener('change', updateBulkButtons));

            function submitSequential(urls, method){
                if(urls.length === 0) return Promise.resolve({success:0,failed:0});
                let idx = 0; let success = 0; let failed = 0;
                function next(){
                    if(idx >= urls.length) return Promise.resolve({success, failed});
                    const u = urls[idx++];
                    const formData = new FormData();
                    // add CSRF
                    formData.append('_token', '{{ csrf_token() }}');
                    if(method === 'PATCH') formData.append('_method', 'PATCH');
                    if(method === 'DELETE') formData.append('_method', 'DELETE');
                    return fetch(u, { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(res => { if(res.ok) success++; else failed++; })
                        .catch(()=>{ failed++; })
                        .then(() => new Promise(res => setTimeout(res, 200)))
                        .then(next);
                }
                return next();
            }

            if(restoreBtn){
                restoreBtn.addEventListener('click', function(){
                    const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.dataset.restoreUrl).filter(Boolean);
                    if(selected.length === 0) return alert('Please select at least one item to restore.');
                    if(!confirm(`Restore ${selected.length} selected {{ $type }}(s)?`)) return;
                    restoreBtn.disabled = true; restoreBtn.textContent = 'Processing...';
                    submitSequential(selected, 'PATCH').then(result => { 
                        alert(`Restored: ${result.success}, Failed: ${result.failed}`); 
                        setTimeout(() => location.replace(location.href), 300);
                    });
                });
            }

            if(deleteBtn){
                deleteBtn.addEventListener('click', function(){
                    const selected = checkboxes.filter(cb => cb.checked).map(cb => cb.dataset.deleteUrl).filter(Boolean);
                    if(selected.length === 0) return alert('Please select at least one item to delete.');
                    if(!confirm(`Permanently delete ${selected.length} selected {{ $type }}(s)? This cannot be undone.`)) return;
                    deleteBtn.disabled = true; deleteBtn.textContent = 'Processing...';
                    submitSequential(selected, 'DELETE').then(result => { 
                        alert(`Deleted: ${result.success}, Failed: ${result.failed}`); 
                        setTimeout(() => location.replace(location.href), 300);
                    });
                });
            }
        })();
    </script>
    </div>
@else
    <div class="alert alert-info rounded shadow-sm border">
        No deleted {{ $labelPlural }} found.
    </div>
@endif
