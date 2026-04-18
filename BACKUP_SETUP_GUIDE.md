# Automated Backup Setup Guide

## Overview
Your database backup system now uses a **single backup file** that automatically overwrites with new data. This file is located at:
```
C:\Users\jimmu\Herd\library\storage\app\backups\database_backup.zip
```

**Backup files are password-protected** using AES-256 encryption for security.

## What Changed
✅ **Single Backup File** - `database_backup.zip` overwrites previous backups (no more timestamp files)
✅ **Password-Protected** - All backups are encrypted with AES-256 for security
✅ **PHP-Based Export** - Uses PHP's PDO directly (no MySQL client issues)
✅ **Works Without mysqldump** - Bypasses authentication plugin problems
✅ **Auto-Logging** - Logs all backup operations to the Activity Log

## Manual Backups
To create a backup manually:
1. Open your application in the browser
2. Go to **Utilities → Database Backups**
3. Click **Create New Backup**
4. Wait for completion message
5. Download the backup if needed

## Password Configuration

All backups are automatically encrypted with AES-256 encryption. The backup password is stored in your `.env` file.

### Change Backup Password

1. Open `.env` file:
   ```
   C:\Users\jimmu\Herd\library\.env
   ```

2. Find or add the line:
   ```
   BACKUP_PASSWORD=snhslms
   ```

3. Change `snhslms` to your desired password

4. Save the file

5. New backups will use the new password

**Password Requirements:**
- Minimum 8 characters
- Use a strong password (mix of letters, numbers, special characters)
- Example: `L!br@ry#2024Secure`

### Extract Password-Protected Backup

To restore a password-protected backup:

1. **Using 7-Zip (Recommended):**
   - Right-click the `.zip` file
   - Select **7-Zip → Extract Here**
   - Enter the password when prompted
   - Extract the `.sql` file

2. **Using Windows Explorer:**
   - Double-click the `.zip` file
   - Enter password when prompted
   - Copy the `.sql` file out

3. **Using Command Line:**
   ```powershell
   # Extract with password
   7z x database_backup.zip -p"YourPassword"
   ```

## Automatic Backups via Task Scheduler

### Step 1: Run the Setup Script (RECOMMENDED)

**Easiest Method:**
1. Open PowerShell as Administrator
   - Right-click PowerShell → **Run as Administrator**
2. Run this command:
   ```powershell
   Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process -Force
   C:\Users\jimmu\Herd\library\setup-backup-scheduler.ps1
   ```
3. Follow the prompts
4. Choose backup frequency (Daily or Hourly)
5. When asked, run a test backup

This script will:
- Create the batch file (`backup-task.bat`)
- Register a Windows Task Scheduler task
- Configure it to run automatically
- Create a log file to track executions

### Step 2 (ALTERNATIVE): Manual Setup

If you prefer to set it up manually:

1. **Create batch file** `C:\Users\jimmu\Herd\library\backup-task.bat`:
   ```batch
   @echo off
   cd /d "C:\Users\jimmu\Herd\library"
   C:\xampp\php\php.exe -d display_errors=0 artisan backup:database >> storage\logs\backup-task.log 2>&1
   echo Backup task completed at %date% %time% >> storage\logs\backup-task.log
   ```

2. **Open Task Scheduler**:
   - Press `Windows Key + R`
   - Type: `taskschd.msc`
   - Press Enter

3. **Create Basic Task**:
   - Right-click "Task Scheduler Library" → **Create Basic Task**
   - Name: `LibraryDatabaseBackup`
   - Description: `Daily automated backup of the library database`

4. **Set Trigger**:
   - Click **Next**
   - Select when task should run (Daily recommended)
   - Set time (e.g., 2:00 AM)
   - Click **Next**

5. **Set Action**:
   - Select **Start a program**
   - Program: `batch-task.bat`
   - Start in: `C:\Users\jimmu\Herd\library`
   - Click **Next**

6. **Finish**:
   - Review settings
   - Click **Create**

## Verification

### Check if Backups Are Running

1. **Check the backup file**:
   ```powershell
   Get-ChildItem "C:\Users\jimmu\Herd\library\storage\app\backups\database_backup.zip" | Select-Object LastWriteTime
   ```
   The "LastWriteTime" should update after each backup run.

2. **Check the log file**:
   ```powershell
   Get-Content "C:\Users\jimmu\Herd\library\storage\logs\backup-task.log" -Tail 20
   ```
   Look for success messages like "✓ Backup completed successfully!"

3. **Check Task Scheduler**:
   - Open Task Scheduler (Press `Windows + R`, type `taskschd.msc`)
   - Search for "LibraryDatabaseBackup"
   - Right-click → **View All Properties**
   - Check "Triggers" tab for schedule
   - Check "History" tab for recent runs

### Test Backup Immediately

Run this to test a backup right now:
```powershell
cd C:\Users\jimmu\Herd\library
php artisan backup:database
```

## Scheduling Reference

### For Daily Backups
- **Frequency**: Daily at a specific time (e.g., 2:00 AM)
- **Best practice**: Run during low-traffic hours

### For Hourly Backups  
- **Frequency**: Every N hours
- **Interval**: 1, 2, 3, 6, 12, 24 hours
- **Note**: Uses more disk space; only recommended for critical systems

### For Weekly Backups
- **Frequency**: Specific day and time
- **Example**: Every Monday at 3:00 AM

## Troubleshooting

### Backups Not Running

**Check 1: Task Scheduler not enabled**
```powershell
# View task status
Get-ScheduledTask -TaskName "LibraryDatabaseBackup" | Select-Object State
```

**Fix**: Re-run the setup script or right-click the task in Task Scheduler → Enable

**Check 2: PHP path issue**
- Verify PHP exists: `C:\xampp\php\php.exe`
- Edit `backup-task.bat` if PHP is elsewhere

**Check 3: Permission issues**
- Verify `storage/app/backups/` directory is writable
- Right-click folder → Properties → Security → Permissions

**Check 4: MySQL connection**
- Verify database settings in `.env` file
- Test connection: `php artisan tinker` → `DB::connection()->getPDO()`

### Backup File Not Growing

Check the log for errors:
```powershell
Get-Content "C:\Users\jimmu\Herd\library\storage\logs\backup-task.log"
```

Common issues:
- Database is empty
- Connection failing
- Permissions denied on backup directory

### Task Shows "Status Pending" 

The task may not have run yet if:
- Schedule hasn't been reached
- The time hasn't arrived yet
- Computer was off at scheduled time

Right-click the task → **Run** to test it manually

## Database Backup File Details

- **Filename**: `database_backup.zip`
- **Location**: `storage/app/backups/`
- **Contents**: SQL dump of entire database wrapped in ZIP
- **Size**: Typically 1-10 MB depending on data volume
- **Refresh**: Overwrites on every backup (manual or automatic)

## Restoring from Backup

1. Extract the ZIP file: `database_backup.zip`
2. Get the SQL file inside
3. Import into MySQL:
   ```bash
   mysql -h 127.0.0.1 -u root -p library < backup.sql
   ```

## Support Files

- **Setup Script**: `setup-backup-scheduler.ps1`
- **Batch File**: `backup-task.bat` (created by setup script)
- **Log File**: `storage/logs/backup-task.log`
- **Backup File**: `storage/app/backups/database_backup.zip`

## Important Notes

⚠️ **Single File Policy**: The backup file overwrites. If you need to keep multiple versions:
1. Download the backup before creating a new one
2. Save with a different name (e.g., `backup_2026-04-13.zip`)
3. Store in a safe location

⚠️ **Off-site Backup**: Consider backup redundancy:
- Upload copies to cloud storage
- Keep copies on external drives
- Maintain daily/weekly backups in archive

📋 **Logging**: All backup operations are logged in:
- `storage/logs/backup-task.log` (Task Scheduler logs)
- `Activity Log` in the application (manual backups)
- Laravel logs in `storage/logs/`

## Help & Support

If backups aren't working:
1. Run `php artisan backup:database` manually in terminal
2. Check `storage/logs/backup-task.log` for errors
3. Verify MySQL connection with `php artisan tinker`
4. Ensure Task Scheduler is enabled and task exists

---

## Part 1: Manual Backups (Web UI)

Users can create backups directly from the **Utilities > Database Backups** page:

1. Click **"Create New Backup"** button
2. The system creates a timestamped backup file
3. Backups are listed with size and creation date
4. Users can download or delete backups individually

### Features
- ✅ Runs on demand
- ✅ Includes microsecond timestamps to prevent collisions
- ✅ Can be deleted manually
- ✅ Logged in activity audit

---

## Part 2: Automated Backups (Task Scheduler)

### Prerequisites

Ensure you have:
1. **PHP installed and accessible from command line**
   - Add PHP to your system PATH or use full path
2. **MySQL/MariaDB configured** (credentials in `.env`)

### Step 1: Locate Your Laravel Installation

Your Laravel project is located at:
```
C:\Users\jimmu\Herd\library
```

### Step 2: Test the Artisan Command Manually

Open Command Prompt (or PowerShell as Administrator) and run:

```bash
cd C:\Users\jimmu\Herd\library
php artisan backup:database
```

You should see output like:
```
Starting database backup: backup_2026-04-13_14-30-45_123456
SQL dump created: 45.23 MB
Backup compressed: 3.45 MB
✓ Backup completed successfully!
```

**If this works, proceed to Step 3.**

If you get errors:
- Check that MySQL/MariaDB credentials are correct in `.env`
- Ensure PHP can reach the MySQL server
- Check database permissions

### Step 3: Configure Windows Task Scheduler

#### Option A: Using the GUI

1. **Open Task Scheduler**
   - Press `Win + R`, type `taskschd.msc`, press Enter
   - Or: Control Panel → Administrative Tools → Task Scheduler

2. **Create a New Task**
   - Right-click **Task Scheduler Library** → **Create Basic Task**
   - Name: `Database Backup - Daily`
   - Description: `Automated daily backup of the library database`

3. **Set Schedule**
   - Choose **Trigger** → **Daily**
   - Set time: Choose your preferred time (e.g., 2:00 AM)
   - Click **Next**

4. **Set Action**
   - Choose **Action** → **Start a program**
   - Program/script: `php.exe`
   - Add arguments: `artisan backup:database --retention=30`
   - Start in: `C:\Users\jimmu\Herd\library`
   - Click **Next**

5. **Finish**
   - Review summary
   - ✓ Check "Open the Properties dialog for this task when I click Finish"
   - Click **Finish**

6. **Configure Advanced Settings**
   - Go to **General** tab:
     - ✓ Run whether user is logged in or not
     - ✓ Run with highest privileges
   - Go to **Conditions** tab:
     - ☐ Start the task only if the computer is on AC power (uncheck)
   - Go to **Settings** tab:
     - ✓ Allow task to be run on demand
     - ✓ If the task fails, restart every 1 minute (up to 5 times)
   - Click **OK**

#### Option B: Using Command Line (PowerShell as Admin)

```powershell
# Define the task
$trigger = New-ScheduledTaskTrigger -Daily -At 02:00AM
$action = New-ScheduledTaskAction -Execute "php.exe" -Argument "artisan backup:database --retention=30" -WorkingDirectory "C:\Users\jimmu\Herd\library"
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -RunOnlyIfNetworkAvailable

# Register the task
Register-ScheduledTask -TaskName "Database Backup - Daily" -Trigger $trigger -Action $action -Settings $settings -RunLevel Highest
```

### Step 4: Monitor and Verify

1. **Check Task History**
   - In Task Scheduler, select your task
   - View **History** tab to see past runs
   - Look for "Task completed with an exit code of (0)" for success

2. **Verify Backup Files Created**
   - Navigate to: `C:\Users\jimmu\Herd\library\storage\app\backups`
   - Check that files are being created daily

3. **Check Activity Log**
   - In your app, go to **Utilities → Activity Log**
   - Filter for "Automated Database Backup" entries

### Step 5: Customize Retention Period

The backup command supports a `--retention` flag to keep backups for N days:

```bash
php artisan backup:database --retention=30    # Keep backups for 30 days (default)
php artisan backup:database --retention=60    # Keep backups for 60 days
php artisan backup:database --retention=7     # Keep backups for 1 week only
```

To change Task Scheduler settings:
1. Open Task Scheduler → find your task
2. Double-click to edit
3. Go to **Actions** tab
4. Edit the action
5. Change `--retention=30` to your desired value
6. Click **OK**

---

## Backup File Naming Convention

### Manual & Automated Backups
```
backup_2026-04-13_14-30-45_123456.zip
         └─ YYYY-MM-DD_HH-MM-SS_microseconds
```

The microsecond precision ensures that even if backups run simultaneously, they won't collide.

---

## Troubleshooting

### Task Runs But Doesn't Create Backup

**Check the logs:**
1. Open `storage/logs/laravel.log`
2. Look for error messages
3. Most common issues:
   - MySQL password has special characters (encode in `.env`)
   - Database credentials are incorrect
   - PHP can't reach the MySQL server
   - File permissions issue in `storage/app/backups`

**Test manually:**
```bash
cd C:\Users\jimmu\Herd\library
php artisan backup:database
```

### "Access Denied" When Task Runs

- Ensure the task is configured to **"Run with highest privileges"**
- Check that `storage/app/backups` directory is writable

### Task Won't Run at Scheduled Time

1. In Task Scheduler, right-click the task → **Run** to test it manually
2. Check **History** tab for error codes
3. Common fixes:
   - Computer was asleep/shut down
   - Task was disabled
   - User permissions changed

### Old Backups Not Deleted

- Verify the `--retention` flag is set correctly
- Manually check `storage/app/backups` for old files
- Logs show what was deleted: `Deleted old backup: ...`

---

## Best Practices

✅ **DO:**
- Run backups during off-peak hours (e.g., 2:00 AM)
- Schedule weekly or daily backups depending on data volume
- Keep retention period generous (30-60 days recommended)
- Monitor the activity log periodically
- Test backup restoration occasionally

❌ **DON'T:**
- Run backups during heavy usage hours
- Delete old backups manually (let the retention system handle it)
- Share backup files over unsecured channels
- Store backups only on the server (consider external storage)

---

## Manual Backup Restoration

In case you need to restore a backup:

1. Download the `.zip` file from **Utilities → Database Backups**
2. Extract the `.sql` file
3. Use MySQL Workbench or command line to restore:
   ```bash
   mysql -u username -p databasename < backup_file.sql
   ```

---

## Additional Commands

View all available backup commands:
```bash
php artisan backup:database --help
```

Force cleanup of old backups (without recreating one):
```bash
php artisan backup:database --retention=30    # Just cleans up, then exits
```

---

## Support

For issues or questions:
1. Check `storage/logs/laravel.log` for error details
2. Review Activity Log in the app for backup history
3. Verify Task Scheduler configuration matches this guide
4. Test manually: `php artisan backup:database`
