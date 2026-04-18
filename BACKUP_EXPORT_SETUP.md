# Automatic Backup Export Setup Guide

## Overview
This guide explains how to set up automatic backup export to an external location (like a folder on your laptop or external drive) outside the system.

## What Was Added

1. **New Backup Config File** (`config/backup.php`)
   - Stores backup export configuration
   - References environment variables

2. **Environment Variables** (in `.env`)
   - `BACKUP_EXPORT_PATH`: Path where backups should be automatically exported
   - `BACKUP_RETENTION_DAYS`: Number of days to retain old backups

3. **Enhanced BackupDatabase Command** (`app/Console/Commands/BackupDatabase.php`)
   - Automatically uses the export path from config if configured
   - Exports backup with fixed filename (overwrites previous backup)
   - Better logging to distinguish automatic vs manual exports

## Configuration Steps

### Step 1: Set the Backup Export Path

Edit your `.env` file and set the `BACKUP_EXPORT_PATH` to your desired external location:

```env
BACKUP_EXPORT_PATH=D:\Backups\LibraryDB
```

**Examples:**
- Windows Desktop: `C:\Users\YourUsername\Desktop\LibraryBackups`
- Windows Documents: `C:\Users\YourUsername\Documents\Backups`
- External Drive: `E:\DatabaseBackups`
- Network Drive: `\\192.168.1.100\backups` (if using network shares)

### Step 2: Create the Backup Directory

The directory will be automatically created if it doesn't exist, but ensure the path is valid and you have write permissions.

### Step 3: Test Manual Backup

1. Open the application in your browser
2. Go to **Utilities > Database Backups**
3. Click **Create New Backup** button
4. Check your browser console/terminal output and the configured backup path
5. You should see a message: `Exported backup (manual): D:\Backups\LibraryDB\database_backup.zip`

## Automated Backups via Task Scheduler

The automated backup (via Windows Task Scheduler) will also automatically export to your configured path.

### Current Task Scheduler Setup

Your Windows Task Scheduler task runs the backup command. Verify it doesn't have the `--export` option already set - if it does, remove it to use the automatic export instead.

**To check/modify the scheduled task:**

1. Open **Task Scheduler**
2. Find your backup task
3. Edit the task and check the **Action** tab
4. The command should look like:
   ```
   php artisan backup:database
   ```
   
   Or if you want to use a custom export path just for that task:
   ```
   php artisan backup:database --export="D:\Backups\LibraryDB"
   ```

## File Naming

- **Local Backup** (in project): `storage/app/backups/database_backup.zip` - gets overwritten each backup
- **External Backup** (auto-exported): `D:\Backups\LibraryDB\database_backup.zip` - gets overwritten each backup

Both use the same fixed filename, so they overwrite the previous backup each time.

## Manual Override

You can still manually specify an export path when running the backup command:

```bash
php artisan backup:database --export="D:\Backups\LibraryDB"
```

This will take precedence over the config setting for that specific run.

## Monitoring Backups

1. **In Application**: Go to **Utilities > Database Backups**
   - Shows the backup stored in `storage/app/backups/`
   - Has a download button to download locally

2. **In File System**: 
   - Local: `storage/app/backups/database_backup.zip`
   - External: `D:\Backups\LibraryDB\database_backup.zip` (or your configured path)

3. **In Logs**: Check `storage/logs/laravel.log` for export status

## Troubleshooting

### Backup not exporting
- Check `BACKUP_EXPORT_PATH` is set in `.env`
- Verify the directory path is valid and you have write permissions
- Check logs: `storage/logs/laravel.log`
- Verify the directory exists (it will be created automatically, but verify permissions)

### "Export skipped: directory not writable"
- The directory exists but you don't have write permissions
- Run the application as administrator or change directory permissions

### "Invalid export path"
- The path specified is empty or invalid
- Verify `BACKUP_EXPORT_PATH` in `.env` has a valid path

## Best Practices

1. **Use an External Location**
   - Don't export to a subdirectory within the project
   - Use an external drive, cloud-synced folder, or network location
   - This ensures you have backups even if the entire project folder is lost

2. **Automated Backups**
   - Set up Windows Task Scheduler to run `php artisan backup:database` on a schedule
   - The backup will automatically export to your configured path
   - Check `storage/logs/laravel.log` to verify the scheduled task ran successfully

3. **Regular Testing**
   - Periodically test restoring from the exported backup
   - Ensure the backup file is not corrupted
   - Verify you can access the external backup location

4. **Backup Retention**
   - Set `BACKUP_RETENTION_DAYS` to automatically clean up old backup files
   - Default is 30 days - adjust as needed
   - Set to 0 to disable automatic cleanup

## Security Considerations

- Store backups in a secure location with restricted access
- Don't store backups in publicly accessible directories
- If using cloud storage, ensure encryption is enabled
- Regularly verify backup integrity and test restores
- Consider keeping off-site backups for disaster recovery
