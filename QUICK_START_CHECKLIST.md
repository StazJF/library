# Quick Start Checklist - Setup & First Run

Use this checklist to quickly set up the SNHS Library Management System for local development.

---

## ⚙️ Pre-Setup Verification

**Option 1: Manual Setup (XAMPP/Traditional)**

- [ ] **PHP 8.2+** - Run `php -v`
- [ ] **Composer** - Run `composer -v`
- [ ] **MySQL 8.0+** - Run `mysql --version`
- [ ] **Node.js 18+** - Run `node -v`
- [ ] **npm 9+** - Run `npm -v`
- [ ] **Git** - Run `git --version`

**Option 2: Laravel Herd (Recommended - Faster)**

- [ ] **Laravel Herd** installed from [herd.laravel.com](https://herd.laravel.com)
- [ ] **Composer** - Run `composer -v`
- [ ] **Node.js 18+** - Run `node -v`
- [ ] **npm 9+** - Run `npm -v`
- [ ] **Git** - Run `git --version`

**Herd Advantage:** ✅ Skip PHP and MySQL installation - they're included!

**If any fail:** 
- **Manual Setup:** Install from [nodejs.org](https://nodejs.org), [getcomposer.org](https://getcomposer.org), [php.net](https://www.php.net), [dev.mysql.com](https://dev.mysql.com)
- **Herd Setup:** Just install Herd and Node.js

---

## 📥 Project Setup (First Time Only)

## 📥 Project Setup (First Time Only)

### Quick Setup with Laravel Herd (Recommended)

```bash
# 1. Install Herd from herd.laravel.com if not already installed

# 2. Clone project into ~/Herd directory (or wherever you keep projects)
cd ~/Herd
git clone https://github.com/Jimnastic123/SNHS.git
cd SNHS

# 3. Install PHP and JS dependencies
composer install
npm install

# 4. Create environment file
cp .env.example .env

# 5. Generate app key
php artisan key:generate

# 6. Create database
# Use Herd dashboard or run:
mysql -u root -e "CREATE DATABASE library_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Run migrations and seeds
php artisan migrate
php artisan db:seed

# 8. Start development
composer run dev
```

✅ **Done!** Access at `http://127.0.0.1:8000`

---

### Manual Setup with XAMPP (Traditional)

```bash
# 1. Clone or navigate to project
cd c:\Users\user\Herd\library
# OR: git clone https://github.com/Jimnastic123/SNHS.git

# 2. Install PHP dependencies
composer install

# 3. Install JavaScript dependencies
npm install

# 4. Copy environment file
copy .env.example .env

# 5. Generate encryption key
php artisan key:generate

# 6. Edit .env database settings
# See section below for configuration
```

---

## 🗄️ Database Configuration

---

## 🗂️ Database Setup (First Time Only)

```bash
# 1. Run migrations (creates all tables)
php artisan migrate

# 2. (Optional) Seed sample data
php artisan db:seed

# This creates:
# - Admin account: admin@example.com / password
# - 50 sample students
# - 30 sample books
# - 5 sample teachers
# - Borrowing transactions
```

---

## 🛠️ Build Assets

```bash
# For development (with hot reload):
npm run build

# OR for production:
npm run build
```

---

## 🚀 Start Development Environment

### Recommended Method (All-in-one):
```bash
composer run dev
```

This starts:
- ✓ PHP development server (http://127.0.0.1:8000)
- ✓ Queue listener
- ✓ Log viewer
- ✓ Vite dev server (hot reload)

**Press `Ctrl + C` to stop**

### Alternative (Manual - 4 terminals):

**Terminal 1:**
```bash
php artisan serve
```

**Terminal 2:**
```bash
php artisan queue:listen --tries=1
```

**Terminal 3:**
```bash
php artisan pail
```

**Terminal 4:**
```bash
npm run dev
```

---

## 🔑 Create First Admin Account

### Option 1 (Recommended - Web Interface):
1. Stop development server (press `Ctrl + C`)
2. Visit: `http://127.0.0.1:8000/create-admin`
3. Fill in the form
4. Click "Create Admin"
5. You'll be logged in automatically

### Option 2 (Command Line):
```bash
php artisan db:seed --class=AdminSeeder
```

**Credentials:** 
- Email: `admin@example.com`
- Password: `password`

### Option 3 (Already seeded):
If you ran `php artisan db:seed`, admin already exists:
- Email: `admin@example.com`
- Password: `password`

---

## ✅ Verification Steps

After starting the application:

1. **Check services:**
   ```bash
   # In new terminal
   curl http://127.0.0.1:8000
   ```
   Should return HTML (no error)

2. **Access dashboard:**
   - Open: `http://127.0.0.1:8000`
   - Click "Login" 
   - Use admin credentials

3. **Check features:**
   - Dashboard: `http://127.0.0.1:8000/dashboard`
   - Books: `http://127.0.0.1:8000/books`
   - Students: `http://127.0.0.1:8000/users`
   - Teachers: `http://127.0.0.1:8000/teachers`
   - Reports: `http://127.0.0.1:8000/dashboard/reports`
   - Utilities: `http://127.0.0.1:8000/utilities`

---

## 🔧 Common Quick Fixes

### Port 8000 Already in Use
```bash
php artisan serve --port=8001
```

### Database Connection Error
```bash
# Verify MySQL is running
mysql -u root -p -e "SELECT 1;"

# Check .env credentials
cat .env | grep DB_

# Clear cache
php artisan cache:clear
php artisan config:clear
```

### "npm: command not found"
- Restart terminal after installing Node.js
- Or reinstall Node.js from [nodejs.org](https://nodejs.org)

### Missing tables
```bash
php artisan migrate
```

### Assets not loading (404 errors)
```bash
npm run build
npm run dev
```

---

## 📝 Environment Variables Needed

**Minimum for development:**
```env
APP_NAME=SNHS
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=base64:xxxxxx  # Generated by php artisan key:generate

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=library_mgmt
DB_USERNAME=root
DB_PASSWORD=
```

See [env-vars.md](documentation/02-setup/env-vars.md) for complete reference.

---

## 📚 Documentation Links

| Topic | Link |
|-------|------|
| Full Setup | [Local Setup Guide](documentation/02-setup/local-setup.md) |
| Architecture | [System Architecture](documentation/01-overview/architecture.md) |
| Features | [Project Summary](documentation/01-overview/project-summary.md) |
| Troubleshooting | [Troubleshooting Guide](documentation/02-setup/troubleshooting.md) |
| Env Vars | [Environment Variables](documentation/02-setup/env-vars.md) |
| Transactions | [Transaction Status](TRANSACTION_STATUS_TRANSITIONS.md) |
| Audit System | [Audit System](AUDIT_SYSTEM_EXPLANATION.md) |
| Backups | [Backup Setup](BACKUP_SETUP_GUIDE.md) |

---

## ⏱️ Estimated Time

- **First-time setup:** 30-45 minutes
  - Prerequisites check: 5 min
  - Composer install: 5-10 min
  - npm install: 5-10 min
  - Database setup: 5 min
  - Asset build: 5 min
  - Verification: 5 min

- **Subsequent starts:** 30 seconds
  - Just run: `composer run dev`

---

## 🎉 Success Indicators

You're ready when:
- ✓ `php artisan config:show` shows no errors
- ✓ `php artisan db:show` displays database info
- ✓ Can access `http://127.0.0.1:8000`
- ✓ Can log in with admin credentials
- ✓ All pages load without 404 errors
- ✓ Dashboard shows statistics
- ✓ Can see books, students, reports

---

## 💡 Tips

1. **Keep everything running:** Don't close the `composer run dev` terminal while developing
2. **Check logs:** `storage/logs/laravel.log` for debugging
3. **Clear cache often:** `php artisan cache:clear`
4. **Use tinker for testing:** `php artisan tinker`
5. **Watch terminal output:** Queue and log viewers show real-time info

---

## 🆘 Need Help?

1. Check: [Troubleshooting Guide](documentation/02-setup/troubleshooting.md)
2. Check logs: `storage/logs/laravel.log`
3. Review: [Local Setup](documentation/02-setup/local-setup.md) step you're on
4. Search: Error message in troubleshooting guide

---

**Last Updated:** May 11, 2026  
**For:** SNHS Library Management System
