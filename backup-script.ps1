# Database Backup Script for Task Scheduler
# This script runs the database backup and logs output to a file
# Usage: powershell.exe -ExecutionPolicy Bypass -File "C:\Users\jimmu\Herd\library\backup-script.ps1"

# Configuration
$ProjectPath = "C:\Users\jimmu\Herd\library"
$LogFile = "$ProjectPath\storage\logs\backup-scheduler.log"
$PHPPath = "C:\xampp\php\php.exe"  # CHANGE THIS to match your PHP installation

# Ensure log directory exists
$LogDir = Split-Path $LogFile
if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir -Force | Out-Null
}

# Function to log messages
function Write-Log {
    param([string]$Message)
    $Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $LogMessage = "[$Timestamp] $Message"
    Add-Content -Path $LogFile -Value $LogMessage
    Write-Host $LogMessage
}

Write-Log "=========================================="
Write-Log "Database Backup - Task Scheduler Started"
Write-Log "=========================================="

# Check if PHP exists
if (-not (Test-Path $PHPPath)) {
    Write-Log "ERROR: PHP not found at: $PHPPath"
    Write-Log "Please update `$PHPPath in this script to match your PHP installation"
    Write-Log "Run: where.exe php (in PowerShell) to find PHP location"
    exit 1
}

Write-Log "PHP found: $PHPPath"

# Change to project directory
if (-not (Test-Path $ProjectPath)) {
    Write-Log "ERROR: Project path not found: $ProjectPath"
    exit 1
}

Set-Location $ProjectPath
Write-Log "Working directory: $ProjectPath"

# Check if backup directory exists, create if needed
$BackupDir = "$ProjectPath\storage\app\backups"
if (-not (Test-Path $BackupDir)) {
    Write-Log "Creating backup directory: $BackupDir"
    New-Item -ItemType Directory -Path $BackupDir -Force | Out-Null
}

# Run the backup command
Write-Log "Running backup command..."
Write-Log "Command: $PHPPath artisan backup:database --retention=30"
Write-Log ""

& $PHPPath artisan backup:database --retention=30 2>&1 | ForEach-Object {
    Add-Content -Path $LogFile -Value $_
}

$ExitCode = $LASTEXITCODE

Write-Log ""
if ($ExitCode -eq 0) {
    Write-Log "✓ Backup completed successfully (Exit Code: 0)"
    Write-Log "Check Utilities → Database Backups to view the backup"
} else {
    Write-Log "✗ Backup failed with exit code: $ExitCode"
    Write-Log "Check storage/logs/laravel.log for details"
}

Write-Log "=========================================="
Write-Log "Database Backup - Task Scheduler Ended"
Write-Log "=========================================="
Write-Log ""

exit $ExitCode
