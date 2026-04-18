# Book Audit System - How It Works

## Overview
Your audit system automatically tracks all changes to books and book copies, logging who made what changes, when they were made, and capturing before/after values. It's built on a combination of Eloquent Observers, a centralized logging service, and an audit context system.

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      Database Changes                            │
│                   (Book or BookCopy Model)                       │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
        ┌────────────────────────────────┐
        │   Eloquent Observer            │
        │  (BookObserver /              │
        │   BookCopyObserver)           │
        │                               │
        │  - Detects created/updated/   │
        │    deleted/restored/force     │
        │    deleted events             │
        └────────────┬──────────────────┘
                     │
                     ▼
        ┌────────────────────────────────┐
        │   AuditContext Check           │
        │                               │
        │  - Is this event suppressed?  │
        │  - Should we log it?          │
        └────────────┬──────────────────┘
                     │
                     ▼
        ┌────────────────────────────────┐
        │   BookAuditLogger::log()       │
        │                               │
        │  - Capture event details      │
        │  - Extract actor info         │
        │  - Get IP & user agent        │
        │  - Compare before/after       │
        └────────────┬──────────────────┘
                     │
                     ▼
        ┌────────────────────────────────┐
        │  book_audit_events Table       │
        │                               │
        │  - Stores complete audit      │
        │    event with all metadata    │
        └────────────────────────────────┘
```

---

## Key Components

### 1. **BookAuditEvent Model** 
**File**: `app/Models/BookAuditEvent.php`

Stores audit records in the `book_audit_events` table with these fields:

| Field | Purpose |
|-------|---------|
| `event` | Event type: `book.created`, `book.updated`, `book.archived`, `book.restored`, `book.deleted_permanently`, `copy.created`, etc. |
| `actor_id`, `actor_email`, `actor_role` | Who performed the action (user info) |
| `subject_type`, `subject_id` | The model that was changed (Book or BookCopy) |
| `book_id`, `book_title` | Reference to the book (denormalized for quick search) |
| `book_copy_id`, `copy_control_number` | Reference to the copy (if applicable) |
| `before` | JSON snapshot of old values (before update) |
| `after` | JSON snapshot of new values (after update) |
| `description` | Human-readable summary (e.g., "Created book 'Laravel Guide'.") |
| `ip` | IP address of the user making the change |
| `user_agent` | Browser/client info |
| `meta` | Additional metadata (JSON) |
| `created_at` | When the change occurred |

**Relationships**:
- `actor()` - The SystemUser who made the change
- `book()` - The Book being audited (with soft deletes)
- `copy()` - The BookCopy being audited (with soft deletes)

---

### 2. **BookObserver** 
**File**: `app/Observers/BookObserver.php`

Automatically hooks into Eloquent model events for the Book model. Tracks these events:

#### **book.created**
- Triggered when: New book is created
- Logs: All tracked fields as `after` values
- `before` is empty

#### **book.updated**
- Triggered when: Book fields are modified
- Only logs: Fields that actually changed (efficiency)
- Captures: `before` (original values) and `after` (new values)
- Ignores: `updated_at` timestamp changes

#### **book.archived** (soft delete)
- Triggered when: Book is soft-deleted (moves to trash)
- `before`: All book data before deletion
- `after`: Empty

#### **book.restored**
- Triggered when: Soft-deleted book is restored
- `before`: Empty
- `after`: All book data after restoration

#### **book.deleted_permanently** (hard delete)
- Triggered when: Book is force-deleted from database
- `before`: All book data before deletion
- `after`: Empty

**Tracked Fields** (only these fields trigger audits):
```
title, author, isbn, category, purchase_price, cost_price, publisher, 
edition, pages, source_of_funds, published_year, acquisition_type, 
condition, call_number, status, copies, available_copies, dewey_decimal, 
cutter_number
```

---

### 3. **BookCopyObserver**
**File**: `app/Observers/BookCopyObserver.php`

Similar to BookObserver but for individual book copies. Tracks:
- `copy.created` - New copy added
- `copy.updated` - Copy details modified
- `copy.archived` - Copy soft-deleted
- `copy.restored` - Copy restored
- `copy.deleted_permanently` - Copy hard-deleted

---

### 4. **BookAuditLogger Service**
**File**: `app/Support/Audit/BookAuditLogger.php`

Central service that handles the actual logging. Static method:

```php
BookAuditLogger::log(
    string $event,           // e.g., 'book.created'
    ?Model $subject = null,  // Book or BookCopy instance
    ?string $description,    // Human-readable text
    array $before = [],      // Old values
    array $after = [],       // New values
    array $meta = []         // Extra metadata
): BookAuditEvent
```

**What it does**:
1. Checks if `book_audit_events` table exists
2. Gets current authenticated user (actor)
3. Extracts book and copy info from subject model
4. Captures IP address and user agent from request
5. Creates the audit record in database
6. Falls back to logging if database write fails

---

### 5. **AuditContext - Suppression System**
**File**: `app/Support\Audit\AuditContext.php`

Allows temporarily disabling audit logging to prevent duplicate/unnecessary entries.

**Methods**:
```php
// Suppress logging for a scope
AuditContext::suppress('book', true);

// Check if suppressed
if (AuditContext::isSuppressed('book')) { ... }

// Execute code with suppression, then restore
AuditContext::withSuppressed('book_copy', function () {
    // Audit logging disabled for book_copy here
    // Automatically re-enabled after
});
```

**Why it matters**: When deleting a book with all its copies, you don't want duplicate entries. The code suppresses book_copy audit events while deleting copies.

---

## How It Works in Practice

### Scenario 1: User Updates Book Price

**User Action**: Admin updates book price from 500 to 600 in BookController

**Flow**:
1. `BookController::update()` is called
2. `$book->update(['purchase_price' => 600])` executes
3. Eloquent triggers `updated()` event on BookObserver
4. Observer checks: Is book auditing suppressed? No → Continue
5. Observer calls `BookAuditLogger::log()` with:
   - `event`: 'book.updated'
   - `subject`: The Book instance
   - `before`: `['purchase_price' => 500]`
   - `after`: `['purchase_price' => 600]`
   - `description`: "Updated book 'Laravel Guide'."
6. Logger creates BookAuditEvent entry with admin's email, IP, user agent, etc.

**Result**: Admin can later view:
- Who changed the price (admin@example.com)
- When (2025-04-18 14:32:15)
- What changed (purchase_price: 500 → 600)
- From where (IP: 192.168.1.100)

---

### Scenario 2: Manual Logging in Controller

**File**: `app/Http/Controllers/BookController.php`

Sometimes logging is done manually for custom events:

```php
// When importing books
BookAuditLogger::log(
    event: 'book.imported',
    subject: $book,
    description: "Imported book via Excel",
    after: $book->toArray(),
    meta: ['import_batch_id' => $batchId]
);

// When bulk updating stock
BookAuditLogger::log(
    event: 'book.stock_adjusted',
    subject: $book,
    description: "Adjusted available copies: $oldCount → $newCount",
    before: ['available_copies' => $oldCount],
    after: ['available_copies' => $newCount]
);
```

---

## Viewing Audit Logs

### Dashboard View: `/audit`
**File**: `resources/views/audit/index.blade.php`

Displays paginated table of all audit events with:
- **Date**: When the change occurred
- **User**: Who made it (email + role)
- **Event**: Type of change (book.created, book.updated, etc.)
- **Book**: Book title (if applicable)
- **Ctrl #**: Copy control number (if applicable)
- **Description**: Summary of change
- **View Button**: Link to detailed view

**Filtering available**:
- Search: Across book title, user email, event type, control number, description
- Event Type: Dropdown of all event types
- User: Dropdown of all users who made changes
- Date Range: From/To date filters

---

### Detail View: `/audit/{id}`
**File**: `resources/views/audit/show.blade.php`

Shows complete details of a single audit event:
- **Metadata**: ID, timestamp, user email, role, IP address
- **Event Info**: Event type, subject type, IDs
- **Book/Copy**: Title and control number
- **Three-Column View**:
  - **Before**: JSON of old values
  - **After**: JSON of new values
  - **Meta**: Additional metadata (JSON)

---

## Controller: BookAuditController
**File**: `app/Http/Controllers/BookAuditController.php`

Handles two routes:

```php
// GET /audit
public function index(Request $request)
{
    // 1. Check authorization (admin/staff only)
    // 2. Build query with filters
    // 3. Paginate results (25 per page)
    // 4. Get list of unique event types
    // 5. Get list of all actors (who made changes)
    // 6. Return to view
}

// GET /audit/{auditEvent}
public function show(BookAuditEvent $auditEvent)
{
    // 1. Check authorization
    // 2. Return detailed view
}
```

---

## Data Flow Example: Complete Update

**User**: Admin edits book details

```
Admin submits form → BookController::update()
    ↓
Book::update(['title' => 'New Title', 'price' => 600])
    ↓
Eloquent fires 'updated' event
    ↓
BookObserver::updated() called
    ↓
Check: AuditContext::isSuppressed('book')? No
    ↓
Extract changed fields: title, price
    ↓
BookAuditLogger::log(
    event: 'book.updated',
    subject: $book,
    before: ['title' => 'Old Title', 'price' => 500],
    after: ['title' => 'New Title', 'price' => 600],
    description: "Updated book 'New Title'."
)
    ↓
Logger captures:
  - actor_id: 5 (admin ID)
  - actor_email: admin@example.com
  - actor_role: admin
  - ip: 192.168.1.100
  - user_agent: Mozilla/5.0...
    ↓
BookAuditEvent::create([...])
    ↓
Record stored in book_audit_events table
    ↓
Admin can view in /audit dashboard
```

---

## Key Features

✅ **Automatic Tracking** - No manual logging needed for model events  
✅ **Before/After Values** - See exactly what changed  
✅ **User Attribution** - Know who made each change  
✅ **IP Tracking** - Know where changes came from  
✅ **Flexible Suppression** - Can suppress logging when needed  
✅ **Full Search** - Search across book, user, event, description  
✅ **Date Filtering** - View changes over time periods  
✅ **Relationship Loading** - See related book/copy data  
✅ **Soft Delete Aware** - Audit logs work with soft-deleted records  
✅ **Graceful Fallback** - Logs errors if DB write fails, doesn't break the app  

---

## Performance Considerations

1. **JSON Storage**: `before`, `after`, `meta` are stored as JSON for flexibility
2. **Denormalization**: `book_title` and `copy_control_number` are stored directly to enable quick searching without joins
3. **Indexes**: Should exist on:
   - `book_id` (for filtering by book)
   - `actor_id` (for filtering by user)
   - `event` (for filtering by event type)
   - `created_at` (for date filtering and latest ordering)
4. **Pagination**: Results paginated at 25 per page to keep queries fast

---

## Database Schema

```sql
CREATE TABLE book_audit_events (
    id BIGINT PRIMARY KEY,
    event VARCHAR(255),
    actor_id BIGINT,
    actor_email VARCHAR(255),
    actor_role VARCHAR(255),
    subject_type VARCHAR(255),
    subject_id BIGINT,
    book_id BIGINT,
    book_copy_id BIGINT,
    book_title VARCHAR(255),
    copy_control_number VARCHAR(255),
    ip VARCHAR(45),
    user_agent TEXT,
    description TEXT,
    before JSON,
    after JSON,
    meta JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

---

## Current Events Tracked

### Book Events
- `book.created` - New book added
- `book.updated` - Book details modified
- `book.archived` - Book soft-deleted
- `book.restored` - Book restored from archive
- `book.deleted_permanently` - Book hard-deleted
- `book.imported` - Book imported via import feature
- `book.stock_adjusted` - Stock/copies count changed
- `book.restricted` - Book marked as restricted
- `book.unrestricted` - Book restriction removed

### Copy Events  
- `copy.created` - New copy added to book
- `copy.updated` - Copy details changed
- `copy.archived` - Copy soft-deleted
- `copy.restored` - Copy restored
- `copy.deleted_permanently` - Copy hard-deleted

---

## Security

✅ Accessible only to staff/admin roles  
✅ Cannot modify or delete audit logs (append-only)  
✅ Doesn't log sensitive data like passwords  
✅ IP and user agent tracked for security  
✅ Actor information is captured at log time (won't change if user is deleted)

