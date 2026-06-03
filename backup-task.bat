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
