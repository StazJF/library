**Purpose And Scope**
Manage library book inventory, catalog browsing, imports, copy tracking, and printable listings.

**User Stories And Flows**
- Staff can create, edit, and delete books. Source: `app/Http/Controllers/BookController.php`, `resources/views/books/*.blade.php`.
- Staff can import books from CSV. Source: `app/Http/Controllers/BookController.php`, `resources/views/books/import.blade.php`.
- Staff can browse a catalog view of available books with clear available vs. total copy counts. Source: `app/Http/Controllers/BookController.php`, `resources/views/books/catalog.blade.php`.
- Staff can add copies to an existing book, generating control numbers and creating corresponding `BookCopy` records. Source: `app/Http/Controllers/BookController.php`.
- Staff can track individual copies by control number, including status (available/borrowed/lost/damaged/repaired/found). Source: `app/Models/BookCopy.php`, `app/Http/Controllers/BookController.php`.
- Copies maintain full lifecycle: from acquisition through borrowing, loss/damage tracking, repair/recovery, and eventual removal. Source: `app/Models/BookCopy.php`, `app/Models/Book.php`.

**Entry Points**
- `/books/*` resource routes -> `BookController`. Source: `routes/web.php`.
- `/books/import`, `/books/catalog`, `/books/print`, `/books/{book}/add-copies`, `/books/api/next-control-base`. Source: `routes/web.php`.

**Key Classes And Responsibilities**
- `BookController`: CRUD, import, catalog, control number generation, copy management, and availability tracking. Source: `app/Http/Controllers/BookController.php`.
- `Book` model: SQL table `books` with SoftDeletes and derived accessors for `available_copies` and `total_copies`. Has many `BookCopy` records representing individual physical copies. Methods include `getAvailableCopies()`, `getTotalCopiesCount()`, `getBorrowedCopies()`, `getLostOrDamagedCopies()`, `markCopyAsLostOrDamaged()`, and `restoreCopy()`. Source: `app/Models/Book.php`.
- `BookCopy` model: SQL table `book_copies` tracks individual copies with fields: `control_number`, `acquisition_year`, `status`, `condition`, `is_lost_damaged`. Methods include `isAvailable()`, `isBorrowed()`, `markAsLost()`, `markAsDamaged()`, `markAsAvailable()`, `getActiveBorrow()`. Maintains full copy lifecycle. Source: `app/Models/BookCopy.php`.

**Data Model**
- `books` table stores bibliographic fields (`title`, `author`, `isbn`, `status`, `category`, `publisher`, etc.) and copy count fields (`copies`, `available_copies`). The `control_numbers` JSON field is legacy; new systems use `book_copies` table. Source: `app/Models/Book.php`.
- `book_copies` table stores individual physical copies with `book_id` (FK), unique `control_number`, `acquisition_year`, `status` (available/borrowed/lost/damaged/repaired/found), `condition`, and `is_lost_damaged` flag. This is the source of truth for copy tracking. Source: `app/Models/BookCopy.php`.
- Backward Compatibility: `Book` accessors (`available_copies`, `total_copies`) check `book_copies` table first, then fall back to legacy JSON arrays if needed. This ensures smooth operation during and after migration.

**Validation And Authorization**
- Book create/update enforce title, author, ISBN, category, copies, and other optional metadata. Source: `app/Http/Controllers/BookController.php`.
- CSV import validates file type and row requirements. Source: `app/Http/Controllers/BookController.php`.

**Side Effects**
- Activity logs for book add, update, delete, import, and copy updates. Source: `app/Http/Controllers/BookController.php`, `app/Models/ActivityLog.php`.
- `BookCopy` records created automatically when books are created or copies are added. Source: `app/Http/Controllers/BookController.php` (`store()`, `update()`, `addCopies()` methods).
- `BookCopy` records deleted (soft delete via BookArchive) when individual copies are removed. Source: `app/Http/Controllers/BookController.php` (`deleteCopy()` method).
- Cache key `ctrl_base` is used to generate unique control numbers. Source: `app/Http/Controllers/BookController.php`.
- Book's `copies` and `available_copies` fields synced whenever `BookCopy` records change. Source: `app/Models/Book.php` accessors.

**Config And Env Dependencies**
- Cache store used for control number base. Source: `config/cache.php`.

**Error Cases And Edge Cases**
- Duplicate ISBN is rejected during create and import. Source: `app/Http/Controllers/BookController.php`.
- Import returns warnings for missing fields or duplicate ISBNs. Source: `app/Http/Controllers/BookController.php`.
- Duplicate `control_number` is rejected; each copy must have a unique identifier. Source: `app/Models/BookCopy.php`.
- Marking a copy as lost/damaged creates a `LostDamagedItem` record and changes its `BookCopy.is_lost_damaged` flag to exclude from availability count. Source: `app/Http/Controllers/BookController.php`, `app/Models/BookCopy.php`.
- Restoring a copy from lost/damaged status updates `BookCopy` status back to available and marks the `LostDamagedItem` as resolved. Source: `app/Models/Book.php` (`restoreCopy()` method).
- Available copies count excludes borrowed, lost, or damaged copies. Only copies with `status='available' AND is_lost_damaged=false` are counted. Source: `app/Models/Book.php` (`getAvailableCopies()` accessor).

**Where To Start Reading Code**
1. `app/Http/Controllers/BookController.php`
2. `app/Models/Book.php`
3. `app/Models/BookCopy.php`
4. `resources/views/books/*.blade.php`

**Related Docs**
- documentation/03-backend/routing-map.md`n- documentation/04-frontend/pages-components.md`n- documentation/05-api/api-map.md`n

