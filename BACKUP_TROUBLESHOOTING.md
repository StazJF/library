# Task Scheduler Backup Troubleshooting Guide

## ⚡ Quick Fix (Most Common Issue)

**The problem is usually: PHP is not in your Windows PATH**

Task Scheduler can't find `php.exe` because it's running in a limited context.

### Solution: Use Full PHP Path

1. **Find where PHP is installed:**
   ```
   Open PowerShell and type:
   where.exe php
   
   Note the full path (e.g., C:\xampp\php\php.exe)
   ```

2. **Update Task Scheduler:**
   - Open Task Scheduler
   - Find your "Database Backup - Daily" task
   - Right-click → **Edit**
   - Go to **Actions** tab
   - Click **Edit** on the action
   - Change **Program/script** from: `php.exe`
   - To: Full path like `C:\xampp\php\php.exe`
   - Click **OK** → **OK**

3. **Test it:**
   - Right-click task → **Run**
   - Check if backup appears in Utilities → Database Backups

---

## 🔍 Step-by-Step Diagnostic

Run this to identify the exact problem:

### Step 1: Run the Diagnostic Script

1. Navigate to `C:\Users\<you>\Herd\library`
2. Right-click **BACKUP_DIAGNOSTICS.bat**
3. Select **Run as administrator**
4. It will test each component and show results

This checks:
- ✓ PHP availability
- ✓ Laravel project
- ✓ Backup directory
- ✓ Database command
- ✓ Laravel logs

---

## 📋 Manual Testing (Safest Way)

### Test 1: Command Line Test

**Open PowerShell as Administrator:**

```powershell
cd C:\Users\<you>\Herd\library
php artisan backup:database
```

**Expected output:**
```
Starting database backup: backup_2026-04-13_14-30-45_123456
SQL dump created: 45.23 MB
Backup compressed: 3.45 MB
✓ Backup completed successfully!
```

**If error:**
- Check your `.env` file for MySQL credentials
- Verify MySQL server is running
- Try: `php artisan tinker` to test Laravel works

---

### Test 2: Check Laravel Logs

```powershell
# Show last 50 lines of Laravel log
Get-Content storage\logs\laravel.log -Tail 50
```

Look for any error messages about the backup.

---

### Test 3: Direct PHP Path Test

If `php artisan...` doesn't work, try with direct path:

**First, find PHP:**
```powershell
where.exe php
```

**Then test with full path:**
```powershell
C:\xampp\php\php.exe artisan backup:database
```

(Replace `C:\xampp\php\php.exe` with your actual path)

---

## ❌ Common Issues & Fixes

### Issue 1: "php is not recognized"

**Cause:** PHP is not in system PATH

**Fix:**
```powershell
# Find PHP path
where.exe php

# Use full path in command
C:\xampp\php\php.exe artisan backup:database
```

---

### Issue 2: "Backup failed with return code: 1"

**Cause:** MySQL/database error

**Fix:**
1. Verify MySQL is running
2. Check `.env` for correct credentials:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=yourdb
   DB_USERNAME=root
   DB_PASSWORD=yourpass
   ```
3. Test connection:
   ```powershell
   mysql -h 127.0.0.1 -u root -p yourdb -e "SELECT 1;"
   ```

---

### Issue 3: "Storage directory does not exist"

**Cause:** `storage/app/backups/` not created

**Fix:**
```powershell
mkdir "C:\Users\<you>\Herd\library\storage\app\backups"
```

---

### Issue 4: "Access Denied" errors

**Cause:** Permissions issue on backup directory

**Fix:**
1. Right-click `storage/app/backups` folder
2. **Properties** → **Security** tab
3. Click **Edit**
4. Select your user
5. Check "Modify" and "Write" boxes
6. Click **Apply** → **OK**

---

### Issue 5: Extracted `.sql` file is empty (0 KB)

Open the ZIP with **7-Zip** and check `backup_info.txt` inside the archive.

- If `SQL size bytes` in `backup_info.txt` is **0**:
  - The dump was generated as empty (export failed or the DB truly has no tables/data).
  - Check `storage/logs/backup-task.log` (scheduler) or `storage/logs/laravel.log` (app) for errors.
  - Re-check `.env` MySQL settings and confirm MySQL is running.

- If `SQL size bytes` in `backup_info.txt` is **greater than 0**, but the extracted file is empty:
  - You likely extracted with the **wrong password** or a tool that failed extraction.
  - In 7-Zip, use **Test** on the archive, then extract again to a new empty folder and re-enter the password.

## 🎯 Fix Task Scheduler Configuration

If manual command works but Task Scheduler doesn't:

### Step 1: Open Task Scheduler

```
Windows + R → taskschd.msc → Enter
```

### Step 2: Find Your Task

Right-click **"Database Backup - Daily"** → **Properties**

### Step 3: Fix the General Tab

- ✓ **"Run only when user is logged in"** OR
- ✓ **"Run whether user is logged in or not"**
  - If you choose the second option, you may need to provide password
  
- ✓ **"Run with highest privileges"**

Click **OK**

### Step 4: Fix the Actions Tab

Click **Edit** on the backup action:

**Program/script field should be:**
```
C:\xampp\php\php.exe
```
(Replace with your actual PHP path from `where.exe php`)

**Add arguments:**
```
artisan backup:database --retention=30
```

**Start in (optional):**
```
C:\Users\<you>\Herd\library
```

Click **OK** twice

### Step 5: Test It

Right-click task → **Run**

Wait 5-10 seconds, then refresh browser to Utilities → Database Backups

---

## ✅ Verification Checklist

- [ ] Ran manual test: `php artisan backup:database` works
- [ ] Found correct PHP path: `where.exe php`
- [ ] Updated Task Scheduler with PHP full path
- [ ] Checked `.env` file for correct credentials
- [ ] MySQL server is running
- [ ] `storage/app/backups/` directory exists and is writable
- [ ] Task Scheduler History shows successful completion
- [ ] Backup file appears in Utilities → Database Backups

---

## 📊 Verify Task Ran Successfully

### Check 1: Task Scheduler History

1. Open Task Scheduler
2. Select **Task Scheduler Library**
3. Find your task
4. Click the **History** tab
5. Look for the most recent entry
6. It should say:
   - Status: **"The task completed with an exit code of (0)."**
   - Time matches your scheduled time

### Check 2: Backup Directory

```powershell
ls C:\Users\<you>\Herd\library\storage\app\backups\
```

Should show recent backup files like:
```
backup_2026-04-13_14-30-45_123456.zip
backup_2026-04-13_14-30-46_987654.zip
```

### Check 3: Activity Log

In your app:
1. Go to **Utilities → Activity Log**
2. Filter for "Automated Database Backup"
3. Should show recent entries

---

## 🆘 Still Not Working?

1. **Run diagnostic script:** `BACKUP_DIAGNOSTICS.bat` (as admin)
2. **Share the output** with me
3. **Share error from:** `storage/logs/laravel.log`
4. **Double-check:** `.env` database credentials

---

## Quick Reference: Working Configuration

If set up correctly, your Task Scheduler should have:

| Field | Value |
|-------|-------|
| **Task Name** | Database Backup - Daily |
| **Trigger** | Daily at 2:00 AM (or your chosen time) |
| **Program** | `C:\xampp\php\php.exe` (your PHP path) |
| **Arguments** | `artisan backup:database --retention=30` |
| **Start in** | `C:\Users\<you>\Herd\library` |
| **Run with highest privileges** | ✓ Checked |
| **Repeat task every** | Optional (not needed for daily) |

---

**Next Step:** Run the diagnostic script to get started!
