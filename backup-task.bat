@echo off
setlocal EnableExtensions

REM Backup task for library database (Task Scheduler runner)
REM Logs to: storage\logs\backup-task.log

set "PROJECT_DIR=%~dp0"
if "%PROJECT_DIR:~-1%"=="\" set "PROJECT_DIR=%PROJECT_DIR:~0,-1%"

cd /d "%PROJECT_DIR%"

if not exist "storage\logs" mkdir "storage\logs"

echo ========================================>> storage\logs\backup-task.log
echo Backup task started at %date% %time%>> storage\logs\backup-task.log

set "PHP_EXE=C:\xampp\php\php.exe"
if not exist "%PHP_EXE%" (
  echo ERROR: PHP not found at %PHP_EXE%>> storage\logs\backup-task.log
  echo Fix: update PHP_EXE in backup-task.bat>> storage\logs\backup-task.log
  echo ========================================>> storage\logs\backup-task.log
  exit /b 1
)

"%PHP_EXE%" -d display_errors=0 artisan backup:database --retention=30 >> storage\logs\backup-task.log 2>&1
set "EXITCODE=%ERRORLEVEL%"

echo Backup task finished at %date% %time% (Exit Code: %EXITCODE%)>> storage\logs\backup-task.log
echo ========================================>> storage\logs\backup-task.log

exit /b %EXITCODE%

