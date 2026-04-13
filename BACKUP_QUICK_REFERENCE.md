# Backup System - Quick Reference

## Current Status
✅ **Fixed and Working**

Your backup system now:
- ✅ Backs up to a single file: `storage/app/backups/database_backup.zip`
- ✅ Overwrites old backups with new data
- ✅ Works without mysqldump (uses PHP-based export)
- ✅ Can be scheduled to run automatically

---

## To Create a Manual Backup

**Option 1: Via Web UI**
1. Go to **Utilities → Database Backups**
2. Click **Create New Backup**
3. Wait 3-5 seconds for completion
4. Download if needed

**Option 2: Via Command Line**
```powershell
php artisan backup:database
```

---

## To Set Up Automatic Backups

**Step 1: Run the Setup Script**
```powershell
# Open PowerShell as Administrator (right-click Run as Administrator)
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process -Force
C:\Users\jimmu\Herd\library\setup-backup-scheduler.ps1
```

**Step 2: Choose Your Schedule**
- Daily (recommend 2:00 AM)
- Hourly (recommend every 6 or 12 hours)

**Step 3: Test**
- Script will ask: "Run a test backup now? (y/n)"
- Type `y` and press Enter
- Watch for "✓ Backup completed successfully!"

---

## To Verify Backups Are Running

**Check 1: Look at backup file** (should be updated regularly)
```powershell
dir "C:\Users\jimmu\Herd\library\storage\app\backups\database_backup.zip"
```

**Check 2: Look at logs**
```powershell
Get-Content "C:\Users\jimmu\Herd\library\storage\logs\backup-task.log" -Tail 5
```

**Check 3: Open Task Scheduler**
- Press `Windows Key + R`
- Type: `taskschd.msc`
- Search for "LibraryDatabaseBackup"
- Check "History" tab for recent runs

---

## Backup File Location
```
C:\Users\jimmu\Herd\library\storage\app\backups\database_backup.zip
```

Size: ~10 KB (compressed)
Updates: Automatically on each backup

---

## Important Notes

⚠️ **The backup file OVERWRITES** - if you need to keep multiple backups:
1. Create a backup
2. Download it
3. Rename it: `backup_2026-04-13.zip`
4. Save in a safe place

---

## If Something Goes Wrong

**Backup won't run?**
1. Check Task Scheduler is enabled (right-click task → Enable)
2. Check permissions: `storage/app/backups/` must be writable
3. Run `php artisan backup:database` manually to test
4. Check `storage/logs/backup-task.log` for error messages

**Database won't restore?**
1. Extract `database_backup.zip`
2. Get `database_backup.sql` file inside
3. Run: `mysql -h 127.0.0.1 -u root -pADMIN123 library < database_backup.sql`

---

## Files Related to Backup

- `setup-backup-scheduler.ps1` - Setup script (run as Administrator)
- `backup-task.bat` - Created by setup script, runs the backup
- `storage/app/backups/database_backup.zip` - Your backup file
- `storage/logs/backup-task.log` - Backup logs
- `BACKUP_SETUP_GUIDE.md` - Detailed setup guide
