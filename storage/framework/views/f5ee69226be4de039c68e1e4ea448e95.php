<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <style>
        .btn-outline-primary { border-color: #93c5fd; color: #3b82f6; background: transparent; transition: all .12s ease; }
        .btn-outline-primary:hover { background: #eff6ff; border-color: #3b82f6; }
        .btn-outline-primary.active { background: #3b82f6; color: #fff; border-color: #3b82f6; }
    </style>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h4 class="mb-1">Borrow for Distribution</h4>
            <p class="text-muted mb-0">Issue distribution books to teachers</p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="container py-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">

                    <h4 class="mb-1">Borrow Distributed Books</h4>
                    <p class="text-muted mb-4">Select teacher and distribution books with quantities to borrow.</p>

                    <form id="borrowDistributeForm" action="<?php echo e(route('borrow.distribute.store')); ?>" method="POST">
                        <?php echo csrf_field(); ?>

                        <h6 class="fw-bold">Teacher Information</h6>
                        <p class="text-muted small">Search teacher by their name to borrow books.</p>

                        <div class="mb-3">
                            <label class="form-label">Select Teacher</label>
                            <select id="student_select_dist" name="user_id" class="form-select" required>
                                <option value="" selected disabled>Select teacher...</option>
                                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $active = (int) ($user->active_distribution_borrows_count ?? 0);
                                        $limit = $maxDistributionBorrows ?? null;
                                        $atLimit = $limit !== null ? ($active >= (int) $limit) : false;
                                    ?>
                                    <option value="<?php echo e($user->_id ?? $user->id); ?>" <?php if($atLimit): ?> disabled <?php endif; ?>>
                                        <?php echo e($user->name ?? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))); ?>

                                        <?php if($active > 0): ?>
                                            (<?php echo e($active); ?> active distribution)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php if(($maxDistributionBorrows ?? null) !== null): ?>
                                <div class="form-text text-muted">Borrowers with <?php echo e($maxDistributionBorrows); ?> active distribution borrows are disabled until they return a book.</div>
                            <?php else: ?>
                                <div class="form-text text-muted">Distribution borrowing does not affect personal borrowing limits.</div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Borrow Date</label>
                                <input type="date" name="borrowed_at" class="form-control" value="<?php echo e(date('Y-m-d')); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Due Date</label>
                                <input type="date" name="due_date" class="form-control" value="<?php echo e(date('Y-m-d', strtotime('+12 months'))); ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="fw-bold">Distribution Book Information</h6>
                        <p class="text-muted small">Select distribution books and add to list.</p>

                        <div class="mb-3">
                            <label class="form-label">Select Book and Quantity</label>
                            <div class="d-flex gap-2">
                                <select id="dist_book_select" class="form-select">
                                    <option value="" selected disabled>Select a book...</option>
                                    <?php $__currentLoopData = $books; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php
                                            // Get accurate counts from the book model accessors
                                            $available = $book->available_copies;
                                            $total = $book->total_copies;
                                            $availableCtrls = $book->copies()
                                                ->where('status', 'available')
                                                ->where('is_lost_damaged', false)
                                                ->whereNotNull('control_number')
                                                ->orderBy('control_number')
                                                ->pluck('control_number')
                                                ->toArray();
                                        ?>
                                        <option value="<?php echo e($book->_id ?? $book->id); ?>" 
                                                        data-title="<?php echo e($book->title); ?>" 
                                                        data-author="<?php echo e($book->author); ?>" 
                                                        data-available-copies="<?php echo e($available); ?>" 
                                                        data-total-copies="<?php echo e($total); ?>"
                                                        data-control-numbers='<?php echo json_encode($availableCtrls, 15, 512) ?>'>
                                            <?php echo e($book->title); ?> (<?php echo e($available); ?>/<?php echo e($total); ?> available)
                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <input type="number" id="dist_book_qty" class="form-control" style="max-width: 100px;" min="1" value="1" placeholder="Qty">
                                <button id="addDistBookBtn" type="button" class="btn btn-secondary">Add Book</button>
                            </div>
                            <small id="stockWarning" class="text-danger" style="display: none;">This book is out of stock.</small>
                        </div>

                        <!-- Control Number Selection for Distribution -->
                        <div class="mb-3" id="distControlNumberSection" style="display: none;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <label class="form-label mb-0">Select Copies (Ctrl#)</label>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="selectAllCopies">
                                    <label class="form-check-label" for="selectAllCopies" style="font-weight: 500; cursor: pointer;">Select All</label>
                                </div>
                            </div>
                            <div id="distControlNumberCheckboxes" class="d-flex flex-wrap gap-3"></div>
                            <small class="text-muted">Click the checkboxes of the copies you want to add.</small>
                        </div>

                        <div id="cartList" class="border rounded p-3 mb-3" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; max-height: 400px; overflow-y: auto; min-height: 100px; align-content: start;">
                            <div class="text-muted small" style="grid-column: 1 / -1;">No books added yet.</div>
                        </div>
                        <div id="hiddenInputs"></div>

                        <div class="d-flex gap-2 mb-4">
                            <button id="confirmBtn" type="button" class="btn btn-primary flex-grow-1">Confirm (0)</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const distBookSelect = document.getElementById('dist_book_select');
                const distBookQty = document.getElementById('dist_book_qty');
                const addDistBookBtn = document.getElementById('addDistBookBtn');
                const cartList = document.getElementById('cartList');
                const hiddenInputs = document.getElementById('hiddenInputs');
                const confirmBtn = document.getElementById('confirmBtn');
                const distControlNumberSection = document.getElementById('distControlNumberSection');
                const distControlNumberCheckboxes = document.getElementById('distControlNumberCheckboxes');

                let cart = [];
                let selectedCopies = [];

                function renderCart() {
                    hiddenInputs.innerHTML = '';
                    if (cart.length === 0) {
                        cartList.innerHTML = '<div class="text-muted small" style="grid-column: 1 / -1;">No books added yet.</div>';
                        confirmBtn.textContent = 'Confirm (0)';
                        return;
                    }

                    let totalCount = 0;
                    cart.forEach(c => totalCount += c.controlNumbers.length);
                    confirmBtn.textContent = `Confirm (${totalCount})`;
                    
                    cartList.innerHTML = '';
                    
                    cart.forEach((c, idx) => {
                        const card = document.createElement('div');
                        card.className = 'card border shadow-sm h-100';
                        card.style.display = 'flex';
                        card.style.flexDirection = 'column';
                        
                        const ctrlsDisplay = c.controlNumbers.join(', ');
                        card.innerHTML = `
                            <div class="card-body d-flex flex-column p-3" style="flex: 1;">
                                <h6 class="card-title mb-1 fw-bold" style="font-size: 0.95rem; line-height: 1.3;">${c.title}</h6>
                                <small class="text-muted mb-2">${c.author}</small>
                                <div class="mb-2">
                                    <span class="badge bg-primary">${c.controlNumbers.length}x</span>
                                </div>
                                <small class="text-muted mb-3" style="font-size: 0.85rem;">
                                    <strong>Ctrl#:</strong><br>${ctrlsDisplay}
                                </small>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-auto" style="font-size: 0.85rem;">Remove</button>
                            </div>
                        `;
                        
                        const removeBtn = card.querySelector('button');
                        removeBtn.addEventListener('click', () => {
                            cart.splice(idx, 1);
                            renderCart();
                        });
                        
                        cartList.appendChild(card);

                        // Create hidden inputs for each control number
                        c.controlNumbers.forEach(ctrl => {
                            const bookInput = document.createElement('input');
                            bookInput.type = 'hidden';
                            bookInput.name = 'book_ids[]';
                            bookInput.value = c.id;
                            hiddenInputs.appendChild(bookInput);

                            const ctrlInput = document.createElement('input');
                            ctrlInput.type = 'hidden';
                            ctrlInput.name = 'copy_numbers[]';
                            ctrlInput.value = ctrl;
                            hiddenInputs.appendChild(ctrlInput);
                        });
                    });
                }

                const userSelect = document.querySelector('select[name="user_id"]');

                // Build control number map for books
                const bookControlMap = {};
                document.querySelectorAll('#dist_book_select option').forEach(opt => {
                    if (!opt.value) return;
                    bookControlMap[opt.value] = {
                        title: opt.dataset.title || '',
                        author: opt.dataset.author || '',
                        controlNumbers: opt.dataset.controlNumbers ? JSON.parse(opt.dataset.controlNumbers) : []
                    };
                });

                // Update stock warning and show control numbers when book selection changes
                distBookSelect.addEventListener('change', function(){
                    const availableCopies = parseInt(this.selectedOptions[0]?.dataset.availableCopies || 0);
                    const stockWarning = document.getElementById('stockWarning');
                    if (availableCopies < 1) {
                        stockWarning.style.display = 'block';
                        distControlNumberSection.style.display = 'none';
                    } else {
                        stockWarning.style.display = 'none';
                        
                        // Show available control numbers as checkboxes
                        const bookId = this.value;
                        const bookData = bookControlMap[bookId];
                        if (bookData && bookData.controlNumbers.length > 0) {
                            distControlNumberSection.style.display = 'block';
                            distControlNumberCheckboxes.innerHTML = '';
                            selectedCopies = [];
                            
                            bookData.controlNumbers.forEach((ctrl) => {
                                const checkboxDiv = document.createElement('div');
                                checkboxDiv.className = 'form-check';
                                
                                const checkbox = document.createElement('input');
                                checkbox.className = 'form-check-input';
                                checkbox.type = 'checkbox';
                                checkbox.id = `ctrl_${ctrl}`;
                                checkbox.value = ctrl;
                                checkbox.dataset.controlNumber = ctrl;
                                
                                const label = document.createElement('label');
                                label.className = 'form-check-label';
                                label.htmlFor = `ctrl_${ctrl}`;
                                label.textContent = ctrl;
                                
                                checkboxDiv.appendChild(checkbox);
                                checkboxDiv.appendChild(label);
                                distControlNumberCheckboxes.appendChild(checkboxDiv);
                                
                                // Add change listener to each checkbox to update Select All state
                                checkbox.addEventListener('change', updateSelectAllState);
                            });
                            
                            // Reset Select All checkbox
                            document.getElementById('selectAllCopies').checked = false;
                        } else {
                            distControlNumberSection.style.display = 'none';
                        }
                    }
                    // Set max quantity to available copies
                    distBookQty.max = Math.max(1, availableCopies);
                });

                // Handle Select All checkbox
                const selectAllCopies = document.getElementById('selectAllCopies');
                
                function updateSelectAllState() {
                    const checkboxes = distControlNumberCheckboxes.querySelectorAll('input[type="checkbox"]');
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    const someChecked = Array.from(checkboxes).some(cb => cb.checked);
                    selectAllCopies.checked = allChecked;
                    selectAllCopies.indeterminate = someChecked && !allChecked;
                }
                
                selectAllCopies.addEventListener('change', function(e) {
                    e.preventDefault();
                    const checkboxes = distControlNumberCheckboxes.querySelectorAll('input[type="checkbox"]');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                    this.indeterminate = false;
                });

                addDistBookBtn.addEventListener('click', function(){
                    const id = distBookSelect.value; 
                    if (!id) return alert('Select a book');
                    
                    const opt = distBookSelect.selectedOptions[0];
                    const availableCopies = parseInt(opt.dataset.availableCopies || 0);
                    const bookData = bookControlMap[id];
                    
                    if (availableCopies < 1) {
                        return alert(`${opt.dataset.title} is out of stock`);
                    }

                    // Get all checked copies from checkboxes
                    let ctrlsToUse = [];
                    if (bookData && bookData.controlNumbers.length > 0) {
                        const checkedBoxes = Array.from(distControlNumberCheckboxes.querySelectorAll('input[type="checkbox"]:checked'));
                        if (checkedBoxes.length === 0) {
                            return alert('Please select at least one copy (Ctrl#)');
                        }
                        ctrlsToUse = checkedBoxes.map(cb => cb.value);
                    } else {
                        ctrlsToUse = ['N/A'];
                    }
                    
                    // Add all selected copies to cart
                    ctrlsToUse.forEach(ctrl => {
                        // Check if this specific copy is already in cart
                        const existing = cart.find(c => c.id === id && c.controlNumbers.includes(ctrl));
                        if (!existing) {
                            cart.push({ 
                                id, 
                                title: opt.dataset.title, 
                                author: opt.dataset.author,
                                controlNumbers: [ctrl]
                            });
                        }
                    });
                    
                    distBookSelect.value = '';
                    distControlNumberSection.style.display = 'none';
                    distControlNumberCheckboxes.innerHTML = '';
                    selectedCopies = [];
                    renderCart();
                });

                confirmBtn.addEventListener('click', function(){
                    if (cart.length === 0) return alert('Add at least one book');
                    if (!document.querySelector('select[name="user_id"]').value) return alert('Select a teacher');
                    document.getElementById('borrowDistributeForm').submit();
                });
            });
        </script>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\jimmu\Herd\library\resources\views/borrow/distribute.blade.php ENDPATH**/ ?>