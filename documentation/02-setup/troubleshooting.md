**Troubleshooting Guide**

This guide covers common issues and their solutions for the SNHS Library Management System.

---

## 🔗 Database Connection Issues

### MySQL Connection Errors

**Error:** `SQLSTATE[HY000]: General error: 15 'Readonly database'` or `SQLSTATE[HY000]: Can't connect to MySQL server`

**Causes & Solutions:**

1. **MySQL Server Not Running**
   - Verify database is running:
     ```bash
     mysql -u root -p -e "SELECT 1;"
     ```
   - Start MySQL:
     - Windows (XAMPP): Open XAMPP Control Panel → Click "Start" for MySQL
     - Mac: `brew services start mysql-server`
     - Linux: `sudo systemctl start mysql`

2. **Incorrect Credentials in .env**
   - Check `.env` file has correct values:
     ```env
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=library_mgmt
     DB_USERNAME=root
     DB_PASSWORD=
     ```
   - Clear Laravel cache and retry:
     ```bash
     php artisan cache:clear
     php artisan config:clear
     php artisan migrate
     ```

3. **Database Doesn't Exist**
   - Create the database:
     ```bash
     mysql -u root -p -e "CREATE DATABASE library_mgmt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
     ```
   - Then run migrations:
     ```bash
     php artisan migrate
     ```

4. **MySQL Connection Timeout**
   - Error: `SQLSTATE[HY000]: General error: 2006 MySQL has gone away`
   - Solutions:
     ```bash
     # Restart MySQL
     sudo systemctl restart mysql
     
     # Run migrations again
     php artisan migrate
     ```

---

## 📦 Migration & Database Issues

### Missing Database Tables

**Error:** `SQLSTATE[42S02]: Table 'library_mgmt.table_name' doesn't exist`

**Solution:**
1. Run all migrations:
   ```bash
   php artisan migrate
   ```
2. If that fails, refresh from scratch:
   ```bash
   php artisan migrate:refresh --seed
   ```

### Table Already Exists

**Error:** `SQLSTATE[42S01]: Base table or view already exists`

**Solution:**
1. Fresh migration (wipes and recreates all tables):
   ```bash
   php artisan migrate:refresh
   ```
2. If you want to keep data, manually fix conflicts or rollback one migration:
   ```bash
   php artisan migrate:rollback --step=1
   php artisan migrate
   ```

### Migration Memory Issues

**Error:** `Allowed memory exhausted` during migrations

**Solution:**
```bash
php -d memory_limit=512M artisan migrate
```

Or permanently increase in `php.ini`:
```ini
memory_limit = 512M
```

### Numeric Value Out of Range

**Error:** `SQLSTATE[22003]: Numeric value out of range: 1264 Out of range value for column 'copies'`

**Cause:** Books CSV import has formatting issues or database column is wrong type

**Solution:**
1. Ensure `copies` column is INTEGER type:
   ```bash
   php artisan migrate  # Runs copy column fix migration
   ```

2. Verify CSV format is correct:
   - Use quoted values for titles with commas
   - Format: `"title","author","publisher","isbn","category","copies"`
   - Example: `"Force, Motion, and Energy","David Wilson","Press","9780000000007","SCIENCE","5"`

---

## 🚀 Server & Port Issues

### Port 8000 Already in Use

**Error:** `Address already in use (in use by XXXX)`

**Solution:**

Option 1: Use different port:
```bash
php artisan serve --port=8001
```

Option 2: Kill process using port 8000:

Windows:
```cmd
netstat -ano | findstr :8000
taskkill /PID <PID> /F
```

Mac/Linux:
```bash
lsof -i :8000
kill -9 <PID>
```

### Vite Dev Server Port 5173 Conflicts

**Error:** Vite cannot bind to port 5173

**Solution:**
```bash
npm run dev -- --host 127.0.0.1 --port 5174
```

### Asset Files Not Loading (404 Errors)

**Error:** CSS/JS not loading, 404 errors in browser console

**Cause:** Vite dev server not running or assets not compiled

**Solution:**
1. Make sure Vite is running:
   ```bash
   npm run dev
   ```
   
2. In development mode, ensure `APP_DEBUG=true` in `.env`

3. For production, build assets:
   ```bash
   npm run build
   ```

---

## 🔑 Authentication & Authorization Issues

### "APP_KEY is missing from .env file"

**Error:** `RuntimeException: The APP_KEY is missing from your .env file`

**Solution:**
```bash
php artisan key:generate
```

### Cannot Login / Session Issues

**Causes & Solutions:**

1. **No admin account exists**
   - Visit `http://127.0.0.1:8000/create-admin` to create first admin
   - Or seed database:
     ```bash
     php artisan db:seed --class=AdminSeeder
     ```

2. **Session table missing**
   - Run migrations:
     ```bash
     php artisan migrate
     ```

3. **Cache corruption**
   - Clear cache and sessions:
     ```bash
     php artisan cache:clear
     php artisan config:clear
     php artisan session:clean
     ```

4. **Wrong credentials**
   - If using seeded data:
     - Email: `admin@example.com`
     - Password: `password`

### "Unauthenticated" on Every Page

**Error:** Redirects to login on every request

**Causes & Solutions:**

1. **Session driver not configured**
   - Check `.env`:
     ```env
     SESSION_DRIVER=database
     ```
   
2. **Cookies blocked by browser**
   - Check browser settings allow cookies
   - Test in incognito/private mode

3. **Clock skew (for JWT if used)**
   - Sync system time

---

## 📚 Book Import & Data Issues

### Excel File Not Accepted

**Error:** `The uploaded file must be a CSV file`

**Cause:** System only accepts CSV format, not Excel (.xlsx)

**Solution:**
1. Open Excel file
2. Save As → CSV (Comma delimited) format
3. Upload CSV file

### Duplicate ISBN Error

**Error:** `SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry`

**Cause:** Attempting to import book with ISBN already in system

**Solution:**
1. Check existing books for that ISBN:
   ```bash
   php artisan tinker
   >>> App\Models\Book::where('isbn', 'YOUR_ISBN')->first();
   ```

2. Either:
   - Remove from CSV
   - Delete existing book if mistake
   - Use different ISBN if corrected

### CSV Parsing Errors

**Error:** Values in wrong columns, import fails with validation errors

**Cause:** CSV has unquoted commas in titles or improper formatting

**Solution:**

Use properly quoted CSV:
```csv
"title","author","publisher","isbn","category","copies"
"Force, Motion, and Energy","David Wilson","Press","9780000000007","SCIENCE","5"
"The Great Gatsby","F. Scott Fitzgerald","Scribner","9780743273565","ENGLISH","3"
```

Bad format (will fail):
```csv
title,author,publisher,isbn,category,copies
Force, Motion, and Energy,David Wilson,Press,9780000000007,SCIENCE,5
```

---

## 📊 Reports & Transaction Issues

### Transaction Status Not Updating

**Error:** Lost/Damaged status doesn't change when marked as repaired/found

**Cause:** Status not being properly tracked in `LostDamagedItemHistory`

**Solution:**
1. Verify `lost_damaged_item_histories` table exists:
   ```bash
   php artisan migrate
   ```

2. Check transaction status calculation:
   ```bash
   php artisan tinker
   >>> $borrow = App\Models\Borrow::with('lostDamagedItem')->find(ID);
   >>> $borrow->getTransactionStatus();
   ```

### Reports Page Loading Slowly

**Cause:** N+1 query problem or large dataset

**Solution:**
1. Check database indexes on `borrows` table
2. Ensure eager loading is working in `DashboardController`
3. Consider pagination limitations

---

## 📝 Backup & Utility Issues

### Backup Fails: "mysqldump not found"

**Error:** `Command not found` or backup fails

**Cause:** MySQL client tools not in system PATH

**Solution:**

Windows:
1. Find `mysqldump.exe` location (usually `C:\Program Files\MySQL\MySQL Server 8.0\bin\`)
2. Add to System PATH:
   - Right-click Computer → Properties → Environment Variables
   - Add MySQL bin folder to PATH
   - Restart Command Prompt

Mac:
```bash
brew install mysql-client
```

Linux:
```bash
sudo apt-get install mysql-client
```

### Backup File Not Created

**Error:** Backup directory error or no file created

**Cause:** Permission or directory issues

**Solution:**
1. Ensure `storage/app/backups/` directory exists:
   ```bash
   mkdir -p storage/app/backups
   ```

2. Check permissions:
   ```bash
   chmod -R 755 storage/
   ```

3. Test backup command:
   ```bash
   php artisan backup:database --verbose
   ```

### Backups Taking Too Long or Timeout

**Solution:**
1. Set longer timeout in `php.ini`:
   ```ini
   max_execution_time = 300
   ```

2. Run backup manually in background:
   ```bash
   nohup php artisan backup:database > backup.log 2>&1 &
   ```

---

## 🎨 Frontend & Asset Issues

### "npm: command not found"

**Cause:** Node.js or npm not installed

**Solution:**
1. Download Node.js from [nodejs.org](https://nodejs.org)
2. Install (includes npm)
3. Restart terminal/command prompt
4. Verify: `npm -v`

### Dependencies Not Installed

**Error:** Module not found or missing dependency

**Solution:**
```bash
rm -rf node_modules package-lock.json
npm install
npm run dev
```

### Tailwind CSS Not Compiling

**Error:** Styles not applying

**Solution:**
1. Rebuild CSS:
   ```bash
   npm run build
   ```

2. Or in development mode:
   ```bash
   npm run dev
   ```

3. Check `tailwind.config.cjs` includes correct template paths

---

## 💾 Memory & Performance Issues

### "Allowed memory exhausted" During Composer Install

**Solution:**
```bash
php -d memory_limit=-1 composer install
```

### Application Running Slowly

**Causes & Solutions:**

1. **Enable Query Logging**
   ```bash
   php artisan tinker
   >>> DB::enableQueryLog();
   # Run some requests
   >>> DB::getQueryLog();
   ```

2. **Check Indexes**
   ```bash
   SHOW INDEX FROM borrows;
   SHOW INDEX FROM books;
   ```

3. **Clear Cache & Optimize**
   ```bash
   php artisan cache:clear
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

4. **Monitor Queue**
   ```bash
   php artisan queue:failed
   php artisan queue:retry all
   ```

---

## 🔍 Debugging Tips

### Enable Debug Mode

**In `.env`:**
```env
APP_DEBUG=true
APP_LOG_LEVEL=debug
```

### View Logs

```bash
# Real-time tail
php artisan pail

# Or view file
tail -f storage/logs/laravel.log
```

### Database Debugging

```bash
php artisan tinker
>>> DB::enableQueryLog();
>>> DB::getQueryLog();
>>> DB::disableQueryLog();
```

### Test Database Connection

```bash
php artisan db:show
```

Should show your database details.

---

## 📞 When to Contact Support

Document the following information:

1. **Error Message:** Full error text
2. **Stack Trace:** Full stack trace from logs
3. **System Info:** 
   - PHP version: `php -v`
   - MySQL version: `mysql --version`
   - OS: Windows/Mac/Linux version
4. **Steps to Reproduce:** What actions led to the error
5. **Logs:** Content of `storage/logs/laravel.log`

---

## ✅ Verification Checklist

Use this to verify everything is working:

```bash
# 1. Check PHP version
php -v

# 2. Check Composer
composer -v

# 3. Check MySQL
mysql -u root -p -e "SELECT 1;"

# 4. Check npm
npm -v

# 5. Test database connection
php artisan db:show

# 6. Check config
php artisan config:show

# 7. Run tests
composer run test

# 8. Start dev environment
composer run dev
```

If all pass, system is ready to use!
