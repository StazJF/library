<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database
                            {--retention=30 : Delete other backup .zip files older than N days (0 disables cleanup)}
                            {--password= : Override backup password (default from BACKUP_PASSWORD env var)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a single MySQL database backup file (password-protected, overwrites existing backup)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port');
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');

            // Validate configuration
            if (!$host || !$database || !$username) {
                $this->error("Database configuration incomplete in .env file");
                $this->error("Ensure DB_HOST, DB_DATABASE, and DB_USERNAME are set");
                Log::error('Backup failed: Database configuration incomplete', [
                    'host' => $host,
                    'database' => $database,
                    'username' => $username
                ]);
                return 1;
            }

            $backupDir = storage_path('app/backups');
            if (!file_exists($backupDir)) {
                @mkdir($backupDir, 0777, true);
                if (!is_dir($backupDir)) {
                    $this->error("Failed to create backup directory: {$backupDir}");
                    Log::error('Failed to create backup directory', ['path' => $backupDir]);
                    return 1;
                }
            }
            
            // Check if directory is writable
            if (!is_writable($backupDir)) {
                $this->error("Backup directory is not writable: {$backupDir}");
                Log::error('Backup directory not writable', ['path' => $backupDir]);
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Configuration error: " . $e->getMessage());
            Log::error('Backup configuration error', ['exception' => $e]);
            return 1;
        }

        // Use single backup filename (overwrites previous backup)
        $filename = "database_backup";
        $sqlPath = storage_path('app/backups/' . $filename . '.sql');
        $zipPath = storage_path('app/backups/' . $filename . '.zip');

        $this->info("Starting database backup...");
        $this->info("Target: {$database}@{$host}:{$port}");
        
        try {
            // Use PHP's database connection to export the database
            $this->exportDatabaseUsingPHP($sqlPath, $host, $port, $database, $username, $password);
        } catch (\Exception $e) {
            $this->error("Backup failed: " . $e->getMessage());
            Log::error('Database backup failed using PHP', [
                'host' => $host,
                'database' => $database,
                'error' => $e->getMessage()
            ]);
            @unlink($sqlPath);
            return 1;
        }

        if (!file_exists($sqlPath)) {
            $this->error("SQL file was not created: {$sqlPath}");
            Log::error('SQL dump file not created', ['path' => $sqlPath]);
            return 1;
        }

        $sqlSize = filesize($sqlPath);
        $this->info("SQL dump created: " . number_format($sqlSize / 1024 / 1024, 2) . " MB");

        // Create zip archive (overwrite if exists)
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            $this->error("Failed to create zip file: {$zipPath}");
            Log::error('Failed to create zip archive', [
                'path' => $zipPath,
                'readonly' => is_dir(dirname($zipPath)) && !is_writable(dirname($zipPath))
            ]);
            @unlink($sqlPath);
            return 1;
        }

        if (!$zip->addFile($sqlPath, basename($sqlPath))) {
            $this->error("Failed to add SQL file to zip");
            Log::error('Failed to add SQL file to zip', [
                'sql_path' => $sqlPath,
                'zip_path' => $zipPath
            ]);
            $zip->close();
            @unlink($sqlPath);
            @unlink($zipPath);
            return 1;
        }

        // Add password protection if configured
        $backupPassword = $this->option('password') ?? config('app.backup_password', env('BACKUP_PASSWORD'));
        if ($backupPassword) {
            if (defined('ZipArchive::EM_AES_256')) {
                $zip->setEncryptionName(basename($sqlPath), ZipArchive::EM_AES_256, $backupPassword);
                $this->info("✓ Password protection enabled (AES-256)");
            } else {
                $this->warn("Password protection requested but not available (requires PHP 7.2+ with libzip support)");
            }
        }

        $zip->close();
        $this->info("Backup compressed: " . number_format(filesize($zipPath) / 1024 / 1024, 2) . " MB");

        // Delete temporary SQL file
        @unlink($sqlPath);

        // Optional: cleanup older backup files created by previous versions/configs.
        $this->cleanupOldBackups($backupDir, $zipPath);

        // Log the backup (using system user ID 0 for automated backups)
        try {
            // Create log without user association to avoid foreign key constraint
            ActivityLog::create([
                'user_id' => null, // System backup - no user
                'action' => 'Automated Database Backup',
                'target_type' => 'database',
                'details' => 'Backup file: ' . $filename . '.zip | Size: ' . number_format(filesize($zipPath) / 1024, 2) . ' KB | Time: ' . date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->warn("Could not log backup: " . $e->getMessage());
        }

        $this->info("✓ Backup completed successfully!");
        Log::info('Database backup completed successfully', ['backup_file' => $filename . '.zip', 'size' => filesize($zipPath)]);
        return 0;
    }

    private function cleanupOldBackups(string $backupDir, string $currentZipPath): void
    {
        $retentionRaw = $this->option('retention');
        $retentionDays = is_numeric($retentionRaw) ? (int) $retentionRaw : 30;

        if ($retentionDays <= 0) {
            return;
        }

        $cutoff = time() - ($retentionDays * 86400);
        $deleted = 0;
        $skipped = 0;

        foreach ((array) glob($backupDir . DIRECTORY_SEPARATOR . '*.zip') as $path) {
            if (!is_string($path) || $path === $currentZipPath) {
                $skipped++;
                continue;
            }

            $mtime = @filemtime($path);
            if ($mtime === false) {
                $skipped++;
                continue;
            }

            if ($mtime < $cutoff) {
                if (@unlink($path)) {
                    $deleted++;
                }
            } else {
                $skipped++;
            }
        }

        if ($deleted > 0) {
            $this->info("Cleanup: deleted {$deleted} old backup(s) (retention={$retentionDays} days)");
            Log::info('Backup cleanup completed', [
                'retention_days' => $retentionDays,
                'deleted' => $deleted,
                'backup_dir' => $backupDir,
            ]);
        }
    }

    /**
     * Export database using PHP's database connection
     */
    private function exportDatabaseUsingPHP($sqlPath, $host, $port, $database, $username, $password)
    {
        $pdo = new \PDO(
            "mysql:host={$host};port={$port};charset=utf8mb4",
            $username,
            $password,
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            ]
        );

        $sql = "-- MySQL Database Dump\r\n";
        $sql .= "-- Generated on " . date('Y-m-d H:i:s') . "\r\n";
        $sql .= "-- Database: `" . $database . "`\r\n";
        $sql .= "-- Host: " . $host . "\r\n";
        $sql .= "\r\n";
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\r\n";
        $sql .= "SET AUTOCOMMIT=0;\r\n";
        $sql .= "SET UNIQUE_CHECKS=0;\r\n\r\n";

        // Switch to target database
        $pdo->exec("USE `{$database}`");

        // Get all tables
        $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$database}'");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $this->info("Exporting table: {$table}");

            // Get CREATE TABLE statement
            $stmt = $pdo->prepare("SHOW CREATE TABLE `{$table}`");
            $stmt->execute();
            $createTable = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($createTable) {
                $sql .= "\r\n-- Table: `{$table}`\r\n";
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\r\n";
                $sql .= $createTable['Create Table'] . ";\r\n";

                // Get table data
                $stmt = $pdo->prepare("SELECT * FROM `{$table}`");
                $stmt->execute();
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

                if (count($rows) > 0) {
                    $sql .= "\r\nINSERT INTO `{$table}` VALUES\r\n";
                    $values = [];
                    foreach ($rows as $row) {
                        $rowValues = [];
                        foreach ($row as $value) {
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = $pdo->quote($value);
                            }
                        }
                        $values[] = '(' . implode(',', $rowValues) . ')';
                    }
                    $sql .= implode(",\r\n", $values) . ";\r\n";
                }
            }
        }

        $sql .= "\r\nSET FOREIGN_KEY_CHECKS=1;\r\nCOMMIT;\r\nSET AUTOCOMMIT=1;\r\n";

        // Write to file
        if (file_put_contents($sqlPath, $sql) === false) {
            throw new \Exception("Failed to write SQL dump to file: {$sqlPath}");
        }
    }
}
