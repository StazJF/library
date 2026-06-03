# SNHS Library Management System

Laravel-based library management system for Subic National High School. Complete inventory, borrowing, transaction tracking, audit logging, and reporting features.

**Status:** ✅ Production-Ready | **Version:** 1.0 | **Last Updated:** May 11, 2026

---

## 🎯 Quick Links

- 🚀 **[Quick Start Checklist](QUICK_START_CHECKLIST.md)** - Setup in 5 steps
- 📖 **[Full Setup Guide](documentation/02-setup/local-setup.md)** - Detailed setup instructions
- 🏗️ **[System Architecture](documentation/01-overview/architecture.md)** - Technical overview
- 📚 **[Project Summary](documentation/01-overview/project-summary.md)** - Complete features list
- 🔧 **[Troubleshooting](documentation/02-setup/troubleshooting.md)** - Common issues & solutions
- 📋 **[Environment Variables](documentation/02-setup/env-vars.md)** - Configuration reference

---

## ✨ Key Features

### 📚 Book Inventory Management
- CSV import with validation and error reporting
- Track individual copy status (available, borrowed, lost, damaged, repaired, found)
- Available vs Total copies tracking with automatic synchronization
- Book archival and restoration
- Printable book lists

### 📊 Transaction Management
- Student and teacher borrow workflows
- Due date tracking and return management
- Borrow receipts with transaction details
- Comprehensive transaction history and filtering

### 🎯 Loss & Damage Tracking
- Mark books as damaged or lost
- Track repair and recovery (repaired, found statuses)
- Non-destructive history logging with status transitions
- Visual status indicators with color-coded badges

### 📈 Advanced Reporting
- All Transactions detailed view with pagination
- Filter by status (Active, Completed, All)
- Sort by date borrowed, due date, return date, or ID
- Enriched transaction data with borrower information
- Real-time status display

### 🔍 Audit System
- Automatic change tracking for all books and copies
- Complete before/after value capture
- Actor information (user, IP, user agent) logging
- Non-destructive audit trail with full history
- Event-based auditing via Eloquent observers

### 💾 Backup & Recovery
- Manual database backup creation
- Automated backup with retention policies
- Timestamped ZIP compression for storage efficiency
- Backup management and deletion
- Activity logging for all backup operations

### 👤 User Management
- Admin and staff account management
- Student database with grade level tracking
- Teacher profile management
- Role-based access control

### 📋 Activity Logging
- Complete audit trail of all system operations
- Detailed change history for compliance
- User activity tracking
- System event logging

---

## 📋 Requirements

| Component | Version | Purpose |
|-----------|---------|---------|
| PHP | 8.2+ | Server runtime |
| MySQL | 8.0+ | Database |
| Composer | 2.0+ | PHP package manager |
| Node.js | 18+ | JS runtime |
| npm | 9+ | JS package manager |

---

## 🚀 Quick Start (5 minutes)

### Option 1: Laravel Herd (Recommended - Faster Setup)

**Laravel Herd** is the fastest way to get started. It includes PHP, MySQL, and Redis automatically.

```bash
# 1. Install Herd from herd.laravel.com (Mac/Windows)

# 2. Clone & install
cd ~/Herd
git clone https://github.com/Jimnastic123/SNHS.git
cd SNHS
composer install
npm install

# 3. Setup
cp .env.example .env
php artisan key:generate
mysql -u root -e "CREATE DATABASE library_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migrate & seed
php artisan migrate --seed

# 5. Start
composer run dev
```

**Result:** Visit `http://127.0.0.1:8000` ✅

---

### Option 2: Manual Setup with XAMPP

1. Clone & Install

```bash
cd path/to/project
composer install
npm install
```

### 2. Configure
```bash
copy .env.example .env
php artisan key:generate
```

Edit `.env` and set database:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_mgmt
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Database Setup
```bash
# Create database
mysql -u root -p -e "CREATE DATABASE library_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php artisan migrate

# (Optional) Seed sample data
php artisan db:seed
```

### 4. Start Development
```bash
composer run dev
```

Services start automatically:
- PHP Server: http://127.0.0.1:8000
- Queue Listener: Background jobs
- Log Viewer: Real-time logs
- Vite Dev Server: Asset compilation

### 5. Login
Visit `http://127.0.0.1:8000`

**Default credentials** (if seeded):
- Email: `admin@example.com`
- Password: `password`

**Or create admin:**
- Visit: `http://127.0.0.1:8000/create-admin`

---

## 📖 Full Documentation

### Getting Started
- [Quick Start Checklist](QUICK_START_CHECKLIST.md) - Step-by-step setup
- [Local Setup Guide](documentation/02-setup/local-setup.md) - Detailed setup with options
- [Environment Variables](documentation/02-setup/env-vars.md) - Configuration reference
- [Troubleshooting](documentation/02-setup/troubleshooting.md) - Common issues & solutions

### Understanding the System
- [Project Summary](documentation/01-overview/project-summary.md) - Complete feature overview
- [System Architecture](documentation/01-overview/architecture.md) - Technical architecture
- [Folder Structure](documentation/01-overview/folder-map.md) - Project organization

### Backend & Features
- [Routing Map](documentation/03-backend/routing-map.md) - All available routes
- [Database Schema](documentation/03-backend/database/schema.md) - Table definitions
- [Transactions & Status Tracking](TRANSACTION_STATUS_TRANSITIONS.md) - Status flow
- [Audit System](AUDIT_SYSTEM_EXPLANATION.md) - Change tracking
- [Copy Count System](COPY_COUNT_IMPLEMENTATION.md) - Inventory tracking
- [Reports Module](REPORTS_MODULE_UPDATES.md) - Advanced reporting

### Operations
- [Backup Setup Guide](BACKUP_SETUP_GUIDE.md) - Automated backups
- [Deployment Guide](documentation/06-operations/deployment.md) - Production deployment
- [Books Import Fix](BOOKS_IMPORT_FIX.md) - CSV import requirements

---

## 🗂️ Project Structure

```
library/
├── app/
│   ├── Models/              # Eloquent models (Book, Borrow, User, etc.)
│   ├── Http/
│   │   ├── Controllers/     # Request handlers
│   │   └── Middleware/      # Request middleware
│   ├── Console/Commands/    # Artisan commands (backup, etc.)
│   ├── Observers/           # Model event listeners (audit tracking)
│   └── Services/            # Business logic
├── bootstrap/               # Framework bootstrap
├── config/                  # Configuration files
├── database/
│   ├── migrations/          # Database schema
│   ├── seeders/             # Sample data
│   └── schema/              # Schema snapshots
├── public/                  # Web root (served files)
├── resources/
│   ├── views/               # Blade templates (HTML)
│   ├── css/                 # Tailwind CSS
│   └── js/                  # JavaScript
├── routes/web.php           # Route definitions
├── storage/                 # Backups, logs, cache
├── tests/                   # PHPUnit tests
├── vendor/                  # PHP dependencies (composer)
├── node_modules/            # JS dependencies (npm)
├── .env                     # Environment config
├── composer.json            # PHP dependencies
└── package.json             # JS dependencies
```

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| **Backend Framework** | Laravel 12 |
| **Server Language** | PHP 8.2+ |
| **Database** | MySQL 8.0+ |
| **Template Engine** | Blade |
| **CSS Framework** | Tailwind CSS 4.0+ |
| **Build Tool** | Vite 7.0+ |
| **Frontend** | React/Inertia.js (scaffolded) |
| **Permissions** | Spatie Permissions |

---

## 🔑 Key Models

| Model | Purpose |
|-------|---------|
| `Book` | Book catalog entries |
| `BookCopy` | Individual copy tracking |
| `Borrow` | Transaction records |
| `User` | Student accounts |
| `Teacher` | Teacher accounts |
| `SystemUser` | Admin/Staff accounts |
| `LostDamagedItem` | Loss/damage tracking |
| `BookAuditEvent` | Change audit log |
| `ActivityLog` | User activity log |

---

## 📊 Database Tables

- `books` - Book catalog
- `book_copies` - Individual copies
- `borrows` - Transactions
- `users` - Students
- `teachers` - Teachers
- `system_users` - Admin/Staff
- `lost_damaged_items` - Loss/damage records
- `lost_damaged_item_histories` - Status history
- `book_audit_events` - Change audit
- `activity_logs` - User activity
- Plus: sessions, cache, migrations, job tables

---

## 🎯 Common Tasks

### Create Admin Account
```bash
# Method 1: Web interface
# Visit: http://127.0.0.1:8000/create-admin

# Method 2: Command
php artisan db:seed --class=AdminSeeder

# Method 3: Artisan tinker
php artisan tinker
>>> App\Models\SystemUser::create([...]);
```

### Import Books
1. Prepare CSV file with columns: title, author, publisher, isbn, category, copies
2. Visit: http://127.0.0.1:8000/books
3. Click "Import" and select CSV file

### Create Backup
```bash
# Manual backup
php artisan backup:database

# Automated (daily)
# Set up in Task Scheduler with:
php artisan backup:database --retention=30
```

### Reset Database
```bash
php artisan migrate:fresh --seed
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
```

---

## 🔍 Debugging

### View Logs
```bash
# Real-time tail
php artisan pail

# Or view file
tail -f storage/logs/laravel.log
```

### Database Debugging
```bash
# Show database info
php artisan db:show

# Interactive shell
php artisan tinker
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
```

---

## 📚 API Documentation

See [API Documentation](documentation/05-api/api-map.md) for:
- Authentication endpoints
- Book management endpoints
- Transaction endpoints
- Reporting endpoints
- Request/response examples

---

## ✅ Testing

Run tests:
```bash
composer run test
```

---

## 🔐 Security

- ✅ Session-based authentication
- ✅ CSRF protection on all forms
- ✅ SQL injection prevention (Eloquent)
- ✅ Complete audit trail
- ✅ Activity logging
- ✅ Role-based access control

**⚠️ Before Production:**
- Set `APP_DEBUG=false`
- Set `APP_ENV=production`
- Generate new `APP_KEY`
- Use strong database password
- Enable HTTPS
- Set up automated backups

---

## 📞 Support

Having issues? Check:

1. **[Quick Start](QUICK_START_CHECKLIST.md)** - Fast setup reference
2. **[Troubleshooting](documentation/02-setup/troubleshooting.md)** - 20+ common issues
3. **[Local Setup](documentation/02-setup/local-setup.md)** - Detailed instructions
4. **Logs:** `storage/logs/laravel.log`

---

## 📝 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | May 11, 2026 | Initial release with all core features |

---

## 📄 License

This project is owned by Subic National High School.

---

## 👥 Project Information

- **Organization:** Subic National High School
- **GitHub:** [Jimnastic123/SNHS](https://github.com/Jimnastic123/SNHS)
- **Current Branch:** main
- **PHP Version:** 8.2+
- **Laravel Version:** 12.0+

---

## 🎓 Learning Resources

### For Developers
1. Read [System Architecture](documentation/01-overview/architecture.md)
2. Study [Routing Map](documentation/03-backend/routing-map.md)
3. Explore [Database Schema](documentation/03-backend/database/schema.md)
4. Review feature implementations:
   - [Audit System](AUDIT_SYSTEM_EXPLANATION.md)
   - [Transaction Tracking](TRANSACTION_STATUS_TRANSITIONS.md)
   - [Reporting Module](REPORTS_MODULE_UPDATES.md)

### For Operations
1. Complete [Local Setup](documentation/02-setup/local-setup.md)
2. Set up [Backups](BACKUP_SETUP_GUIDE.md)
3. Learn [Deployment](documentation/06-operations/deployment.md)
4. Reference [Troubleshooting](documentation/02-setup/troubleshooting.md)

---

**Last Updated:** May 11, 2026  
**Next Update:** When new features are added or dependencies upgrade

- If you see `Table 'system_users' doesn't exist`, run:

```bash
php artisan migrate --path=database/migrations/2026_02_27_000001_create_system_users_table.php
```

- If `teachers` table already exists but migration is pending:
  - Option A: insert the migration record into the `migrations` table manually, or
  - Option B: drop `teachers` then re-run `php artisan migrate`.
