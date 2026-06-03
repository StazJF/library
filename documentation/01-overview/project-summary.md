**Product Summary**

This project is a comprehensive web-based library management system designed for Subic National High School. It enables staff and admins to manage book inventory, student and teacher profiles, borrowing and returns, advanced reporting with transaction tracking, and utilities such as audit logging, archive/restore, and automated backups.

**Key Features:**
- **Book Inventory Management** - Catalog management with import/export, copy tracking, and status monitoring
- **Transaction Management** - Borrowing and returns for students and teachers with full history
- **Loss & Damage Tracking** - Track lost/damaged items with recovery status (repaired, found)
- **Advanced Reporting** - Comprehensive transaction reports with status transitions, filtering, and sorting
- **Copy Count Tracking** - Clear separation of available vs. total copies with detailed status breakdown
- **Audit System** - Complete audit trail of all book and copy changes with actor information
- **Backup & Recovery** - Automated database backups with retention policies and manual backup options
- **Activity Logging** - All user actions logged for compliance and troubleshooting

---

**Primary User Roles**

- **Admin and Staff:** Authenticate via `SystemUser` with roles `admin` or `staff`. Have full access to all system features including book management, reporting, and utilities.
  - Sources: `app/Models/SystemUser.php`, `app/Http/Controllers/Auth/LoginController.php`, `config/auth.php`
  
- **Students:** Stored in the `User` model, can borrow books through the borrow interface.
  - Sources: `app/Models/User.php`, `app/Http/Controllers/UserController.php`
  
- **Teachers:** Stored in a dedicated `Teacher` model, have separate borrow management interface.
  - Sources: `app/Models/Teacher.php`, `app/Http/Controllers/TeacherController.php`

---

**Core Capabilities**

#### 1. **Book Inventory Management**
- Import books via CSV with validation and error reporting
- Maintain detailed book information (title, author, ISBN, category, publisher)
- Track individual book copies with control numbers and acquisition years
- Monitor copy status: available, borrowed, lost, damaged, repaired, or found
- Generate printable book lists
- Archive and restore books from active catalog
- Sources: `routes/web.php`, `app/Http/Controllers/BookController.php`, `app/Models/Book.php`, `app/Models/BookCopy.php`

#### 2. **Copy Count Management**
- **Available Copies:** Real-time count of books available to borrow
- **Total Copies:** Complete inventory of all book copies
- **Status Breakdown:** Detailed view of copies by status (borrowed, lost, damaged, repaired, found)
- Automatic sync between `BookCopy` records and `Book.copies` field
- Sources: `app/Models/Book.php`, `app/Http/Controllers/BookController.php`

#### 3. **Transaction Management**
- **Student Borrowing:** Students can borrow and return books with due dates
- **Teacher Borrowing:** Separate interface for teacher book borrowing
- **Borrow Receipts:** Generate transaction receipts with book details
- **Remarks:** Add notes to borrow transactions
- **Return Workflows:** Process returns with status tracking
- Sources: `routes/web.php`, `app/Http/Controllers/BorrowController.php`, `app/Http/Controllers/TeacherBorrowController.php`, `app/Models/Borrow.php`

#### 4. **Loss & Damage Tracking**
- **Mark as Damaged:** Report books needing repair
- **Mark as Lost:** Report missing books
- **Mark as Repaired:** Update damaged books when restored
- **Mark as Found:** Update lost books when recovered
- **Full History:** Non-destructive logging of all status transitions
- **Status Badges:** Visual indicators with color coding (red for damaged, blue for repaired, green for found)
- Sources: `app/Models/LostDamagedItem.php`, `app/Models/Borrow.php`, `resources/views/reports.blade.php`

#### 5. **Advanced Reporting*
- **All Transactions Report:** Comprehensive table of all borrowing transactions
- **Filtering:** Filter by status (Active, Completed, All)
- **Sorting:** Sort by date borrowed, due date, return date, or transaction ID
- **Pagination:** Navigate through transactions (10 per page)
- **Status Tracking:** Display current status (Pending, Active, Overdue, Returned, Damaged/For Repair, Lost and Found, Repaired, Found)
- **Enriched Data:** Includes borrower type (Student/Teacher), due dates, and status labels
- Sources: `app/Http/Controllers/DashboardController.php`, `resources/views/reports.blade.php`

#### 6. **Audit System**
- **Book Changes Tracking:** Automatically log all book modifications (created, updated, archived, deleted, restored)
- **Copy Changes Tracking:** Track individual book copy changes
- **Detailed Metadata:** Capture actor info, IP address, user agent, before/after values
- **Non-Destructive:** All changes preserved for compliance and troubleshooting
- **Query Support:** Search audit logs by event type, actor, date range, or affected books
- Sources: `app/Models/BookAuditEvent.php`, `app/Observers/BookObserver.php`, `app/Observers/BookCopyObserver.php`, `app/Services/BookAuditLogger.php`

#### 7. **Staff Management**
- Create and manage admin and staff accounts
- Assign roles and permissions
- View staff activity logs
- Sources: `routes/web.php`, `app/Http/Controllers/UserManagementController.php`, `resources/views/staff/*.blade.php`

#### 8. **Backup & Recovery**
- **Manual Backups:** Create instant database backups from UI
- **Automated Backups:** Schedule daily backups with configurable retention
- **Compression:** ZIP compression for storage efficiency
- **File Management:** Delete backups from UI
- **Activity Logging:** All backup actions logged
- Sources: `app/Console/Commands/BackupDatabase.php`, `app/Http/Controllers/UtilitiesController.php`, `routes/web.php`

#### 9. **Activity Logging & Utilities**
- **Activity Log:** Complete audit trail of all user actions
- **Archive/Restore:** Archive books and restore them later
- **Backup Management:** Create, view, and delete database backups
- **System Utilities:** Various maintenance and diagnostic tools
- Sources: `app/Http/Controllers/UtilitiesController.php`, `app/Models/ActivityLog.php`

---

**System Architecture**

```
┌──────────────┐
│  Browser UI  │ (Blade Templates with Tailwind CSS & Bootstrap)
└──────┬───────┘
       │
       ▼
┌──────────────────────────┐
│   Laravel Routes         │ (routes/web.php)
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│   HTTP Controllers       │ (app/Http/Controllers/)
│   - BookController       │
│   - BorrowController     │
│   - DashboardController  │
│   - UtilitiesController  │
│   - etc.                 │
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│   Eloquent Models        │ (app/Models/)
│   - Book, BookCopy       │
│   - Borrow, User, Teacher│
│   - LostDamagedItem      │
│   - ActivityLog, etc.    │
└──────┬───────────────────┘
       │
       ▼
┌──────────────────────────┐
│   MySQL Database         │
│   (17+ tables)           │
└──────────────────────────┘
```

---

**Technology Stack**

| Layer | Technology | Version |
|-------|-----------|---------|
| **Backend Framework** | Laravel | 12.0+ |
| **Language** | PHP | 8.2+ |
| **Database** | MySQL | 8.0+ |
| **Frontend Templating** | Blade | (Laravel built-in) |
| **CSS Framework** | Tailwind CSS | 4.0+ |
| **Build Tool** | Vite | 7.0+ |
| **Frontend Framework** | React/Inertia.js | (scaffolded, partially used) |
| **Package Manager (PHP)** | Composer | 2.0+ |
| **Package Manager (JS)** | npm | 9.0+ |

---

**Development Stack**

| Tool | Purpose |
|------|---------|
| **PHPUnit** | Unit and feature testing |
| **Laravel Pail** | Real-time log viewing |
| **Laravel Tinker** | Interactive shell for debugging |
| **Spatie Permissions** | Role and permission management |
| **Inertia.js** | React/Laravel integration (scaffolded) |

---

**Key Characteristics**

- **Session-Based Auth:** Uses `SystemUser` model with session middleware
- **Eloquent ORM:** All data access through Laravel Eloquent models
- **Blade Templating:** Primary UI rendering technology
- **Event-Based Auditing:** Observers automatically track model changes
- **Queue Support:** Background job processing capability
- **Caching:** Multiple cache store options (file, database, Redis)

---

**Where To Go Next**

- **Setup Instructions:** [documentation/02-setup/local-setup.md](../02-setup/local-setup.md)
- **Environment Variables:** [documentation/02-setup/env-vars.md](../02-setup/env-vars.md)
- **Architecture Details:** [documentation/01-overview/architecture.md](./architecture.md)
- **Folder Structure:** [documentation/01-overview/folder-map.md](./folder-map.md)
- **Backend Routing:** [documentation/03-backend/routing-map.md](../03-backend/routing-map.md)
- **Feature Documentation:**
  - [Transaction Status Tracking](../../TRANSACTION_STATUS_TRANSITIONS.md)
  - [Audit System](../../AUDIT_SYSTEM_EXPLANATION.md)
  - [Copy Count Implementation](../../COPY_COUNT_IMPLEMENTATION.md)
  - [Reports Module](../../REPORTS_MODULE_UPDATES.md)
- **Known Issues:** [documentation/07-quality/known-issues.md](../07-quality/known-issues.md)
