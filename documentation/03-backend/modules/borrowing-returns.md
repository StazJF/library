**Purpose And Scope**
Handle borrowing and returning of books for students and teachers, including receipts and remarks.

**User Stories And Flows**
- Staff can borrow books for students with a limit of 3 active borrows. Each borrow links to a specific `BookCopy` for accurate tracking. Source: `app/Http/Controllers/BorrowController.php`.
- Staff can borrow books for teachers from the distribution workflow. Source: `app/Http/Controllers/BorrowController.php`.
- Staff can process returns and apply remarks and notes. Return status tracks the state: pending, returned_on_time, late_return, damaged_for_repair, lost_and_found, repaired, or found. Source: `app/Http/Controllers/BorrowController.php`.
- Staff can mark borrowed items as lost or damaged, creating a `LostDamagedItem` record and starting an audit trail in `LostDamagedItemHistory`. Source: `app/Http/Controllers/BorrowController.php`.
- Staff can mark items as repaired or found (recovered), with status transitions logged non-destructively in history. Source: `app/Http/Controllers/BorrowController.php`.
- Receipts can be printed for a borrow record. Source: `app/Http/Controllers/BorrowController.php`, `resources/views/borrow/receipt.blade.php`.
- All Transactions Reports view displays status transitions (Damaged → Repaired, Lost → Found) with visual indicators. Source: `app/Http/Controllers/DashboardController.php`, `resources/views/reports.blade.php`.

**Entry Points**
- `/borrow/*` routes -> `BorrowController` and `TeacherBorrowController`. Source: `routes/web.php`.

**Key Classes And Responsibilities**
- `BorrowController`: student borrow (store, return), teacher distribution borrow, return processing, lost/damaged marking, repair/recovery actions, receipts. Source: `app/Http/Controllers/BorrowController.php`.
- `TeacherBorrowController`: teacher borrow flow and returns. Source: `app/Http/Controllers/TeacherBorrowController.php`.
- `DashboardController`: reports() method with enhanced transaction status tracking via eager loading of `lostDamagedItem` and histories. Source: `app/Http/Controllers/DashboardController.php`.
- `Borrow` model: stores borrow records with `book_copy_id` (specific copy), `return_status` (transaction status), and relationships to `LostDamagedItem`. Methods include `getTransactionStatus()`, `getTransactionStatusLabel()`, `isLostOrDamaged()`, `getLossType()`. Status constants: `STATUS_PENDING`, `STATUS_RETURNED_ON_TIME`, `STATUS_LATE_RETURN`, `STATUS_DAMAGED_FOR_REPAIR`, `STATUS_LOST_AND_FOUND`, `STATUS_REPAIRED`, `STATUS_FOUND`. Source: `app/Models/Borrow.php`.
- `BookCopy` model: synced with borrow status (available/borrowed/lost/damaged/repaired/found). Updated when items are borrowed, returned, marked lost/damaged, or repaired/found. Source: `app/Models/BookCopy.php`.
- `LostDamagedItem` model: represents items marked as lost or damaged. Stores `type` (lost/damaged) and `status` (active/returned/replaced). Source: `app/Models/LostDamagedItem.php`.
- `LostDamagedItemHistory` model: non-destructive audit trail. Each action creates a new record with `action` (created/repaired/returned/etc.), `remarks`, `created_by`. Previous records are never overwritten. Source: `app/Models/LostDamagedItemHistory.php`.

**Data Model**
- `borrows` table with `user_id` (borrower), `book_id` (book), `book_copy_id` (FK to specific `BookCopy`), `borrowed_at`, `due_date`, `returned_at`, `return_status` (tracks transaction state), `remark`, `notes`, `role`. Source: `app/Models/Borrow.php`.
- `lost_damaged_items` table links to `borrows.id` and tracks when items are marked lost or damaged. Stores `type` (lost/damaged) and `status` (active/returned/replaced). Source: `app/Models/LostDamagedItem.php`.
- `lost_damaged_item_histories` table provides non-destructive audit trail. Each state change (damaged, repaired, lost, found, etc.) creates a new record with `action`, `remarks`, and `created_by` (staff member who took action). Previous records remain unchanged. Source: `app/Models/LostDamagedItemHistory.php`.
- `BookCopy` records updated on borrow/return to reflect current status. A copy's status field transitions: available → borrowed → (returned OR lost/damaged → repaired/found). Source: `app/Models/BookCopy.php`.

**Validation And Authorization**
- Borrow validation includes user, dates, and max book count (3 active borrows per student). Source: `app/Http/Controllers/BorrowController.php`.
- Return validation restricts `return_status` to allowed values and `notes` length. Source: `app/Http/Controllers/BorrowController.php`, `app/Http/Controllers/TeacherBorrowController.php`.
- Lost/Damaged marking requires valid `book_id`, `borrow_id`, and `type` (lost/damaged). Source: `app/Http/Controllers/BorrowController.php`.
- Repair/Recovery actions require existing `LostDamagedItem` record and valid action type. Source: `app/Http/Controllers/BorrowController.php`.

**Side Effects**
- Updates `BookCopy.status` on borrow (available → borrowed) and return (borrowed → available). Source: `app/Http/Controllers/BorrowController.php`, `app/Models/BookCopy.php`.
- Updates `Book.available_copies` count when copies are borrowed/returned. Source: `app/Models/Book.php`.
- Writes activity logs for all borrow and return events. Source: `app/Http/Controllers/BorrowController.php`, `app/Models/ActivityLog.php`.
- Creates `LostDamagedItem` record and initiates `LostDamagedItemHistory` when item is marked lost/damaged. Source: `app/Http/Controllers/BorrowController.php`.
- Updates `BookCopy.is_lost_damaged=true` and status when marked as lost or damaged. Excludes from available count. Source: `app/Models/BookCopy.php`.
- Creates new `LostDamagedItemHistory` entry (non-destructive) when item is repaired or found, changing both `LostDamagedItem.status` and `BookCopy` state back to available. Source: `app/Http/Controllers/BorrowController.php`.
- Updates borrower remark field when return is late or has notes. Source: `app/Http/Controllers/BorrowController.php`.

**Config And Env Dependencies**
- Penalty settings are read via the `penalty_settings` table. Source: `app/Http/Controllers/BorrowController.php`.

**Error Cases And Edge Cases**
- Borrow fails when a book is unavailable (no available copies) or when a borrower exceeds the limit (3 active borrows). Source: `app/Http/Controllers/BorrowController.php`.
- Return processing supports multiple borrow IDs with quantity limits. Source: `app/Http/Controllers/BorrowController.php`.
- Marking an item as lost/damaged requires valid `LostDamagedItem` entry; duplicate entries are prevented by checking existing records. Source: `app/Http/Controllers/BorrowController.php`.
- Repairing or marking as found requires the `LostDamagedItem` to be in active status. A new history entry is created; the previous entry remains. Source: `app/Http/Controllers/BorrowController.php`.
- If a copy is borrowed again after being lost/damaged/repaired, a new borrow record is created with updated status tracking. Source: `app/Models/Borrow.php`, `app/Models/BookCopy.php`.

**Where To Start Reading Code**
1. `app/Http/Controllers/BorrowController.php`
2. `app/Http/Controllers/DashboardController.php` (reports method)
3. `app/Http/Controllers/TeacherBorrowController.php`
4. `app/Models/Borrow.php`
5. `app/Models/BookCopy.php`
6. `app/Models/LostDamagedItem.php`
7. `app/Models/LostDamagedItemHistory.php`
8. `resources/views/borrow/*.blade.php`
9. `resources/views/reports.blade.php`

**Related Docs**
- documentation/03-backend/routing-map.md`n- documentation/04-frontend/pages-components.md`n- documentation/05-api/api-map.md`n
