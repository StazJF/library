**Architecture Overview**

---

## 🏗️ Runtime Stack

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| **Backend Framework** | Laravel | `^12.0` | Web application framework |
| **Server Language** | PHP | `^8.2` | Server-side scripting |
| **Database** | MySQL | `8.0+` | Relational database |
| **Template Engine** | Blade | (Laravel built-in) | Server-side templating |
| **CSS Framework** | Tailwind CSS | `^4.1.18` | Utility-first CSS |
| **Build Tool** | Vite | `^7.0` | Frontend asset bundler |
| **Frontend Framework** | React/Inertia.js | React `^19.2.3`, Inertia `^0.11.1` | Client-side interactivity (scaffolded, partially used) |
| **Task Runner** | Composer Scripts | (built-in) | Development automation |
| **Package Managers** | Composer, npm | (latest) | Dependency management |

**Sources:** 
- Framework: `composer.json`, `bootstrap/app.php`
- Frontend: `vite.config.js`, `package.json`
- Database: `config/database.php`
- Template: `resources/views/layouts/app.blade.php`

---

## 📊 Request Flow Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Browser/Client                           │
└────────────────────┬────────────────────────────────────────┘
                     │ HTTP Request
                     ▼
┌─────────────────────────────────────────────────────────────┐
│                 Web Server (PHP)                            │
│                   (port 8000)                               │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │  routes/web.php            │
        │  (URL Routing)             │
        └────────────┬───────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │  Middleware Stack          │
        │  - Auth, CORS, CSRF        │
        └────────────┬───────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │  HTTP Controllers          │
        │  (app/Http/Controllers/*)  │
        │  - BookController          │
        │  - BorrowController        │
        │  - DashboardController     │
        │  - UtilitiesController     │
        │  - etc.                    │
        └────────────┬───────────────┘
                     │
            ┌────────┴───────┐
            │                │
            ▼                ▼
   ┌──────────────────┐  ┌──────────────────┐
   │ Eloquent Models  │  │ Services/Logic   │
   │ (app/Models/*)   │  │ (app/Services/*) │
   │ - Book           │  │ - Audit Logging  │
   │ - Borrow         │  │ - Backup Manager │
   │ - User           │  │ - etc.           │
   │ - etc.           │  └──────┬───────────┘
   └──────┬───────────┘         │
          │      ┌──────────────┘
          │      │
          ▼      ▼
   ┌──────────────────────┐
   │  MySQL Database      │
   │  (library_mgmt)      │
   │  - 17+ tables        │
   │  - Relationships     │
   │  - Audit logging     │
   └──────────┬───────────┘
              │
              ▼
      (Data retrieval)
              │
              ▼
   ┌──────────────────────┐
   │  Blade Templates     │
   │  (resources/views/*) │
   │  - Rendered HTML     │
   │  - Asset includes    │
   └──────────┬───────────┘
              │
              ▼
   ┌──────────────────────┐
   │  Response (HTML+CSS) │
   └──────────┬───────────┘
              │
              ▼
┌─────────────────────────────────────────────────────────────┐
│              Browser Renders Page                           │
└─────────────────────────────────────────────────────────────┘
```

**Sources:** `routes/web.php`, `app/Http/Controllers/`, `app/Models/`, `resources/views/`

---

## 🔄 Key Architectural Patterns

### 1. **Model-View-Controller (MVC)**

- **Models** (`app/Models/`) - Represent database entities and business logic
- **Views** (`resources/views/`) - Blade templates for presentation
- **Controllers** (`app/Http/Controllers/`) - Handle requests and coordinate logic

### 2. **Eloquent ORM**

All database operations go through Eloquent models:

```php
// Instead of raw SQL:
$books = DB::select('SELECT * FROM books WHERE status = ?', ['available']);

// Use Eloquent:
$books = Book::where('status', 'available')->get();
```

**Benefits:**
- Type-safe database access
- Built-in relationship handling
- Automatic timestamp management
- Query optimization

### 3. **Service Layer Pattern**

Business logic abstracted into service classes:

```php
// BookAuditLogger - handles audit event creation
BookAuditLogger::log($book, 'created', $actor, ...);

// BackupService - handles database backups
BackupService::createBackup();
```

### 4. **Observer Pattern**

Automatic event handling via Eloquent observers:

```php
BookObserver - Watches for Book model events:
  - created() - Logs when book added
  - updated() - Logs when book modified
  - deleted() - Logs when book permanently deleted
  - restored() - Logs when soft-deleted book restored
```

**Sources:** `app/Observers/`, `app/Models/`, `app/Providers/AppServiceProvider.php`

---

## 🎯 Core Features Architecture

### 1. **Book Inventory System**

```
Book Model
├── Attributes: title, author, isbn, category, copies
├── Relationships:
│   ├── copies → BookCopy (one-to-many)
│   ├── borrowHistories → Borrow (one-to-many)
│   └── auditEvents → BookAuditEvent (one-to-many)
├── Methods:
│   ├── getAvailableCopiesAttribute()
│   ├── getTotalCopiesAttribute()
│   ├── getCopyStatusBreakdown()
│   └── archive(), restore()
└── Observer: BookObserver (tracks all changes)

BookCopy Model
├── Attributes: control_number, status, condition, acquisition_year
├── Relationships:
│   ├── book → Book
│   ├── borrows → Borrow (one-to-many)
│   └── auditEvents → BookAuditEvent (one-to-many)
└── Observer: BookCopyObserver
```

### 2. **Transaction Management System**

```
Borrow Model (Transaction)
├── Attributes: borrowed_at, due_date, returned_at, return_status
├── Relationships:
│   ├── book → Book
│   ├── user → User (Student)
│   ├── teacher → Teacher (if teacher borrow)
│   └── lostDamagedItem → LostDamagedItem
├── Methods:
│   ├── getTransactionStatus()
│   ├── getTransactionStatusLabel()
│   ├── isLostOrDamaged()
│   └── getLossType()
└── Status Values:
    - STATUS_PENDING
    - STATUS_RETURNED_ON_TIME
    - STATUS_LATE_RETURN
    - STATUS_DAMAGED_FOR_REPAIR
    - STATUS_LOST_AND_FOUND
    - STATUS_REPAIRED (NEW)
    - STATUS_FOUND (NEW)
```

### 3. **Loss & Damage Tracking System**

```
LostDamagedItem Model
├── Attributes: type (lost/damaged), status (active/returned/replaced)
├── Relationships:
│   ├── borrow → Borrow
│   ├── book → Book
│   └── histories → LostDamagedItemHistory (one-to-many)
└── Methods: getStatus(), isLost(), isDamaged()

LostDamagedItemHistory Model
├── Attributes: action (created/repaired/returned/resolved)
├── Relationships:
│   └── lostDamagedItem → LostDamagedItem
└── Non-destructive: Each action creates new history entry
```

**Action Flow:**
```
Mark as Damaged
      ↓
Create LostDamagedItem + History (action='created')
      ↓
Display "Damaged / For Repair" 🔧
      ↓
Mark as Repaired
      ↓
Add History Entry (action='repaired') ← Previous history preserved
      ↓
Display "Repaired" ✓
```

### 4. **Audit System Architecture**

```
BookAuditEvent Model (Centralized Audit Log)
├── Event Types:
│   ├── book.created
│   ├── book.updated
│   ├── book.archived
│   ├── book.restored
│   ├── book.deleted_permanently
│   ├── copy.created
│   ├── copy.updated
│   └── copy.deleted
├── Captures:
│   ├── Actor: who (user info, IP, user agent)
│   ├── Subject: what (model type, ID)
│   ├── Changes: before/after values
│   └── Context: timestamp, description
└── Relationships:
    ├── actor → SystemUser
    ├── book → Book (via book_id, soft delete safe)
    └── copy → BookCopy (via book_copy_id)

Observers (Automatic Tracking)
├── BookObserver → Hooks Book model events
├── BookCopyObserver → Hooks BookCopy model events
└── All events logged via BookAuditLogger service
```

### 5. **Reporting & Analytics System**

```
DashboardController
└── reports() method:
    ├── Fetches all Borrow records
    ├── Eager loads: book, user, lostDamagedItem with histories
    ├── Enriches data:
    │   ├── transaction_status (from getTransactionStatus())
    │   ├── transaction_status_label (human-readable)
    │   ├── borrower_name (student/teacher name)
    │   └── borrower_type (Student/Teacher)
    ├── Applies filters:
    │   ├── By status (Active/Completed/All)
    │   └── By date range
    ├── Supports sorting:
    │   ├── By date borrowed
    │   ├── By due date
    │   ├── By return date
    │   └── By transaction ID
    └── Returns paginated results (10 per page)
```

### 6. **Backup System Architecture**

```
BackupDatabase Artisan Command
├── Runs: php artisan backup:database
├── Creates: Timestamped ZIP files
├── Storage: storage/app/backups/
├── Retention: Auto-delete old backups
└── Logging: All backups logged to activity_logs

UtilitiesController
├── backup() - Manual backup creation
├── deleteBackup() - Remove specific backup
├── viewActivityLog() - Audit trail
└── archive/restore - Book archive management
```

---

## 💾 Database Layer Architecture

### Data Model Structure

```
┌─────────────────────────────────────────────────────────────┐
│                    Core Entities                            │
├─────────────────────────────────────────────────────────────┤

┌─ Books & Copies ─────────────────────────────────────┐
│ books                    book_copies                 │
│ ├── id                   ├── id                       │
│ ├── title                ├── book_id (FK)             │
│ ├── author               ├── control_number           │
│ ├── isbn (unique)        ├── status                   │
│ ├── category             ├── condition                │
│ ├── copies               ├── is_lost_damaged          │
│ └── created_at           └── created_at               │
└─────────────────────────────────────────────────────┘

┌─ Users & Roles ──────────────────────────────────────┐
│ system_users             users                       │
│ ├── id                   ├── id                       │
│ ├── name                 ├── name                     │
│ ├── email                ├── email                    │
│ ├── password             ├── grade_level              │
│ ├── role (admin/staff)   └── created_at               │
│ └── created_at                                        │
│                          teachers                     │
│ Permissions:             ├── id                       │
│ roles_permissions        ├── name                     │
│ ├── role_id              ├── subject                  │
│ └── permission_id        └── created_at               │
└─────────────────────────────────────────────────────┘

┌─ Transactions ───────────────────────────────────────┐
│ borrows                                              │
│ ├── id                                               │
│ ├── user_id (FK) - Student                           │
│ ├── teacher_id (FK) - Optional teacher               │
│ ├── book_id (FK)                                     │
│ ├── book_copy_id (FK)                                │
│ ├── borrowed_at                                      │
│ ├── due_date                                         │
│ ├── returned_at                                      │
│ ├── return_status                                    │
│ ├── remarks                                          │
│ └── created_at                                       │
└─────────────────────────────────────────────────────┘

┌─ Loss & Damage ──────────────────────────────────────┐
│ lost_damaged_items      lost_damaged_item_histories  │
│ ├── id                  ├── id                       │
│ ├── borrow_id (FK)      ├── lost_damaged_item_id     │
│ ├── book_id (FK)        ├── action                   │
│ ├── type (lost/dmg)     ├── remarks                  │
│ ├── status              ├── created_by               │
│ └── created_at          └── created_at               │
└─────────────────────────────────────────────────────┘

┌─ Audit & Logging ────────────────────────────────────┐
│ book_audit_events       activity_logs                │
│ ├── id                  ├── id                       │
│ ├── event               ├── user_id                  │
│ ├── actor_id            ├── action                   │
│ ├── subject_type        ├── description              │
│ ├── subject_id          ├── model_type               │
│ ├── before (JSON)       ├── model_id                 │
│ ├── after (JSON)        ├── changes (JSON)           │
│ ├── description         └── created_at               │
│ └── created_at                                       │
│                         audit_logs                   │
│                         ├── id                       │
│                         ├── event_code               │
│                         ├── table_name               │
│                         ├── key_value                │
│                         ├── old_value (JSON)         │
│                         ├── new_value (JSON)         │
│                         └── created_at               │
└─────────────────────────────────────────────────────┘

└─ Infrastructure ──────────────────────────────────────┐
│ migrations              jobs, cache, sessions, etc.  │
│ (track applied         (Laravel queue & cache)      │
│  migrations)                                        │
└─────────────────────────────────────────────────────┘
```

**Sources:** 
- Database config: `config/database.php`
- Migrations: `database/migrations/`
- Models: `app/Models/`
- Schema visualization: `database/schema/`

---

## 🔐 Authentication & Authorization

### Session-Based Auth Flow

```
1. User visits /login
        ↓
2. LoginController displays form
        ↓
3. User submits credentials
        ↓
4. LoginController validates against system_users table
        ↓
5. Session created via auth middleware
        ↓
6. Session cookie sent to browser
        ↓
7. Auth middleware verifies session on each request
        ↓
8. User can access protected routes
```

**Sources:**
- Auth config: `config/auth.php`
- Login controller: `app/Http/Controllers/Auth/LoginController.php`
- Model: `app/Models/SystemUser.php`
- Middleware: `app/Http/Middleware/Authenticate.php`

### Role-Based Access (Not Currently Enforced)

```
Roles in system_users:
├── admin - Full system access
└── staff - Limited administrative access

Middleware Available (not wired to routes):
├── CheckRole - Verify specific role
└── RoleMiddleware - Role-based route protection

Spatie Permissions:
├── Roles - Named role sets
├── Permissions - Granular capabilities
└── Relationship bridge - Users can have many roles/permissions
```

---

## 🛠️ Development Environment Setup

### Local Development Architecture

```
┌─────────────────────────────────────────────────────┐
│        Development Environment (composer run dev)   │
├─────────────────────────────────────────────────────┤

Terminal 1: PHP Development Server
  Command: php artisan serve
  Runs on: http://127.0.0.1:8000
  Purpose: Handles HTTP requests

Terminal 2: Queue Listener
  Command: php artisan queue:listen --tries=1
  Purpose: Processes background jobs

Terminal 3: Log Viewer
  Command: php artisan pail
  Purpose: Real-time log display

Terminal 4: Vite Dev Server
  Command: npm run dev
  Runs on: http://localhost:5173
  Purpose: Asset compilation with hot reload

Browser:
  Open: http://127.0.0.1:8000
  Displays: Application with hot-reloaded assets
```

### Asset Pipeline

```
Source Files
├── resources/css/app.css (Tailwind CSS source)
├── resources/js/app.js (JavaScript entry)
└── resources/views/layouts/app.blade.php (Blade layout)

  ↓ (npm run dev - Development)
  
Vite Compiler
├── Watches for changes
├── Hot module replacement
└── Serves unminified assets

  ↓ or ↓ (npm run build - Production)

Compiled Assets
├── public/build/app.css (minified, hashed)
├── public/build/app.js (minified, hashed)
└── public/build/manifest.json (asset map)

Browser Loading
├── Loads compiled CSS
├── Loads compiled JS
└── Renders page
```

**Sources:**
- Vite config: `vite.config.js`
- Tailwind config: `tailwind.config.cjs`
- JS entry: `resources/js/app.js`

---

## 📋 Request Lifecycle Summary

```
1. HTTP Request arrives
        ↓
2. Laravel bootstraps application (bootstrap/app.php)
        ↓
3. Route dispatcher finds matching route (routes/web.php)
        ↓
4. Middleware pipeline processes:
   - Authentication
   - CSRF verification
   - Session handling
        ↓
5. Controller action executes
   - Validates input
   - Calls models/services
   - Fetches data
        ↓
6. Eloquent queries database
   - Executes with observers firing
   - Audit events created if needed
   - Results returned
        ↓
7. Response prepared
   - Data passed to Blade view
   - HTML rendered
   - CSS/JS included
        ↓
8. Response sent to browser
        ↓
9. Browser renders HTML
   - Loads CSS
   - Executes JS (React/Inertia if applicable)
   - Displays page
```

---

## 🎨 Frontend Architecture Notes

**Current State:**
- **Primary:** Blade templates with Tailwind CSS
- **Secondary:** React/Inertia.js (scaffolded but not heavily used)
- **Assets:** Vite for bundling and hot reload

**Template Structure:**
```
resources/views/
├── layouts/app.blade.php (Main layout with navbar, footer)
├── books/
│   ├── index.blade.php (Book catalog)
│   ├── create.blade.php (Create book form)
│   └── edit.blade.php (Edit book form)
├── borrow/
│   ├── index.blade.php (Transaction list)
│   ├── create.blade.php (New borrow form)
│   └── receipt.blade.php (Transaction receipt)
├── users/
├── dashboard.blade.php (Dashboard page)
├── reports.blade.php (Reports & analytics)
├── utilities/
│   └── backups.blade.php (Backup management)
└── auth/
    └── login.blade.php (Login page)
```

**Sources:**
- Layout: `resources/views/layouts/app.blade.php`
- CSS: `resources/css/app.css` (Tailwind directives)
- JS entry: `resources/js/app.js`

---

## Where To Go Next

- [Local Setup Guide](../02-setup/local-setup.md) - Get running locally
- [Routing Map](../03-backend/routing-map.md) - All available routes
- [Database Schema](../03-backend/database/schema.md) - Detailed table info
- [Feature Documentation](../../TRANSACTION_STATUS_TRANSITIONS.md) - Feature specifics
