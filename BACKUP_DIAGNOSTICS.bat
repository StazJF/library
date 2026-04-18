@echo off
REM Backup System Diagnostic Script
REM Run this as Administrator to diagnose backup issues

echo ========================================
echo Database Backup Diagnostics
echo ========================================
echo.

REM Check if PHP is available
echo [1] Checking if PHP is available...
php -v
if errorlevel 1 (
    echo ERROR: PHP not found in PATH
    echo.
    echo SOLUTION: Add PHP to your system PATH or use full path in Task Scheduler
    echo.
) else (
    echo SUCCESS: PHP found
    echo.
)

REM Check Laravel project directory
echo [2] Checking Laravel project...
cd /d C:\Users\jimmu\Herd\library
if exist "artisan" (
    echo SUCCESS: artisan file found
) else (
    echo ERROR: artisan file not found
    echo Current directory: %CD%
)
echo.

REM Check if storage/app/backups directory exists
echo [3] Checking backup directory...
if exist "storage\app\backups" (
    echo SUCCESS: storage\app\backups directory exists
    echo Files in backup directory:
    dir storage\app\backups\
) else (
    echo ERROR: storage\app\backups directory does NOT exist
    echo SOLUTION: Creating directory...
    mkdir storage\app\backups
    echo Directory created.
)
echo.

REM Test the backup command
echo [4] Testing backup command...
echo Running: php artisan backup:database
echo.
php artisan backup:database
if errorlevel 1 (
    echo ERROR: Backup command failed with error code %ERRORLEVEL%
) else (
    echo SUCCESS: Backup command completed
)
echo.

REM Show current backups
echo [5] Current backup files:
if exist "storage\app\backups" (
    dir /B "storage\app\backups"
) else (
    echo Backup directory still doesn't exist
)
echo.

REM Check Laravel logs
echo [6] Checking Laravel logs (last 20 lines)...
if exist "storage\logs\laravel.log" (
    echo.
    echo [Latest Laravel log entries]
    powershell -NoProfile -Command "Get-Content storage\logs\laravel.log -Tail 20"
) else (
    echo No laravel.log file found
)
echo.

echo ========================================
echo Diagnostics Complete
echo ========================================
pause
