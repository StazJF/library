# Windows Task Scheduler Setup for Database Backups
# This script creates an automated task that runs the backup command daily

# Check if running as Administrator
$currentUser = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = New-Object Security.Principal.WindowsPrincipal($currentUser)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Please right-click PowerShell and select 'Run as Administrator'" -ForegroundColor Yellow
    exit 1
}

# Configuration
$projectPath = "C:\Users\jimmu\Herd\library"
$taskName = "LibraryDatabaseBackup"
$description = "Daily automated backup of the library database"

# Schedule options: Hourly, Daily, Weekly, Monthly
$scheduleFrequency = "Daily"  # Change this to "Hourly" for hourly backups
$scheduleTime = "02:00 AM"    # Time for daily backups (2 AM)
$hourlyInterval = 1           # For hourly backups, how many hours between runs

Write-Host "`n=== Database Backup Scheduler Setup ===" -ForegroundColor Green
Write-Host "Project Path: $projectPath" -ForegroundColor Cyan
Write-Host "Task Name: $taskName" -ForegroundColor Cyan
Write-Host "Frequency: $scheduleFrequency at $scheduleTime" -ForegroundColor Cyan

# Verify project path exists
if (-not (Test-Path $projectPath)) {
    Write-Host "ERROR: Project path not found: $projectPath" -ForegroundColor Red
    exit 1
}

# Check if php exists
$phpPath = "C:\xampp\php\php.exe"
if (-not (Test-Path $phpPath)) {
    Write-Host "ERROR: PHP not found at: $phpPath" -ForegroundColor Red
    Write-Host "Make sure XAMPP is installed at C:\xampp" -ForegroundColor Yellow
    exit 1
}

# Create the backup command
$backupBatch = "$projectPath\backup-task.bat"

# Create batch file that will be executed by Task Scheduler
$batchContent = @"
@echo off
REM Backup task for library database
REM This file is executed by Windows Task Scheduler

cd /d "$projectPath"
if not exist "storage\logs" mkdir "storage\logs"
echo ========================================>> storage\logs\backup-task.log
echo Backup task started at %date% %time%>> storage\logs\backup-task.log
"$phpPath" -d display_errors=0 artisan backup:database --retention=30 >> storage\logs\backup-task.log 2>&1
set EXITCODE=%ERRORLEVEL%
echo Backup task finished at %date% %time% (Exit Code: %EXITCODE%)>> storage\logs\backup-task.log

REM Log completion
echo ========================================>> storage\logs\backup-task.log
exit /b %EXITCODE%
"@

# Write batch file
Write-Host "`nCreating batch file..." -ForegroundColor Yellow
try {
    Set-Content -Path $backupBatch -Value $batchContent -Encoding ASCII
    Write-Host "✓ Batch file created: $backupBatch" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Failed to create batch file: $_" -ForegroundColor Red
    exit 1
}

# Create Task Scheduler job
Write-Host "`nSetting up Windows Task Scheduler task..." -ForegroundColor Yellow

try {
    # Remove existing task if it exists
    $existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Write-Host "Found existing task, removing it..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
        Start-Sleep -Seconds 1
    }

    # Create trigger based on frequency
    if ($scheduleFrequency -eq "Daily") {
        # Parse time (e.g., "02:00 AM")
        $timeObj = [DateTime]::ParseExact($scheduleTime, "hh:mm tt", $null)
        $trigger = New-ScheduledTaskTrigger -Daily -At $timeObj
        Write-Host "✓ Created daily trigger at $scheduleTime" -ForegroundColor Green
    }
    elseif ($scheduleFrequency -eq "Hourly") {
        $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours $hourlyInterval) -RepetitionDuration (New-TimeSpan -Days 36500)
        Write-Host "✓ Created hourly trigger (every $hourlyInterval hour(s))" -ForegroundColor Green
    }
    else {
        $trigger = New-ScheduledTaskTrigger -Daily -At (Get-Date -Hour 2 -Minute 0)
        Write-Host "✓ Created default daily trigger at 2:00 AM" -ForegroundColor Green
    }

    # Create action
    # IMPORTANT: Task Scheduler uses CreateProcess and cannot execute .bat directly.
    # Use cmd.exe /c to run the batch file reliably.
    $action = New-ScheduledTaskAction -Execute "cmd.exe" -Argument "/c `"$backupBatch`"" -WorkingDirectory $projectPath

    # Create settings
    # Keep conditions permissive so the task actually runs at the scheduled time.
    $settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

    # Register task
    $principal = New-ScheduledTaskPrincipal -UserID "NT AUTHORITY\SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    Register-ScheduledTask -TaskName $taskName `
        -Trigger $trigger `
        -Action $action `
        -Principal $principal `
        -Settings $settings `
        -Description $description `
        | Out-Null

    Write-Host "✓ Task registered successfully!" -ForegroundColor Green
} catch {
    Write-Host "ERROR: Failed to register task: $_" -ForegroundColor Red
    exit 1
}

# Verify task was created
$task = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($task) {
    Write-Host "`n=== Task Successfully Created ===" -ForegroundColor Green
    Write-Host "Task Name: $($task.TaskName)" -ForegroundColor Cyan
    Write-Host "State: $($task.State)" -ForegroundColor Cyan
    Write-Host "Enabled: $($task.Triggers[0].Enabled)" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Next Run: (will be calculated by Task Scheduler)" -ForegroundColor Yellow
} else {
    Write-Host "ERROR: Task verification failed" -ForegroundColor Red
    exit 1
}

Write-Host "`n=== Setup Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Your database will be automatically backed up:" -ForegroundColor Cyan
Write-Host "  Schedule: $scheduleFrequency" -ForegroundColor White
if ($scheduleFrequency -eq "Daily") {
    Write-Host "  Time: $scheduleTime" -ForegroundColor White
}
Write-Host "  Backup File: $projectPath\storage\app\backups\database_backup.zip" -ForegroundColor White
Write-Host "  Log File: $projectPath\storage\logs\backup-task.log" -ForegroundColor White
Write-Host ""
Write-Host "To verify backups are running:" -ForegroundColor Yellow
Write-Host "  1. Check the log file: storage/logs/backup-task.log" -ForegroundColor White
Write-Host "  2. Check backup file modification time in Storage Explorer" -ForegroundColor White
Write-Host "  3. Open Task Scheduler and search for '$taskName'" -ForegroundColor White
Write-Host ""
Write-Host "To change the schedule, edit this file and run it again." -ForegroundColor Yellow

# Optional: Run a test backup
Write-Host ""
$testBackup = Read-Host "Run a test backup now? (y/n)"
if ($testBackup -eq "y" -or $testBackup -eq "Y") {
    Write-Host "`nRunning test backup..." -ForegroundColor Yellow
    & $backupBatch
    Write-Host "`nTest backup completed! Check storage/logs/backup-task.log for details." -ForegroundColor Green
}

Write-Host ""
