@echo off
REM Backup System Diagnostic Script
REM Run this as Administrator to diagnose backup issues

echo ========================================
echo Database Backup Diagnostics
echo ========================================
echo.

REM Check if PHP is available
echo [1] Checking if PHP is available...
set "PHP_EXE="
if not defined PHP_EXE if exist "%USERPROFILE%\.config\herd\bin\php85\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php85\php.exe"
if not defined PHP_EXE if exist "%USERPROFILE%\.config\herd\bin\php84\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php84\php.exe"
if not defined PHP_EXE if exist "%USERPROFILE%\.config\herd\bin\php83\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php83\php.exe"
if not defined PHP_EXE if exist "%USERPROFILE%\.config\herd\bin\php82\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php82\php.exe"
if not defined PHP_EXE if exist "%USERPROFILE%\.config\herd\bin\php81\php.exe" set "PHP_EXE=%USERPROFILE%\.config\herd\bin\php81\php.exe"
if not defined PHP_EXE (
    for /f "delims=" %%I in ('where.exe php 2^>nul') do (
        set "PHP_EXE=%%I"
        goto :php_found
    )
)
:php_found
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"

if not defined PHP_EXE (
    echo ERROR: Could not find php.exe
    echo.
    echo SOLUTION: Install Herd recommended, or add PHP to your PATH, then rerun this diagnostic.
    echo.
    goto :after_php_check
)

"%PHP_EXE%" -v
if errorlevel 1 (
    echo ERROR: PHP found but failed to run: %PHP_EXE%
    echo.
    goto :after_php_check
)

echo SUCCESS: PHP found at %PHP_EXE%
echo.
:after_php_check

REM Check Laravel project directory
echo [2] Checking Laravel project...
cd /d "%~dp0"
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
if not defined PHP_EXE (
    echo SKIP: PHP not available, cannot run artisan command.
    echo.
    goto :after_backup_test
)

echo Running: "%PHP_EXE%" artisan backup:database
echo.
"%PHP_EXE%" artisan backup:database
if errorlevel 1 (
    echo ERROR: Backup command failed with error code %ERRORLEVEL%
) else (
    echo SUCCESS: Backup command completed
)
echo.
:after_backup_test

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
