# 🚀 Quick Fix for Task Scheduler Backup Not Working

## Most Likely Cause

**PHP executable not found by Task Scheduler**

The backup command works when you run it manually because your terminal knows where PHP is. Task Scheduler doesn't have that context.

---

## ⚡ THE FIX (2 Minutes)

### Step 1: Find Your PHP Path

Open PowerShell and run:
```powershell
where.exe php
```

You'll get something like: `C:\xampp\php\php.exe`

**Copy this path** - you'll need it.

### Step 2: Update Task Scheduler

1. Open **Task Scheduler** (`Win + R` → `taskschd.msc` → Enter)
2. Find **"Database Backup - Daily"** task
3. **Right-click** → **Properties**
4. Go to **Actions** tab
5. Select the backup action and click **Edit...**
6. In **Program/script:** field, replace `php.exe` with your full PHP path
   - Example: `C:\xampp\php\php.exe`
7. Click **OK** → **OK** → **OK**

### Step 3: Test It

1. In Task Scheduler, right-click your task
2. Click **Run**
3. Wait 10 seconds
4. Check **Utilities → Database Backups**
5. New backup should be there! ✓

---

## 🔍 If That Didn't Work

### Debug Checklist:

- [ ] Is MySQL running?
- [ ] Are your `.env` database credentials correct?
- [ ] Does the backup directory exist? `storage\app\backups\`
- [ ] Manual command works? `php artisan backup:database`

### Run Diagnostic:

```powershell
cd C:\Users\<you>\Herd\library
C:\BACKUP_DIAGNOSTICS.bat  # Or use your PHP path
```

### Check Logs:

```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

---

## Alternative: Use PowerShell Script (More Reliable)

If the direct approach doesn't work, use the PowerShell script method:

### Step 1: Edit the Script

1. Open: `C:\Users\<you>\Herd\library\backup-script.ps1`
2. Find this line:
   ```powershell
   $PHPPath = "C:\xampp\php\php.exe"  # CHANGE THIS to match your PHP installation
   ```
3. Replace with your actual PHP path (from `where.exe php`)
4. **Save** it

### Step 2: Update Task Scheduler

1. Open **Task Scheduler**
2. Right-click your task → **Properties**
3. Go to **Actions** tab
4. Click **Edit...**
5. Change all fields:
   - **Program/script:** `powershell.exe`
   - **Add arguments:** `-ExecutionPolicy Bypass -File "C:\Users\<you>\Herd\library\backup-script.ps1"`
   - **Start in:** `C:\Users\<you>\Herd\library`
6. Click **OK** → **OK**

### Step 3: Test

Right-click task → **Run** → Wait 10 seconds → Check backups ✓

---

## What to Check Next Time

After setting up Task Scheduler:

1. **Did the task run?**
   - Task Scheduler → History tab → Look for your task
   - Should say: "Task completed with an exit code of (0)"

2. **Is there a backup file?**
   - Navigate to: `storage/app/backups/`
   - Should see files like: `backup_2026-04-13_14-30-45_123456.zip`

3. **Is it logged?**
   - Utilities → Activity Log
   - Filter for "Automated Database Backup"

---

## Files Created for You

- **BACKUP_DIAGNOSTICS.bat** - Run as admin to test everything
- **backup-script.ps1** - PowerShell script for more reliable Task Scheduler
- **BACKUP_TROUBLESHOOTING.md** - Full troubleshooting guide

---

**👉 Try the 2-minute fix first. If it doesn't work, run BACKUP_DIAGNOSTICS.bat to see exact errors.**
