# Database Backup Script for Task Scheduler
# This script runs the database backup and logs output to a file
# Usage: powershell.exe -ExecutionPolicy Bypass -File "<project>\backup-script.ps1"

# Configuration
$ProjectPath = Split-Path -Parent $PSCommandPath
$LogFile = "$ProjectPath\storage\logs\backup-scheduler.log"
$PHPPath = $null

# Detect PHP (prefer Herd-managed PHP)
$phpCandidates = @(
    "$env:USERPROFILE\.config\herd\bin\php85\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php84\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php83\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php82\php.exe",
    "$env:USERPROFILE\.config\herd\bin\php81\php.exe",
    "C:\xampp\php\php.exe"
)

foreach ($candidate in $phpCandidates) {
    if (Test-Path $candidate) {
        $PHPPath = $candidate
        break
    }
}

if (-not $PHPPath) {
    $phpFromPath = (Get-Command php -ErrorAction SilentlyContinue)?.Source
    if ($phpFromPath -and (Test-Path $phpFromPath)) {
        $PHPPath = $phpFromPath
    }
}

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

function Get-DotEnvValue {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Key
    )

    if (-not (Test-Path $Path)) {
        return $null
    }

    $line = Get-Content -Path $Path -ErrorAction SilentlyContinue | Where-Object { $_ -match ("^" + [Regex]::Escape($Key) + "=") } | Select-Object -First 1
    if (-not $line) {
        return $null
    }

    $value = $line.Substring($Key.Length + 1)
    if ($value.StartsWith('"') -and $value.EndsWith('"') -and $value.Length -ge 2) {
        $value = $value.Substring(1, $value.Length - 2)
    }
    if ($value.StartsWith("'") -and $value.EndsWith("'") -and $value.Length -ge 2) {
        $value = $value.Substring(1, $value.Length - 2)
    }
    if ($value -eq "") {
        return $null
    }
    return $value
}

Write-Log "=========================================="
Write-Log "Database Backup - Task Scheduler Started"
Write-Log "=========================================="

# Check if PHP exists
if (-not (Test-Path $PHPPath)) {
    Write-Log "ERROR: PHP not found at: $PHPPath"
    Write-Log "Fix: Install Herd (recommended) or add PHP to PATH, then rerun."
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

    # Copy the resulting ZIP to a secured folder (overwrites each run)
    $zipSource = "$ProjectPath\\storage\\app\\backups\\database_backup.zip"
    if (Test-Path $zipSource) {
        $dotEnvSecureDir = Get-DotEnvValue -Path (Join-Path $ProjectPath ".env") -Key "BACKUP_SECURE_EXPORT_DIR"
        $secureDir = if ($env:BACKUP_SECURE_EXPORT_DIR) { $env:BACKUP_SECURE_EXPORT_DIR } elseif ($dotEnvSecureDir) { $dotEnvSecureDir } else { "$env:ProgramData\\LibraryBackups" }

        try {
            if (-not (Test-Path $secureDir)) {
                New-Item -ItemType Directory -Path $secureDir -Force | Out-Null
            }

            # Best-effort: lock down permissions (may require admin)
            try {
                & icacls $secureDir /inheritance:r | Out-Null
                & icacls $secureDir /grant:r "$env:USERNAME:(OI)(CI)F" "Administrators:(OI)(CI)F" "SYSTEM:(OI)(CI)F" | Out-Null
            } catch {
                Write-Log "WARN: Could not tighten ACLs on secure folder: $secureDir"
            }

            $zipDest = Join-Path $secureDir "database_backup.zip"
            Copy-Item -Path $zipSource -Destination $zipDest -Force
            Write-Log "✓ Secure copy updated: $zipDest"
        } catch {
            Write-Log "WARN: Secure copy failed: $($_.Exception.Message)"
        }
    } else {
        Write-Log "WARN: Backup zip not found to secure-copy: $zipSource"
    }
} else {
    Write-Log "✗ Backup failed with exit code: $ExitCode"
    Write-Log "Check storage/logs/laravel.log for details"
}

Write-Log "=========================================="
Write-Log "Database Backup - Task Scheduler Ended"
Write-Log "=========================================="
Write-Log ""

exit $ExitCode
