<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\BookArchive;
use App\Models\BookCopy;
use App\Models\User;
use App\Models\SystemUser;
use App\Models\Teacher;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UtilitiesController extends Controller
{
    // List available backup files
    public function listBackups()
    {
        $backupDir = storage_path('app/backups');
        $backups = [];
        if (file_exists($backupDir)) {
            $files = glob($backupDir . '/*.zip');
            usort($files, function($a, $b) { return filemtime($b) - filemtime($a); }); // newest first
            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                ];
            }
        }
        return view('utilities.backups', compact('backups'));
    }

    public function backupStatus()
    {
        $backupDir = storage_path('app/backups');
        $file = $backupDir . '/database_backup.zip';

        if (!file_exists($file)) {
            return response()
                ->json([
                    'exists' => false,
                ])
                ->header('Cache-Control', 'no-store');
        }

        $mtime = filemtime($file) ?: null;

        return response()
            ->json([
                'exists' => true,
                'name' => basename($file),
                'size' => filesize($file),
                'modified_at_unix' => $mtime,
                'modified_at_iso' => $mtime ? date('c', $mtime) : null,
                'modified_at' => $mtime ? date('Y-m-d H:i:s', $mtime) : null,
            ])
            ->header('Cache-Control', 'no-store');
    }
    

    // Download a specific backup file
    public function downloadBackup($filename)
    {
        $backupDir = storage_path('app/backups');
        $safeName = basename($filename);
        $file = $backupDir . '/' . $safeName;
        if (!file_exists($file)) {
            abort(404, 'Backup file not found.');
        }
        return response()->download($file);
    }
    // Utilities Dashboard
    public function index()
    {
        return view('utilities.index');
    }

    // Logs Page
    public function logs()
    {
        $query = ActivityLog::latest();
        
        // Handle search
        if (request('search')) {
            $search = request('search');
            $query->where('action', 'like', "%{$search}%")
                  ->orWhere('details', 'like', "%{$search}%");
        }
        
        // Handle year filter
        if (request('year')) {
            $year = request('year');
            $query->whereYear('created_at', $year);
        }
        
        // Handle action filter
        if (request('action_filter')) {
            $action = request('action_filter');
            $query->where('action', 'like', "%{$action}%");
        }
        
        $logs = $query->paginate(20);
        return view('utilities.activity-log', compact('logs'));
    }

    // Archive Page
    public function archive()
    {
        $search = request('q');
        
        // Books search and pagination
        $booksQuery = \App\Models\BookArchive::query()->latest();
        if ($search) {
            $booksQuery->where('title', 'like', "%{$search}%")
                      ->orWhere('author', 'like', "%{$search}%")
                      ->orWhere('isbn', 'like', "%{$search}%")
                      ->orWhere('ctrl_number', 'like', "%{$search}%");
        }
        $books = $booksQuery->paginate(10, ['*'], 'book_page');
        
        // Students search and pagination
        $studentsQuery = User::onlyTrashed();
        if ($search) {
            $studentsQuery->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
        }
        $students = $studentsQuery->paginate(10, ['*'], 'student_page');
        
        // Staff search and pagination
        $staffQuery = SystemUser::onlyTrashed();
        if ($search) {
            $staffQuery->where('email', 'like', "%{$search}%")
                      ->orWhere('role', 'like', "%{$search}%");
        }
        $staff = $staffQuery->paginate(10, ['*'], 'staff_page');
        
        // Teachers search and pagination
        $teachersQuery = Teacher::onlyTrashed();
        if ($search) {
            $teachersQuery->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
        }
        $teachers = $teachersQuery->paginate(10, ['*'], 'teacher_page');

        return view('utilities.archive', compact('books', 'students', 'staff', 'teachers'));
    }

    // Restore single item
    public function restore($model, $id)
    {
        if (strtolower($model) === 'book') {
            return $this->restoreBookArchive($id);
        }

        $class = $this->getModel($model);
        $item = $class::onlyTrashed()->find($id);

        if (!$item) {
            return back()->with('error', 'Item not found or not deleted.');
        }

        // Get item details before restoring
        $details = $this->getItemDetails($model, $item);
        
        $item->restore();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Restored ' . ucfirst($model),
            'target_type' => $model,
            'target_id' => $id,
            'details' => $details
        ]);

        return back()->with('success', ucfirst($model) . " restored successfully.");
    }

    // Restore all
    public function restoreAll($model)
    {
        if (strtolower($model) === 'book') {
            return $this->restoreAllBookArchives();
        }

        $class = $this->getModel($model);
        $items = $class::onlyTrashed()->get();

        foreach ($items as $item) {
            $id = $item->id ?? $item->id;
            $details = $this->getItemDetails($model, $item);
            
            $item->restore();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Restored ' . ucfirst($model),
                'target_type' => $model,
                'target_id' => $id,
                'details' => $details
            ]);
        }

        return back()->with('success', "All {$model}s restored successfully.");
    }

    // Delete permanently (single)
    public function delete($model, $id)
    {
        if (strtolower($model) === 'book') {
            return $this->deleteBookArchive($id);
        }

        $class = $this->getModel($model);
        $item = $class::onlyTrashed()->find($id);

        if (!$item) {
            return back()->with('error', 'Item not found.');
        }

        $idValue = $item->id ?? $item->id;
        $details = $this->getItemDetails($model, $item);
        
        $item->forceDelete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Permanently Deleted ' . ucfirst($model),
            'target_type' => $model,
            'target_id' => $idValue,
            'details' => $details
        ]);

        return back()->with('success', ucfirst($model) . " deleted permanently.");
    }

    // Delete all
    public function deleteAll($model)
    {
        if (strtolower($model) === 'book') {
            return $this->deleteAllBookArchives();
        }

        $class = $this->getModel($model);
        $items = $class::onlyTrashed()->get();

        foreach ($items as $item) {
            $id = $item->id ?? $item->id;
            $details = $this->getItemDetails($model, $item);
            
            $item->forceDelete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Permanently Deleted ' . ucfirst($model),
                'target_type' => $model,
                'target_id' => $id,
                'details' => $details
            ]);
        }

        return back()->with('success', "All {$model}s deleted permanently.");
    }

    /**
     * Database Backup Function (MySQL) - Manual backup triggered via UI
     */
    public function backup()
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $backupDir = storage_path('app/backups');
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        // Use single backup filename
        $filename = "database_backup";
        $sqlPath = $backupDir . '/' . $filename . '.sql';
        $zipPath = $backupDir . '/' . $filename . '.zip';

        try {
            // Use PHP's database connection to export
            $this->exportDatabaseUsingPHP($sqlPath, $host, $port, $database, $username, $password);
        } catch (\Exception $e) {
            Log::error('Backup failed', ['error' => $e->getMessage()]);
            return redirect()->route('utilities.backups')->with('error', 'Backup failed: ' . $e->getMessage());
        }

        if (!file_exists($sqlPath)) {
            return redirect()->route('utilities.backups')->with('error', 'Backup failed. SQL file not created.');
        }

        // Create zip archive (overwrite if exists)
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            @unlink($sqlPath);
            return redirect()->route('utilities.backups')->with('error', 'Failed to create backup archive.');
        }

        if (file_exists($sqlPath)) {
            $zip->addFile($sqlPath, basename($sqlPath));
        }
        $zip->close();

        // Clean up temporary SQL file
        @unlink($sqlPath);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Manual Database Backup',
            'target_type' => 'database',
            'details' => 'File: ' . $filename . '.zip | Size: ' . number_format(filesize($zipPath) / 1024, 2) . ' KB | Time: ' . date('Y-m-d H:i:s')
        ]);

        return redirect()->route('utilities.backups')->with('success', 'Backup created successfully.');
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
        $stmt = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$database}' ORDER BY TABLE_NAME");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
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
            throw new \Exception("Failed to write SQL dump to file");
        }
    }

    /**
     * Delete a specific backup file
     */
    public function deleteBackup($filename)
    {
        $backupDir = storage_path('app/backups');
        $safeName = basename($filename);
        $file = $backupDir . '/' . $safeName;

        if (!file_exists($file)) {
            return back()->with('error', 'Backup file not found.');
        }

        if (!str_ends_with($safeName, '.zip')) {
            return back()->with('error', 'Invalid backup file.');
        }

        if (unlink($file)) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Deleted Backup',
                'target_type' => 'database',
                'details' => 'Deleted backup file: ' . $safeName
            ]);
            return back()->with('success', 'Backup deleted successfully.');
        }

        return back()->with('error', 'Failed to delete backup file.');
    }

    // Book archive actions
    private function restoreBookArchive($id)
    {
        $archive = BookArchive::query()->find($id);
        if (!$archive) {
            return back()->with('error', 'Item not found.');
        }

        $controlNumber = trim((string) ($archive->ctrl_number ?? ''));
        if ($controlNumber === '') {
            return back()->with('error', 'Cannot restore: missing control number.');
        }

        if (BookCopy::query()->where('control_number', $controlNumber)->exists()) {
            return back()->with('error', "Cannot restore: control number '{$controlNumber}' already exists.");
        }

        $isbn = trim((string) ($archive->isbn ?? ''));
        if ($isbn === '') {
            return back()->with('error', 'Cannot restore: missing ISBN.');
        }

        $book = Book::withTrashed()->where('isbn', $isbn)->first();
        if (!$book) {
            return back()->with('error', "Cannot restore: no matching book found for ISBN '{$isbn}'.");
        }

        try {
            DB::transaction(function () use ($book, $archive, $controlNumber) {
                if (method_exists($book, 'trashed') && $book->trashed()) {
                    $book->restore();
                }

                $book->copies()->create([
                    'control_number' => $controlNumber,
                    'acquisition_year' => $archive->year,
                    'status' => 'available',
                    'condition' => $archive->condition,
                    'is_lost_damaged' => false,
                ]);

                $newCopiesCount = $book->copies()->count();
                $newAvailableCount = $book->copies()->available()->count();
                $book->update([
                    'copies' => $newCopiesCount,
                    'available_copies' => $newAvailableCount,
                    'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
                ]);

                $details = $this->getItemDetails('book', $archive);
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'Restored Book Copy',
                    'target_type' => 'book',
                    'target_id' => $archive->id,
                    'details' => $details,
                ]);

                $archive->delete();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to restore archived book copy.');
        }

        return back()->with('success', 'Book copy restored successfully.');
    }

    private function deleteBookArchive($id)
    {
        $archive = BookArchive::query()->find($id);
        if (!$archive) {
            return back()->with('error', 'Item not found.');
        }

        $details = $this->getItemDetails('book', $archive);
        $archiveId = $archive->id;
        $archive->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Permanently Deleted Book Archive',
            'target_type' => 'book',
            'target_id' => $archiveId,
            'details' => $details,
        ]);

        return back()->with('success', 'Archived book copy deleted permanently.');
    }

    private function restoreAllBookArchives()
    {
        $archives = BookArchive::query()->latest()->get();
        $restored = 0;
        $failed = 0;

        foreach ($archives as $archive) {
            try {
                $controlNumber = trim((string) ($archive->ctrl_number ?? ''));
                $isbn = trim((string) ($archive->isbn ?? ''));
                if ($controlNumber === '' || $isbn === '') {
                    $failed++;
                    continue;
                }

                if (BookCopy::query()->where('control_number', $controlNumber)->exists()) {
                    $failed++;
                    continue;
                }

                $book = Book::withTrashed()->where('isbn', $isbn)->first();
                if (!$book) {
                    $failed++;
                    continue;
                }

                DB::transaction(function () use ($book, $archive, $controlNumber) {
                    if (method_exists($book, 'trashed') && $book->trashed()) {
                        $book->restore();
                    }

                    $book->copies()->create([
                        'control_number' => $controlNumber,
                        'acquisition_year' => $archive->year,
                        'status' => 'available',
                        'condition' => $archive->condition,
                        'is_lost_damaged' => false,
                    ]);

                    $newCopiesCount = $book->copies()->count();
                    $newAvailableCount = $book->copies()->available()->count();
                    $book->update([
                        'copies' => $newCopiesCount,
                        'available_copies' => $newAvailableCount,
                        'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
                    ]);

                    $details = $this->getItemDetails('book', $archive);
                    ActivityLog::create([
                        'user_id' => Auth::id(),
                        'action' => 'Restored Book Copy',
                        'target_type' => 'book',
                        'target_id' => $archive->id,
                        'details' => $details,
                    ]);

                    $archive->delete();
                });

                $restored++;
            } catch (\Throwable $e) {
                $failed++;
            }
        }

        if ($restored === 0 && $failed > 0) {
            return back()->with('error', "No book copies restored. Failed: {$failed}.");
        }

        if ($failed > 0) {
            return back()->with('success', "Restored {$restored} book copy/copies. Failed: {$failed}.");
        }

        return back()->with('success', "All book copies restored successfully ({$restored}).");
    }

    private function deleteAllBookArchives()
    {
        $archives = BookArchive::query()->latest()->get();

        foreach ($archives as $archive) {
            $details = $this->getItemDetails('book', $archive);
            $archiveId = $archive->id;
            $archive->delete();

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Permanently Deleted Book Archive',
                'target_type' => 'book',
                'target_id' => $archiveId,
                'details' => $details,
            ]);
        }

        return back()->with('success', 'All archived book copies deleted permanently.');
    }

    // Map string → Model
    private function getModel($model)
    {
        $model = strtolower($model);

        if ($model === 'book') return Book::class;
        if ($model === 'student') return User::class;
        if ($model === 'teacher') return Teacher::class;
        if ($model === 'staff' || $model === 'account') return SystemUser::class;

        abort(404, 'Invalid model type.');
    }

    // Get item details for logging
    private function getItemDetails($model, $item)
    {
        $model = strtolower($model);

        if ($model === 'book') {
            $title = $item->title ?? 'N/A';
            $author = $item->author ?? 'N/A';
            $isbn = $item->isbn ?? 'N/A';
            $ctrl = $item->ctrl_number ?? null;
            $ctrlPart = $ctrl ? " (Ctrl: {$ctrl})" : '';
            return "Book: '{$title}' by {$author} (ISBN: {$isbn}){$ctrlPart}";
        } elseif ($model === 'student') {
            return "Student: {$item->first_name} {$item->last_name} (Email: {$item->email})";
        } elseif ($model === 'teacher') {
            return "Teacher: {$item->name} (Email: {$item->email})";
        } elseif ($model === 'staff' || $model === 'account') {
            return "Staff/Admin: {$item->email} (Role: {$item->role})";
        }

        return "Unknown item";
    }
}
