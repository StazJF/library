**Data Model Overview**
This app uses SQL tables via Eloquent models, with MySQL configured as the default connection. Sources: `app/Models/*`, `config/database.php`.

**Tables and Models**
- `books` (model `Book`): fields include `title`, `author`, `isbn`, `status`, `category`, `copies`, `available_copies`, `publisher`, `edition`, `pages`, `source_of_funds`, `cost_price`, `published_year`, `purchase_price`, `acquisition_type`, `condition`, `copy_status`, `call_number`, `dewey_decimal`, `cutter_number`, `control_numbers` (JSON, legacy). Soft deletes enabled. Has many `BookCopy` records. Source: `app/Models/Book.php`.
- `book_copies` (model `BookCopy`): fields include `book_id`, `control_number` (unique), `acquisition_year`, `status` (available/borrowed/lost/damaged/found/repaired), `condition`, `is_lost_damaged` (boolean flag). Tracks individual book copies with full lifecycle management. Source: `app/Models/BookCopy.php`.
- `borrows` (model `Borrow`): fields include `user_id`, `book_id`, `book_copy_id` (FK to specific copy), `borrowed_at`, `due_date`, `returned_at`, `return_status` (pending/returned_on_time/late_return/damaged_for_repair/lost_and_found/repaired/found), `remark`, `notes`, `role`. Links to `LostDamagedItem` when applicable. Source: `app/Models/Borrow.php`.
- `lost_damaged_items` (model `LostDamagedItem`): fields include `borrow_id`, `book_id`, `type` (lost/damaged), `status` (active/returned/replaced), `is_resolved` (boolean). Tracks when items are marked lost or damaged. Source: `app/Models/LostDamagedItem.php`.
- `lost_damaged_item_histories` (model `LostDamagedItemHistory`): fields include `lost_damaged_item_id`, `action` (created/repaired/returned/resolved/replaced/pending/forwarded), `remarks`, `created_by` (FK to system_users). Non-destructive audit trail of all status transitions. Source: `app/Models/LostDamagedItemHistory.php`.
- `users` (model `User`): fields include `name`, `email`, `gender`, `address`, `phone_number`, `role`, `first_name`, `last_name`, `grade_section`, `lrn`, `borrowed`, `remark`. Soft deletes enabled. Source: `app/Models/User.php`.
- `system_users` (model `SystemUser`): fields include `email`, `password`, `role`. Soft deletes enabled. Source: `app/Models/SystemUser.php`.
- `teachers` (model `Teacher`): fields include `name`, `first_name`, `last_name`, `gender`, `address`, `phone_number`, `email`, `remark`. Soft deletes enabled. Source: `app/Models/Teacher.php`.
- `distributed_books` (model `DistributedBook`): fields include `title`, `author`, `publisher`, `isbn`, `category`, `copies`, `available_copies`, `status`, `edition`, `pages`, `source_of_funds`, `cost_price`, `year`, `condition`. Soft deletes enabled. Source: `app/Models/DistributedBook.php`.
- `activity_logs` (model `ActivityLog`): fields include `user_id`, `action`, `target_type`, `target_id`, `details`. Source: `app/Models/ActivityLog.php`.
- `penalty_settings` (model `PenaltySetting`): fields include `borrow_days_allowed`, `penalty_per_day`. Source: `app/Models/PenaltySetting.php`.

**Relationships**
- `Book` has many `BookCopy` records via `copies()` relationship. Source: `app/Models/Book.php`.
- `Book` has many `Borrow` records. Source: `app/Models/Book.php`.
- `BookCopy` belongs to `Book` via `book()` relationship. Source: `app/Models/BookCopy.php`.
- `BookCopy` has many `Borrow` records via `borrows()` relationship. Source: `app/Models/BookCopy.php`.
- `Borrow` belongs to `User` and `Book` and `BookCopy` (optional, for specific copy tracking). Source: `app/Models/Borrow.php`.
- `Borrow` has one `LostDamagedItem` via `lostDamagedItem()` relationship. Source: `app/Models/Borrow.php`.
- `LostDamagedItem` belongs to `Borrow`, `Book`, and has many `LostDamagedItemHistory` records. Source: `app/Models/LostDamagedItem.php`.
- `LostDamagedItemHistory` belongs to `LostDamagedItem` and `SystemUser` (created_by). Source: `app/Models/LostDamagedItemHistory.php`.
- `User` has many `Borrow` records. Source: `app/Models/User.php`.
- `Teacher` has many `Borrow` records (via `user_id`). Source: `app/Models/Teacher.php`.
- `ActivityLog` belongs to `SystemUser` as the actor. Source: `app/Models/ActivityLog.php`.

**Constraints**
- `book_copies.book_id` references `books.id` with cascade delete. Source: `database/migrations/2026_03_25_000000_create_book_copies_table.php`.
- `book_copies.control_number` is unique across the system. Source: `database/migrations/2026_03_25_000000_create_book_copies_table.php`.
- `borrows.book_copy_id` references `book_copies.id` (nullable, for backward compatibility). Source: `database/migrations/2026_03_25_000002_add_book_copy_id_to_borrows_table.php`.
- `lost_damaged_items.borrow_id` references `borrows.id` with cascade delete. Source: `database/migrations/*_create_lost_damaged_items_table.php`.
- `lost_damaged_item_histories.lost_damaged_item_id` references `lost_damaged_items.id` with cascade delete. Source: `database/migrations/*_create_lost_damaged_item_histories_table.php`.
- `lost_damaged_item_histories.created_by` references `system_users.id` (nullable, `nullOnDelete`). Source: `database/migrations/*_create_lost_damaged_item_histories_table.php`.
- `activity_logs.user_id` references `system_users.id` (nullable, `nullOnDelete`). Source: `database/migrations/2025_11_29_063414_create_activity_logs_table.php`.
