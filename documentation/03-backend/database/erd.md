**ERD (MySQL)**

```mermaid
erDiagram
    USERS {
        BIGINT id PK
        VARCHAR first_name
        VARCHAR last_name
        VARCHAR name
        VARCHAR email
        VARCHAR gender
        VARCHAR grade_section
        VARCHAR lrn
        VARCHAR phone_number
        TEXT address
        INT borrowed
        VARCHAR role
        VARCHAR remark
        TIMESTAMP created_at
        TIMESTAMP updated_at
        TIMESTAMP deleted_at
    }

    SYSTEM_USERS {
        BIGINT id PK
        VARCHAR email
        VARCHAR password
        VARCHAR role
        TIMESTAMP created_at
        TIMESTAMP updated_at
        TIMESTAMP deleted_at
    }

    TEACHERS {
        BIGINT id PK
        VARCHAR name
        VARCHAR first_name
        VARCHAR last_name
        VARCHAR gender
        VARCHAR address
        VARCHAR phone_number
        VARCHAR email
        VARCHAR remark
        TIMESTAMP created_at
        TIMESTAMP updated_at
        TIMESTAMP deleted_at
    }

    BOOKS {
        BIGINT id PK
        VARCHAR title
        VARCHAR author
        VARCHAR publisher
        VARCHAR isbn
        VARCHAR category
        INT copies
        INT available_copies
        VARCHAR status
        VARCHAR edition
        INT pages
        VARCHAR source_of_funds
        DECIMAL cost_price
        INT published_year
        DECIMAL purchase_price
        VARCHAR acquisition_type
        VARCHAR condition
        VARCHAR copy_status
        VARCHAR call_number
        VARCHAR dewey_decimal
        VARCHAR cutter_number
        JSON control_numbers
        TIMESTAMP created_at
        TIMESTAMP updated_at
        TIMESTAMP deleted_at
    }

    BOOK_COPIES {
        BIGINT id PK
        BIGINT book_id FK
        VARCHAR control_number UK
        INT acquisition_year
        VARCHAR status
        VARCHAR condition
        BOOLEAN is_lost_damaged
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    DISTRIBUTED_BOOKS {
        BIGINT id PK
        VARCHAR title
        VARCHAR author
        VARCHAR publisher
        VARCHAR isbn
        VARCHAR category
        INT copies
        INT available_copies
        VARCHAR status
        VARCHAR edition
        INT pages
        VARCHAR source_of_funds
        DECIMAL cost_price
        INT year
        VARCHAR condition
        TIMESTAMP created_at
        TIMESTAMP updated_at
        TIMESTAMP deleted_at
    }

    BORROWS {
        BIGINT id PK
        BIGINT user_id
        BIGINT book_id
        BIGINT book_copy_id FK
        DATE borrowed_at
        DATE due_date
        TIMESTAMP returned_at
        VARCHAR return_status
        VARCHAR remark
        TEXT notes
        VARCHAR role
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    LOST_DAMAGED_ITEMS {
        BIGINT id PK
        BIGINT borrow_id FK
        BIGINT book_id
        VARCHAR type
        VARCHAR status
        BOOLEAN is_resolved
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    LOST_DAMAGED_ITEM_HISTORIES {
        BIGINT id PK
        BIGINT lost_damaged_item_id FK
        VARCHAR action
        TEXT remarks
        BIGINT created_by FK
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    PENALTY_SETTINGS {
        BIGINT id PK
        INT borrow_days_allowed
        DECIMAL penalty_per_day
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    ACTIVITY_LOGS {
        BIGINT id PK
        BIGINT user_id FK
        VARCHAR action
        VARCHAR target_type
        BIGINT target_id
        TEXT details
        TIMESTAMP created_at
        TIMESTAMP updated_at
    }

    SYSTEM_USERS ||--o{ ACTIVITY_LOGS : "actor"
    SYSTEM_USERS ||--o{ LOST_DAMAGED_ITEM_HISTORIES : "created_by"
    USERS ||--o{ BORROWS : "borrows"
    TEACHERS ||--o{ BORROWS : "borrows"
    BOOKS ||--o{ BOOK_COPIES : "has_copies"
    BOOKS ||--o{ BORROWS : "book"
    BOOK_COPIES ||--o{ BORROWS : "copy"
    BORROWS ||--o{ LOST_DAMAGED_ITEMS : "item"
    LOST_DAMAGED_ITEMS ||--o{ LOST_DAMAGED_ITEM_HISTORIES : "histories"

    %% Note: Teachers can also be referenced in BORROWS.user_id by role logic
    %% Note: Distributed books are referenced by book_id in some flows
```

**Relationship Notes**
- `book_copies.book_id` references `books.id` via FK. Each book can have multiple copies.
- `book_copies.control_number` is unique within the system and tracks individual book copies.
- `borrows.book_copy_id` links borrowing transactions to specific book copies for better tracking.
- `borrows.user_id` is used for both students (`users`) and teachers (`teachers`) based on role logic.
- `lost_damaged_items.borrow_id` references `borrows.id` and tracks when items are marked lost or damaged.
- `lost_damaged_item_histories` maintains a complete audit trail of all status transitions without overwriting prior records.
- `activity_logs.user_id` references `system_users.id` via FK.

Sources: `app/Models/*`, `database/migrations/*`, `app/Http/Controllers/BorrowController.php`, `app/Http/Controllers/BookController.php`, `app/Http/Controllers/DashboardController.php`, `app/Models/BookCopy.php`, `app/Models/LostDamagedItem.php`.
