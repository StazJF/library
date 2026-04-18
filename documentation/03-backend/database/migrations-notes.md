**Migration Notes**
- Migrations define the full SQL schema used by the app. Sources: `database/migrations/*`, `config/database.php`.
- Tables include users, system_users, books, book_copies, borrows, lost_damaged_items, lost_damaged_item_histories, teachers, distributed_books, activity_logs, penalty_settings, cache, and queue tables. Sources: `database/migrations/*`.
- Activity logs link back to system users via a nullable foreign key. Source: `database/migrations/2025_11_29_063414_create_activity_logs_table.php`.

**Recent Migrations (March 25 - April 2, 2026)**

**Book Copies Normalization Suite** (March 25, 2026)
- `2026_03_25_000000_create_book_copies_table.php` - Creates `book_copies` table to normalize individual book copies. Each copy has unique `control_number`, `acquisition_year`, `status`, `condition`, and `is_lost_damaged` flag. Indexes on `book_id`, `status`, `is_lost_damaged` for query performance.
- `2026_03_25_000001_migrate_book_copies_data.php` - Migrates existing JSON array data from `books.control_numbers` to individual `book_copies` records. Preserves all copy metadata and related status information.
- `2026_03_25_000002_add_book_copy_id_to_borrows_table.php` - Adds `book_copy_id` foreign key to `borrows` table for direct linking of borrow transactions to specific copies.
- `2026_03_25_000003_populate_book_copy_id_in_borrows.php` - Populates `book_copy_id` in existing borrow records by matching control numbers for backward compatibility.

**Lost and Damaged Item Tracking Suite** (Earlier, assumed complete)
- `*_create_lost_damaged_items_table.php` - Creates `lost_damaged_items` table with `borrow_id`, `book_id`, `type` (lost/damaged), `status` (active/returned/replaced), and `is_resolved` flag.
- `*_create_lost_damaged_item_histories_table.php` - Creates `lost_damaged_item_histories` table with non-destructive action logging. Each state transition creates a new record with `action` (created/repaired/returned/etc.), `remarks`, and `created_by` audit trail.

**Migration Order and Deployment**
Run `php artisan migrate` after configuring MySQL in `.env`. Migrations execute in chronological order:
1. Foundation tables (users, books, etc.)
2. BookCopy normalization suite (brings copy data into structured tables)
3. Lost/damaged item tables (for tracking item status transitions)

This ensures foreign key dependencies are satisfied and data integrity is maintained throughout the migration process.
