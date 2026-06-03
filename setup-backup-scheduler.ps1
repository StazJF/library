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
$projectPath = Split-Path -Parent $PSCommandPath
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

# Detect PHP (prefer Herd-managed PHP)
$phpCandidates = @(
    "$env:USERPROFILE\.config\herd\bin\php85\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php84\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php83\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php82\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php81\php.exe",
    "C:\xampp\php\php.exe"
)

$phpPath = $null
foreach ($candidate in $phpCandidates) {
    if (Test-Path $candidate) {
        $phpPath = $candidate
        break
    }
}

if (-not $phpPath) {
    $phpFromPath = (Get-Command php -ErrorAction SilentlyContinue)?.Source
    if ($phpFromPath -and (Test-Path $phpFromPath)) {
        $phpPath = $phpFromPath
    }
}

if (-not $phpPath) {
    Write-Host "ERROR: Could not find php.exe" -ForegroundColor Red
    Write-Host "Fix: Install Herd (recommended) or add PHP to your PATH." -ForegroundColor Yellow
    Write-Host "Expected Herd path example: $env:USERPROFILE\.config\herd\bin\php84\php.exe" -ForegroundColor Yellow
    exit 1
}

# Create the backup command
$backupBatch = "$projectPath\backup-task.bat"

# Create batch file that will be executed by Task Scheduler
$batchContent = @"
@echo off
setlocal EnableExtensions

REM Backup task for library database (Task Scheduler runner)
REM Logs to: storage\logs\backup-task.log

set "PROJECT_DIR=%~dp0"
cd /d "%PROJECT_DIR%"

if not exist "storage\logs" mkdir "storage\logs"

echo ========================================>> storage\logs\backup-task.log
echo Backup task started at %date% %time%>> storage\logs\backup-task.log

set "PHP_EXE="

REM Prefer Herd-managed PHP (highest version first)
if not defined PHP_EXE (
  if exist "%USERPROFILE%\.config\herd\bin\php85\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php85\php.exe"
)
if not defined PHP_EXE (
  if exist "%USERPROFILE%\.config\herd\bin\php84\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php84\php.exe"
)
if not defined PHP_EXE (
  if exist "%USERPROFILE%\.config\herd\bin\php83\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php83\php.exe"
)
if not defined PHP_EXE (
  if exist "%USERPROFILE%\.config\herd\bin\php82\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php82\php.exe"
)
if not defined PHP_EXE (
  if exist "%USERPROFILE%\.config\herd\bin\php81\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php81\php.exe"
)

REM Fall back to PATH lookup (works if PHP is on PATH for the task user)
for /f "delims=" %%I in ('where.exe php 2^>nul') do if not defined PHP_EXE set "PHP_EXE=%%I"

REM Fall back to common XAMPP location
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"

if not defined PHP_EXE (
  echo ERROR: Could not find php.exe>> storage\logs\backup-task.log
  echo Fix: install PHP, add to PATH, or install Herd and ensure Herd PHP exists under %%USERPROFILE%%\.config\herd\bin\php84\php.exe.>> storage\logs\backup-task.log
  echo ========================================>> storage\logs\backup-task.log
  exit /b 1
)

echo Using PHP: %PHP_EXE%>> storage\logs\backup-task.log

"%PHP_EXE%" -d display_errors=0 artisan backup:database --retention=30 >> storage\logs\backup-task.log 2>&1
set "EXITCODE=%ERRORLEVEL%"

REM Secure export copy (overwrites each run)
set "ZIP_SRC=%PROJECT_DIR%storage\app\backups\database_backup.zip"
set "SECURE_DIR=%ProgramData%\LibraryBackups"
if not "%BACKUP_SECURE_EXPORT_DIR%"=="" set "SECURE_DIR=%BACKUP_SECURE_EXPORT_DIR%"
if exist "%PROJECT_DIR%.env" (
  for /f "usebackq tokens=1,* delims==" %%A in (`findstr /b /c:"BACKUP_SECURE_EXPORT_DIR=" "%PROJECT_DIR%.env" 2^>nul`) do (
    if not "%%B"=="" set "SECURE_DIR=%%B"
  )
)

if "%EXITCODE%"=="0" (
  if exist "%ZIP_SRC%" (
    if not exist "%SECURE_DIR%" mkdir "%SECURE_DIR%" 2>nul
    REM Best-effort: lock down folder permissions (may require admin)
    icacls "%SECURE_DIR%" /inheritance:r >nul 2>&1
    icacls "%SECURE_DIR%" /grant:r "%USERNAME%:(OI)(CI)F" "Administrators:(OI)(CI)F" "SYSTEM:(OI)(CI)F" >nul 2>&1
    copy /Y "%ZIP_SRC%" "%SECURE_DIR%\database_backup.zip" >nul 2>&1
    if "%ERRORLEVEL%"=="0" (
      echo Secure copy updated: %SECURE_DIR%\database_backup.zip>> storage\logs\backup-task.log
    ) else (
      echo WARN: Secure copy failed (check permissions): %SECURE_DIR%>> storage\logs\backup-task.log
    )
  ) else (
    echo WARN: Backup zip not found to secure-copy: %ZIP_SRC%>> storage\logs\backup-task.log
  )
)

echo Backup task finished at %date% %time% (Exit Code: %EXITCODE%)>> storage\logs\backup-task.log
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
    $userId = if ($env:USERDOMAIN) { "$env:USERDOMAIN\$env:USERNAME" } else { $env:USERNAME }
    $principal = New-ScheduledTaskPrincipal -UserID $userId -LogonType InteractiveToken -RunLevel Highest
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
