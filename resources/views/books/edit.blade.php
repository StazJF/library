  @extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card">
            <div class="card-header">
                <h4>Edit Book</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('books.update', $book) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="row g-4">
                        {{-- Left Column: Book Information Form --}}
                        <div class="col-lg-5">
                            <div class="book-form-section">
                                <h6 class="text-muted mb-3"><i class="bi bi-book me-2"></i>Book Information</h6>
                                
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="{{ $book->title }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="author" class="form-label">Author</label>
                                    <input type="text" class="form-control" id="author" name="author" value="{{ $book->author }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="publisher" class="form-label">Publisher</label>
                                    <input type="text" class="form-control" id="publisher" name="publisher" value="{{ $book->publisher }}">
                                </div>
                                <div class="mb-3">
                                    <label for="isbn" class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="isbn" name="isbn" value="{{ $book->isbn }}" required>
                                </div>
                                <div class="mb-3">
                                    <label for="call_number" class="form-label">Control Number Base</label>
                                    <input type="text" class="form-control @error('call_number') is-invalid @enderror" id="call_number" name="call_number" value="{{ old('call_number', $book->call_number ?? $nextCtrlBase) }}">
                                    @error('call_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                @php
                                    $selectedCat = old('category', $book->category);
                                    $isOther = $selectedCat === 'other' || (!$categories->contains($selectedCat) && $selectedCat !== '');
                                @endphp
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
                                        <option value="">-- Select Category --</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}" {{ $selectedCat === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                        <option value="other" {{ $selectedCat === 'other' ? 'selected' : '' }}>Other</option>
                                    </select>
                                    <input type="text" name="other_category" id="other_category" class="form-control mt-2 @error('other_category') is-invalid @enderror" placeholder="Enter category" value="" style="display: none;">
                                    @error('category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @error('other_category')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="published_year" class="form-label">Published Year</label>
                                        <input
                                            type="number"
                                            name="published_year"
                                            id="published_year"
                                            class="form-control @error('published_year') is-invalid @enderror"
                                            value="{{ old('published_year', $book->published_year) }}"
                                            min="1900"
                                            max="{{ date('Y') + 1 }}"
                                        >
                                        @error('published_year')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label for="pages" class="form-label">Pages</label>
                                        <input
                                            type="number"
                                            name="pages"
                                            id="pages"
                                            class="form-control @error('pages') is-invalid @enderror"
                                            value="{{ old('pages', $book->pages) }}"
                                            min="1"
                                        >
                                        @error('pages')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="edition" class="form-label">Edition</label>
                                    <input
                                        type="text"
                                        name="edition"
                                        id="edition"
                                        class="form-control @error('edition') is-invalid @enderror"
                                        value="{{ old('edition', $book->edition) }}"
                                        placeholder="e.g., 3rd Edition"
                                    >
                                    @error('edition')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label for="condition" class="form-label">Condition</label>
                                        <select
                                            name="condition"
                                            id="condition"
                                            class="form-select @error('condition') is-invalid @enderror"
                                        >
                                            <option value="">-- Select --</option>
                                            <option value="Brand New" {{ old('condition', $book->condition) === 'Brand New' ? 'selected' : '' }}>Brand New</option>
                                            <option value="Old" {{ old('condition', $book->condition) === 'Old' ? 'selected' : '' }}>Old</option>
                                        </select>
                                        @error('condition')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="acquisition_type" class="form-label">Acquisition Type</label>
                                    <select
                                        name="acquisition_type"
                                        id="acquisition_type"
                                        class="form-select @error('acquisition_type') is-invalid @enderror"
                                    >
                                        <option value="">-- Select Type --</option>
                                        <option value="purchase" {{ old('acquisition_type', $book->acquisition_type) === 'purchase' ? 'selected' : '' }}>Purchase</option>
                                        <option value="donation" {{ old('acquisition_type', $book->acquisition_type) === 'donation' ? 'selected' : '' }}>Donation</option>
                                        <option value="exchange" {{ old('acquisition_type', $book->acquisition_type) === 'exchange' ? 'selected' : '' }}>Exchange</option>
                                        <option value="grant" {{ old('acquisition_type', $book->acquisition_type) === 'grant' ? 'selected' : '' }}>Grant</option>
                                    </select>
                                    @error('acquisition_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="source_of_funds" class="form-label">Source of Funds</label>
                                    <input
                                        type="text"
                                        name="source_of_funds"
                                        id="source_of_funds"
                                        class="form-control @error('source_of_funds') is-invalid @enderror"
                                        value="{{ old('source_of_funds', $book->source_of_funds) }}"
                                        placeholder="e.g., School Budget, PTA Fund"
                                    >
                                    @error('source_of_funds')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="purchase_price" class="form-label">Purchase Price</label>
                                    <input
                                        type="number"
                                        name="purchase_price"
                                        id="purchase_price"
                                        class="form-control @error('purchase_price') is-invalid @enderror"
                                        value="{{ old('purchase_price', $book->purchase_price) }}"
                                        min="0"
                                        step="0.01"
                                    >
                                    @error('purchase_price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: Physical Copies Details --}}
                        <div class="col-lg-7">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0"><i class="bi bi-boxes me-2"></i>Physical Copies</h6>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="deleteSelectedCopiesBtn">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" id="addCopyBtn">
                                                <i class="bi bi-plus me-1"></i>Add
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive copies-table-wrapper">
                                        <table class="table table-sm table-hover mb-0" id="copiesTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 36px;" class="text-center">
                                                        <input type="checkbox" id="selectAllCopies" class="form-check-input" title="Select all">
                                                    </th>
                                                    <th>Ctrl #</th>
                                                    <th style="width: 25%;">Year</th>
                                                    <th style="width: 20%;">Status</th>
                                                    <th style="width: 20%;">Condition</th>
                                                    <th style="width: 20%;" class="text-center">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody id="copiesContainer">
                                                @forelse($copies as $copy)
                                                    <tr data-copy-id="{{ $copy->id }}">
                                                        <td class="text-center">
                                                            <input type="checkbox" class="form-check-input copy-select" value="{{ $copy->id }}" {{ $copy->status === 'borrowed' ? 'disabled' : '' }}>
                                                        </td>
                                                        <td><input type="text" name="control_numbers[]" class="form-control form-control-sm ctrl-number" value="{{ $copy->control_number }}" readonly></td>
                                                        <td><input type="number" name="copy_year[]" class="form-control form-control-sm copy-year-input" min="1900" max="2100" value="{{ $copy->acquisition_year }}" placeholder="Enter year"></td>
                                                        <td><input type="text" name="copy_status[]" class="form-control form-control-sm" value="{{ $copy->status }}" readonly></td>
                                                        <td>
                                                            <select name="copy_condition[]" data-copy-id="{{ $copy->id }}" class="form-select form-select-sm copy-condition-select">
                                                                <option value="Brand New" {{ $copy->condition === 'Brand New' ? 'selected' : '' }}>Brand New</option>
                                                                <option value="Old" {{ $copy->condition === 'Old' ? 'selected' : '' }}>Old</option>
                                                            </select>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-danger deleteCopyBtn" data-copy-id="{{ $copy->id }}" data-book-id="{{ $book->id }}" {{ $copy->status === 'borrowed' ? 'disabled' : '' }}>
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted py-3">No copies yet. Add copies using the button above.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Add Copies Field (below table) --}}
                            {{-- <div class="mt-3">
                                <label for="copies" class="form-label">Add Number of Copies</label>
                                <input type="number" class="form-control" id="copies" name="copies" value="0" min="0" required>
                                <small class="form-text text-muted d-block mt-1">Current copies: {{ $book->copies }}. Enter a number to add more copies.</small>
                            </div>
                        </div>
                    </div> --}}

                    {{-- Form Actions (Full Width) --}}
                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Update Book</button>
                            <a href="{{ route('books.catalog') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

<style>
    /* Edit Book Layout Styles */
    .book-form-section {
        padding-bottom: 1rem;
    }

    .copies-table-wrapper {
        max-height: 500px;
        overflow-y: auto;
        border-top: 1px solid #dee2e6;
    }

    .copies-table-wrapper thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .copies-table-wrapper table {
        margin-bottom: 0;
    }

    .copies-table-wrapper tbody tr:hover {
        background-color: #f8f9fa;
    }

    .copies-table-wrapper input[readonly],
    .copies-table-wrapper select {
        font-size: 0.85rem;
    }

    .copies-table-wrapper .form-control-sm,
    .copies-table-wrapper .form-select-sm {
        min-width: 0;
    }

    /* Responsive Adjustments */
    @media (max-width: 991.98px) {
        .card.bg-light {
            margin-top: 1.5rem;
        }

        .copies-table-wrapper {
            max-height: 450px;
        }
    }

    @media (max-width: 768px) {
        .book-form-section h6 {
            font-size: 0.95rem;
        }

        .copies-table-wrapper {
            max-height: 400px;
            font-size: 0.85rem;
        }

        .copies-table-wrapper th,
        .copies-table-wrapper td {
            padding: 0.4rem !important;
        }
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category');
        const otherInput = document.getElementById('other_category');
        const addCopyBtn = document.getElementById('addCopyBtn');
        const deleteSelectedCopiesBtn = document.getElementById('deleteSelectedCopiesBtn');
        const copiesContainer = document.getElementById('copiesContainer');
        const copiesInput = document.getElementById('copies');
        const bookId = @json($book->id);
        const selectAllCopies = document.getElementById('selectAllCopies');

        function toggleOther() {
            if (categorySelect.value === 'other') {
                otherInput.style.display = 'block';
                otherInput.required = true;
                otherInput.disabled = false;
            } else {
                otherInput.style.display = 'none';
                otherInput.required = false;
                otherInput.disabled = true;
                otherInput.value = '';
            }
        }

        function syncOtherCategory() {
            const val = otherInput.value.trim();
            if (!val) return;
            let existing = Array.from(categorySelect.options).find(o => o.value === val);
            if (!existing) {
                const opt = document.createElement('option');
                opt.value = val;
                opt.text = val;
                categorySelect.add(opt, categorySelect.options[categorySelect.options.length-1]);
                categorySelect.value = val;
            }
        }

        // Handle delete copy button clicks
        copiesContainer.addEventListener('click', function(e) {
            if (e.target.closest('.deleteCopyBtn')) {
                const btn = e.target.closest('.deleteCopyBtn');
                const copyId = btn.getAttribute('data-copy-id');
                const bookIdAttr = btn.getAttribute('data-book-id');
                const controlNumber = btn.closest('tr').querySelector('.ctrl-number').value;

                if (btn.disabled) return;
                
                const label = controlNumber ? controlNumber : '(unassigned)';
                if (!confirm(`Delete copy ${label}?`)) {
                    return;
                }
                
                const formData = new FormData();
                formData.append('copy_id', copyId);
                
                fetch(`/books/${bookIdAttr}/delete-copy`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    }
                })
                .then(async response => {
                    const ct = response.headers.get('content-type') || '';
                    if (ct.includes('application/json')) return response.json();
                    const text = await response.text();
                    throw new Error(text.slice(0, 180));
                })
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + (data.error || data.message || 'Failed to delete copy'));
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Error deleting copy: ' + err.message);
                });
            }
        });

        // Select all / none
        selectAllCopies?.addEventListener('change', function() {
            const checks = Array.from(document.querySelectorAll('.copy-select'));
            checks.forEach(cb => {
                if (!cb.disabled) cb.checked = this.checked;
            });
        });

        // Bulk delete selected copies
        deleteSelectedCopiesBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            const selected = Array.from(document.querySelectorAll('.copy-select:checked'))
                .map(cb => cb.value);

            if (selected.length === 0) {
                alert('Select at least one copy to delete.');
                return;
            }

            if (!confirm(`Delete ${selected.length} selected copy/copies? This will remove them from the database.`)) {
                return;
            }

            const formData = new FormData();
            selected.forEach(id => formData.append('copy_ids[]', id));

            fetch(`/books/${bookId}/delete-copies`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                }
            })
            .then(async r => {
                const ct = r.headers.get('content-type') || '';
                if (ct.includes('application/json')) return r.json();
                const text = await r.text();
                throw new Error(text.slice(0, 180));
            })
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Deleted selected copies.');
                    location.reload();
                } else {
                    alert('Error: ' + (data.error || data.message || 'Failed to delete selected copies'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error deleting selected copies: ' + err.message);
            });
        });

        // Handle add copy button - open modal or redirect to add copies
        addCopyBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const toAdd = prompt('Enter number of copies to add (1-1000):', '1');
            if (toAdd === null) return;
            
            const count = parseInt(toAdd) || 0;
            if (count < 1 || count > 1000) {
                alert('Please enter a number between 1 and 1000');
                return;
            }
            
            // Show acquisition year input
            const acquisitionYear = prompt('Enter acquisition year (optional):', new Date().getFullYear().toString());
            
            const formData = new FormData();
            formData.append('additional_copies', count);
            if (acquisitionYear) {
                formData.append('acquisition_year', acquisitionYear);
            }
            
            fetch(`/books/${bookId}/add-copies`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json'
                }
            })
            .then(async response => {
                const ct = response.headers.get('content-type') || '';
                if (ct.includes('application/json')) return response.json();
                const text = await response.text();
                throw new Error(text.slice(0, 180));
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to add copies'));
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error adding copies: ' + err.message);
            });
        });

        // On page load, if category is custom (not in dropdown), add it as an option
        const currentCatValue = categorySelect.value;
        if (currentCatValue && currentCatValue !== 'other' && currentCatValue !== '') {
            const optionExists = Array.from(categorySelect.options).some(o => o.value === currentCatValue);
            if (!optionExists) {
                const opt = document.createElement('option');
                opt.value = currentCatValue;
                opt.text = currentCatValue;
                opt.selected = true;
                const otherOption = categorySelect.querySelector('option[value="other"]');
                categorySelect.insertBefore(opt, otherOption);
            }
        }

        categorySelect.addEventListener('change', toggleOther);
        otherInput.addEventListener('input', syncOtherCategory);

        // Handle form submission - ensure custom category is properly selected
        const formElement = document.querySelector('form');
        if (formElement) {
            formElement.addEventListener('submit', function(e) {
                const selectedValue = categorySelect.value;
                const customValue = otherInput.value.trim();
                
                // If "other" is selected, custom value is required
                if (selectedValue === 'other') {
                    if (!customValue) {
                        e.preventDefault();
                        otherInput.classList.add('is-invalid');
                        alert('Please enter a custom category');
                        return false;
                    }
                    // Find or create the option
                    let option = Array.from(categorySelect.options).find(o => o.value === customValue);
                    if (!option) {
                        const opt = document.createElement('option');
                        opt.value = customValue;
                        opt.textContent = customValue;
                        opt.text = customValue;
                        const otherOption = categorySelect.querySelector('option[value="other"]');
                        categorySelect.insertBefore(opt, otherOption);
                    }
                    // Select it
                    categorySelect.value = customValue;
                } else {
                    // If "other" is NOT selected, clear the custom value
                    otherInput.value = '';
                }
            });
        }

        toggleOther();
    });
</script>
@endpush
