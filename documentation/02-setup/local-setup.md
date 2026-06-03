# Local Setup Guide - Library Management System

**Last Updated:** May 11, 2026  
**Project:** SNHS Library Management System  
**Stack:** Laravel 12, PHP 8.2+, MySQL 8+, React/Inertia.js, Tailwind CSS

---

## 📋 Prerequisites

Before starting, ensure you have the following installed on your system:

| Component | Version | Purpose | Download | Required |
|-----------|---------|---------|----------|----------|
| **PHP** | `8.2` or higher | Backend runtime | [php.net](https://www.php.net/downloads) | Unless using Herd |
| **Composer** | `^2.0` | PHP dependency manager | [getcomposer.org](https://getcomposer.org) | Yes |
| **MySQL** | `8.0` or higher | Database server | [dev.mysql.com](https://dev.mysql.com/downloads/mysql) | Unless using Herd |
| **Node.js** | `18.0` or higher | JavaScript runtime | [nodejs.org](https://nodejs.org) | Yes |
| **npm** | `9.0` or higher | JavaScript package manager | Included with Node.js | Yes |
| **Git** | Latest | Version control | [git-scm.com](https://git-scm.com) | Yes |
| **Laravel Herd** (Optional) | Latest | All-in-one dev environment | [herd.laravel.com](https://herd.laravel.com) | Optional |

**Note:** If using **Laravel Herd**, you can skip installing PHP and MySQL manually. Herd includes both with better performance.

### Verify Installation

```bash
php -v              # Should show PHP 8.2+
composer -v         # Should show Composer version
mysql --version     # Should show MySQL 8.0+
node -v             # Should show Node.js 18+
npm -v              # Should show npm 9+
```

---

## ⚡ Optional: Using Laravel Herd (Recommended for Mac/Windows)

**Laravel Herd** is an all-in-one local development environment for Laravel that includes PHP 8.2+, MySQL, Redis, and more. It's significantly faster than XAMPP.

### Quick Herd Setup

1. **Install Herd:**
   - Download from [herd.laravel.com](https://herd.laravel.com)
   - Install and launch

2. **Clone Project:**
   ```bash
   cd ~/Herd  # or wherever you want
   git clone https://github.com/Jimnastic123/SNHS.git
   cd SNHS
   ```

3. **Install Dependencies:**
   ```bash
   composer install
   npm install
   ```

4. **Create .env:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database Setup:**
   ```bash
   # Create database (Herd provides MySQL)
   mysql -u root -p -e "CREATE DATABASE library_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # Or use herd database command if available
   # herd database create library_mgmt
   ```

6. **Run Migrations:**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

7. **Start Development:**
   ```bash
   composer run dev
   ```

**Benefits of Herd:**
- ✅ No manual MySQL/PHP installation
- ✅ Automatic PHP/MySQL updates
- ✅ Built-in Redis support
- ✅ Faster than XAMPP
- ✅ Better performance
- ✅ Easier database management

**Note:** If using Herd, skip the manual PHP/MySQL installation section and use the credentials Herd provides in its dashboard.

---

## 🚀 Step-by-Step Setup Process

### For Manual Setup (XAMPP/Traditional):

If not using Laravel Herd, follow these steps:

### Step 1: Clone Repository

```bash
git clone https://github.com/Jimnastic123/SNHS.git
cd SNHS
```

Or if already in the project directory:

```bash
cd c:\Users\user\Herd\library
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

**What it does:**
- Downloads all PHP packages listed in `composer.json`
- Installs Laravel framework, Inertia.js, Spatie permissions, and other dependencies
- Creates `vendor/` directory with all dependencies
- Generates autoload files

**Troubleshooting:** If you encounter permission errors on Windows, run Command Prompt as Administrator.

### Step 3: Install JavaScript Dependencies

```bash
npm install
```

**What it does:**
- Installs all npm packages for frontend development
- Sets up Vite build tools, Tailwind CSS, React, and development utilities
- Creates `node_modules/` directory

### Step 4: Create Environment Configuration File

#### Option A: Using Command (Windows)
```bash
copy .env.example .env
```

#### Option B: Using Command (Mac/Linux)
```bash
cp .env.example .env
```

#### Option C: Manual Copy
- Find `.env.example` in the project root
- Copy it and rename to `.env`

### Step 5: Generate Application Encryption Key

```bash
php artisan key:generate
```

**What it does:**
- Generates a secure `APP_KEY` in your `.env` file
- This key is used to encrypt sensitive data (sessions, cookies, etc.)
- Creates a unique key for your installation

**Output:** You should see:
```
Application key set successfully.
```

### Step 6: Configure Database Connection

Edit the `.env` file and update the database section. Here are the key variables:

#### For Local Development with XAMPP:

```env
# Application Name
APP_NAME=SNHS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_mgmt
DB_USERNAME=root
DB_PASSWORD=
```

#### For Laravel Herd:

```env
# Application Name
APP_NAME=SNHS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://snhs.test  # Or http://127.0.0.1:8000

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_mgmt
DB_USERNAME=root
DB_PASSWORD=  # Herd uses no password by default
```

**Herd Tip:** You can access your project at `http://snhs.test` if you add it to Herd's site management.

#### For Remote Database:

```env
DB_CONNECTION=mysql
DB_HOST=your-database-host.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_secure_password
```

#### Configuration Details:

| Variable | Description | Example |
|----------|-------------|---------|
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database server address | `127.0.0.1` (local) or IP/hostname |
| `DB_PORT` | Database port | `3306` (default) |
| `DB_DATABASE` | Database name to create/use | `library_mgmt` |
| `DB_USERNAME` | Database user | `root` (XAMPP/Herd default) or your user |
| `DB_PASSWORD` | Database password | Empty for XAMPP/Herd, or your password |

### Step 7: Create Database

#### Using MySQL Command:

```bash
mysql -u root -p -e "CREATE DATABASE library_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

#### Using Laravel Herd:

If you're using Laravel Herd, you can use Herd's database management:

```bash
# Via Herd CLI (if available)
herd database create library_mgmt
```

Or use the Herd dashboard:
1. Open Herd dashboard
2. Go to "Databases" section
3. Click "Create Database"
4. Enter name: `library_mgmt`

#### Or through MySQL client:

1. Open MySQL Workbench or phpMyAdmin
2. Create new database named `library_mgmt`
3. Use UTF8MB4 encoding

### Step 8: Run Database Migrations

```bash
php artisan migrate
```

**What it does:**
- Creates all necessary database tables
- Sets up schema for books, users, teachers, borrows, audit logs, backups, and more
- Ensures your database structure matches the application requirements

**Tables created include:**
- `books` - Book catalog
- `book_copies` - Individual copy tracking
- `users` - Student records
- `teachers` - Teacher records
- `system_users` - Admin/staff accounts
- `borrows` - Borrow/return transactions
- `lost_damaged_items` - Loss/damage tracking
- `audit_logs` - Activity audit trail
- `book_audit_events` - Book change history
- And others for caching, sessions, queues

**Output:** You should see:
```
Migrating: 2024_01_01_000000_create_users_table
Migrated:  2024_01_01_000000_create_users_table (xxx.xx ms)
...
```

### Step 9: Seed Initial Data (Optional)

To populate the database with sample data and create a default admin account:

```bash
php artisan db:seed
```

**What gets created:**
- **Admin Account:** 
  - Email: `admin@example.com`
  - Password: `password`
  - Role: Administrator
- **Sample Students:** Multiple student records for testing
- **Sample Books:** Book catalog with various titles
- **Sample Teachers:** Teacher accounts

⚠️ **Warning:** Do NOT use these credentials in production. Change them immediately after first login.

### Step 10: Build Frontend Assets

```bash
npm run build
```

**What it does:**
- Compiles Tailwind CSS from source
- Bundles JavaScript assets for production use
- Creates optimized static files in `public/` directory

**For development (with hot reload):**
```bash
npm run dev
```

This starts the Vite development server with hot module replacement.

---

## 🧪 Testing the Setup

### Check All Configurations

```bash
php artisan config:show
```

This displays your current configuration. Verify:
- `APP_DEBUG=true` for local development
- `DB_CONNECTION=mysql`
- Correct database credentials

### Test Database Connection

```bash
php artisan db:show
```

Should display your database name and tables. Example output:
```
+----+-----+--------+
| Connection | Driver | Database |
+----+-----+--------+
| mysql | mysql | library_mgmt |
+----+-----+--------+
```

### Run Tests (if available)

```bash
composer run test
```

This runs all PHPUnit tests if they exist.

---

## 🎯 Running the Application

### Method 1: Using the Composer Script (Recommended)

This starts all required services concurrently:

```bash
composer run dev
```

**Services started:**
1. **PHP Development Server** - `http://127.0.0.1:8000`
2. **Queue Listener** - Processes background jobs
3. **Log Tailing** - Displays real-time logs
4. **Vite Dev Server** - Hot module reloading for assets

**Output:**
```
[server] Laravel development server started: http://127.0.0.1:8000
[vite] ✓ built in xxx ms
```

Open your browser and go to `http://127.0.0.1:8000`

**To stop:** Press `Ctrl + C`

### Method 2: Manual Terminal Setup (Advanced)

If you prefer to run services in separate terminals:

**Terminal 1 - Start PHP Server:**
```bash
php artisan serve
```

**Terminal 2 - Start Queue Listener:**
```bash
php artisan queue:listen --tries=1
```

**Terminal 3 - View Real-time Logs:**
```bash
php artisan pail
```

**Terminal 4 - Start Vite Dev Server:**
```bash
npm run dev
```

All terminals should be open and running. Open `http://127.0.0.1:8000` in your browser.

---

## 👤 Creating the First Admin Account

### Option 1: Using the Web Interface

1. Stop the development server if running
2. Visit: `http://127.0.0.1:8000/create-admin`
3. Fill in the form:
   - **Name:** Enter full name
   - **Email:** Enter admin email
   - **Password:** Choose secure password (min 8 characters)
   - **Role:** Select "Admin"
4. Click "Create Admin"
5. You'll be automatically logged in

### Option 2: Using Artisan Command

```bash
php artisan tinker
>>> App\Models\SystemUser::create([
    'name' => 'Admin Name',
    'email' => 'admin@example.com',
    'password' => bcrypt('securePassword123'),
    'role' => 'admin'
]);
>>> exit
```

### Option 3: Using Database Seeder

```bash
php artisan db:seed --class=AdminSeeder
```

Creates admin with email `admin@example.com` and password `password`.

⚠️ **Security Note:** Change default credentials immediately in production.

---

## 📊 Database Seeding

### Seed Everything (Recommended for Development)

```bash
php artisan db:seed
```

Creates:
- 1 Admin user (admin@example.com, password: password)
- 50 Sample students with realistic data
- 30 Sample books with ISBNs and categories
- 5 Sample teachers
- Various borrowing transactions

### Seed Specific Data

```bash
# Seed only admin
php artisan db:seed --class=AdminSeeder

# Seed only students
php artisan db:seed --class=UserSeeder

# Seed only books
php artisan db:seed --class=BookSeeder

# Seed only teachers
php artisan db:seed --class=TeacherSeeder
```

### Reset Database and Reseed

```bash
php artisan migrate:fresh --seed
```

⚠️ **Warning:** This deletes all data and recreates the database from scratch.

---

## 🔧 Common Issues & Troubleshooting

### Herd-Specific Issues

#### MySQL Not Running in Herd

**Error:** Connection refused or MySQL not found

**Solution:**
1. Open Herd dashboard
2. Check if MySQL service is running (should have a green indicator)
3. If not, click to start it
4. Verify connection: `mysql -u root -e "SELECT 1;"`

#### Herd Services Not Starting

**Error:** Can't connect to services

**Solution:**
1. Restart Herd from your system tray/menu bar
2. Or restart individual services from Herd dashboard
3. Check Herd logs for specific errors

#### Database Port Conflict

**Error:** Port 3306 already in use

**Solution:**
1. If using both Herd and XAMPP, disable one
2. Change port in `.env`:
   ```env
   DB_PORT=3307  # Use different port
   ```
3. Verify in Herd dashboard what port MySQL is using

#### Accessing Project URL

**Issue:** Can't access `http://snhs.test`

**Solution:**
1. Add to Herd's site management:
   - Open Herd dashboard
   - Add new site pointing to project directory
   - Herd will automatically create the `.test` domain
2. Or use: `http://127.0.0.1:8000` instead

---

### General Issues

#### Issue 1: "PDOException: SQLSTATE[HY000]: General error: 15 'Readonly database'"

**Cause:** Database write permissions issue

**Solution:**
```bash
# Check database permissions
mysql -u root -p library_mgmt -e "SHOW GRANTS FOR 'root'@'localhost';"

# Or reset permissions
php artisan cache:clear
php artisan config:clear
php artisan migrate:refresh --seed
```

### Issue 2: "The APP_KEY is missing from your .env file"

**Cause:** App key not generated

**Solution:**
```bash
php artisan key:generate
```

### Issue 3: "SQLSTATE[HY000]: General error: 2006 MySQL has gone away"

**Cause:** MySQL connection timeout or server crashed

**Solution:**
1. Check if MySQL is running:
   - Windows: Open XAMPP Control Panel and start MySQL
   - Mac: `brew services restart mysql`
   - Linux: `sudo systemctl restart mysql`

2. Test connection:
   ```bash
   mysql -u root -p -e "SELECT 1;"
   ```

3. Rebuild connection:
   ```bash
   php artisan migrate:refresh --seed
   ```

### Issue 4: "npm: command not found"

**Cause:** Node.js or npm not installed

**Solution:**
1. Download and install from [nodejs.org](https://nodejs.org)
2. Restart your terminal/command prompt
3. Verify installation: `npm -v`

### Issue 5: "Composer require fatal error: Allowed memory exhausted"

**Cause:** PHP memory limit too low

**Solution:**
```bash
php -d memory_limit=-1 composer install
```

Or set PHP memory limit in `php.ini`:
```ini
memory_limit = 512M
```

### Issue 6: Port 8000 Already in Use

**Cause:** Another application using port 8000

**Solution:**
```bash
# Use different port
php artisan serve --port=8001
```

Or find and stop the process using port 8000:
- Windows: `netstat -ano | findstr :8000`
- Mac/Linux: `lsof -i :8000`

### Issue 7: "Class 'Spatie\Permission\PermissionRegistrar' not found"

**Cause:** Spatie permissions package not installed properly

**Solution:**
```bash
composer install
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

---

## 📁 Project Structure After Setup

After successful setup, your project structure should look like:

```
library/
├── app/                          # Application code
│   ├── Models/                   # Eloquent models (Book, Borrow, etc.)
│   ├── Http/Controllers/         # Request handlers
│   ├── Console/Commands/         # Artisan commands
│   └── Observers/                # Model observers (audit tracking)
├── bootstrap/                    # Bootstrap configuration
├── config/                       # Configuration files (app.php, database.php, etc.)
├── database/
│   ├── migrations/               # Database schema migrations
│   └── seeders/                  # Data seeders
├── public/                       # Web root (CSS, JS, images)
├── resources/
│   ├── views/                    # Blade templates
│   ├── css/                      # Tailwind CSS
│   └── js/                       # React/JavaScript
├── routes/                       # Route definitions (web.php)
├── storage/                      # Backups, logs, cache
├── tests/                        # PHPUnit tests
├── vendor/                       # PHP dependencies (created by composer install)
├── node_modules/                 # JavaScript dependencies (created by npm install)
├── .env                          # Environment configuration (created by you)
└── composer.json                 # PHP dependencies definition
```

---

## 🌐 Accessing the Application

### URLs to Access

| Page | URL | Purpose |
|------|-----|---------|
| **Home/Dashboard** | `http://127.0.0.1:8000` | Main dashboard |
| **Login** | `http://127.0.0.1:8000/login` | Admin/Staff login |
| **Create Admin** | `http://127.0.0.1:8000/create-admin` | Create first admin |
| **Books** | `http://127.0.0.1:8000/books` | Book catalog |
| **Students** | `http://127.0.0.1:8000/users` | Student management |
| **Teachers** | `http://127.0.0.1:8000/teachers` | Teacher management |
| **Transactions** | `http://127.0.0.1:8000/borrow` | Borrow/return management |
| **Reports** | `http://127.0.0.1:8000/dashboard/reports` | Transaction reports |
| **Utilities** | `http://127.0.0.1:8000/utilities` | Backups, logs, archive |

### Default Credentials (After Seeding)

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@example.com` | `password` |

⚠️ **Change these immediately after first login!**

---

## 💾 Backup & Recovery

The system includes built-in backup functionality:

### Manual Backup
```bash
php artisan backup:database
```

Creates timestamped ZIP files in `storage/app/backups/`

### Automatic Backups
Set up Windows Task Scheduler or cron job to run daily:
```bash
php artisan backup:database --retention=30
```

This keeps backups for 30 days automatically.

See [BACKUP_SETUP_GUIDE.md](../../BACKUP_SETUP_GUIDE.md) for detailed setup.

---

## 📚 Next Steps

1. **Read Project Overview:** [documentation/01-overview/project-summary.md](../01-overview/project-summary.md)
2. **Review Architecture:** [documentation/01-overview/architecture.md](../01-overview/architecture.md)
3. **Check Backend Routing:** [documentation/03-backend/routing-map.md](../03-backend/routing-map.md)
4. **Review Features:**
   - Transaction Status Tracking: [TRANSACTION_STATUS_TRANSITIONS.md](../../TRANSACTION_STATUS_TRANSITIONS.md)
   - Audit System: [AUDIT_SYSTEM_EXPLANATION.md](../../AUDIT_SYSTEM_EXPLANATION.md)
   - Copy Counting: [COPY_COUNT_IMPLEMENTATION.md](../../COPY_COUNT_IMPLEMENTATION.md)
   - Reports Module: [REPORTS_MODULE_UPDATES.md](../../REPORTS_MODULE_UPDATES.md)
5. **Troubleshoot Issues:** [troubleshooting.md](./troubleshooting.md)
6. **Learn Deployment:** [documentation/06-operations/deployment.md](../06-operations/deployment.md)

---

## 🆘 Getting Help

If you encounter issues:

1. **Check logs:** `storage/logs/laravel.log`
2. **Check errors:** Run `php artisan config:clear && php artisan cache:clear`
3. **Database issues:** Check `DB_*` variables in `.env`
4. **Frontend issues:** Check browser console for JavaScript errors
5. **See troubleshooting:** [troubleshooting.md](./troubleshooting.md)
