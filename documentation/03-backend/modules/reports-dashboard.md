**Purpose And Scope**
Provide dashboard summaries and report metrics for system usage.

**User Stories And Flows**
- Staff can view a dashboard with totals, due soon borrows, available books, and popular book counts. Source: `app/Http/Controllers/DashboardController.php`, `resources/views/dashboard.blade.php`.
- Staff can view All Transactions Reports showing transaction history with clear status indicators including lost/damaged state, repairs, and recovery. Source: `app/Http/Controllers/DashboardController.php`, `resources/views/reports.blade.php`.
- Staff can view transaction status including: pending, returned on time, late returns, damaged items (with repair status), lost items (with recovery status). Source: `app/Http/Controllers/DashboardController.php`.
- Report rows highlight items that are currently damaged or lost (yellow background) to draw attention to active inventory issues. Source: `resources/views/reports.blade.php`.
- Report status badges display with color coding and icons (Tools for damaged, Check-circle for repaired, Exclamation for lost, Search for found). Source: `resources/views/reports.blade.php`.

**Entry Points**
- `/dashboard` -> `DashboardController@index`. Source: `routes/web.php`.
- `/reports` -> `DashboardController@reports`. Source: `routes/web.php`.

**Key Classes And Responsibilities**
- `DashboardController`: aggregates counts and lists for views. The `reports()` method implements eager loading of `lostDamagedItem` with histories, enriches transaction data with status information, and applies pagination. Source: `app/Http/Controllers/DashboardController.php`.
- `Borrow` model: implements transaction status determination via `getTransactionStatus()`, `getTransactionStatusLabel()`, `isLostOrDamaged()`, and `getLossType()` methods. Examines related `LostDamagedItem` records and their history to determine current state. Source: `app/Models/Borrow.php`.
- `LostDamagedItem` model: tracks items marked as lost or damaged, with relationships to `LostDamagedItemHistory` for audit trail. Source: `app/Models/LostDamagedItem.php`.
- `LostDamagedItemHistory` model: non-destructive logging of all status transitions. Each action (created, repaired, returned, etc.) creates a new record without modifying previous entries. Source: `app/Models/LostDamagedItemHistory.php`.

**Data Model**
- Uses `Book`, `User`, `Borrow`, and `Teacher` models for counts and charts. Source: `app/Models/*`, `app/Http/Controllers/DashboardController.php`.
- `Borrow` model extended with transaction status tracking: `return_status` field stores current status (pending, returned_on_time, late_return, damaged_for_repair, lost_and_found, repaired, found).
- `LostDamagedItem` model tracks items marked as lost or damaged, linked to specific borrow transactions. Stores `type` (lost/damaged) and `status` (active/returned/replaced).
- `LostDamagedItemHistory` table provides complete audit trail. Each status change creates a new record with `action`, `remarks`, and `created_by`. Previous records remain unchanged and are always accessible.
- Report enrichment adds computed fields to each transaction: `transaction_status` (final status including transitions), `transaction_status_label` (human-readable), `transaction_loss_type` (current loss/damage state), `is_lost_or_damaged` (boolean for UI logic).

**Validation And Authorization**
- No additional validation beyond auth middleware. Source: `routes/web.php`.
- Reports are read-only views; status modifications (marking lost/damaged, repairs, etc.) are handled via separate BorrowController actions. Source: `app/Http/Controllers/BorrowController.php`.

**Side Effects**
- The `reports()` method eager-loads `lostDamagedItem` with `histories` to minimize database queries. Source: `app/Http/Controllers/DashboardController.php`.
- Each transaction in the report is enriched with computed transaction status information using `Borrow` model methods. Source: `app/Http/Controllers/DashboardController.php`.
- Row styling in reports is based on `is_lost_or_damaged` flag: affected items display with yellow background for visibility. Source: `resources/views/reports.blade.php`.
- Status badges are colored and styled based on status constants (red for damaged, blue for repaired, orange for lost, green for found). Source: `resources/views/reports.blade.php`.

**Where To Start Reading Code**
1. `app/Http/Controllers/DashboardController.php` (especially `reports()` method)
2. `app/Models/Borrow.php` (status determination methods)
3. `app/Models/LostDamagedItem.php`
4. `app/Models/LostDamagedItemHistory.php`
5. `resources/views/reports.blade.php` (status display and styling)
6. `resources/views/dashboard.blade.php`

**Related Docs**
- documentation/03-backend/routing-map.md`n- documentation/04-frontend/pages-components.md`n- documentation/05-api/api-map.md`n
