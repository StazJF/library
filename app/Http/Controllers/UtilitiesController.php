<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
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
        
        // Grouped Books (only show if the book is deleted AND all copies are deleted)
        $booksQuery = Book::onlyTrashed()
            ->whereDoesntHave('copies')
            ->with(['deletedCopies' => function ($q) {
                $q->orderBy('control_number');
            }])
            ->latest('deleted_at');
        if ($search) {
            $booksQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('isbn', 'like', "%{$search}%")
                    ->orWhere('call_number', 'like', "%{$search}%");
            });
        }
        $books = $booksQuery->paginate(10, ['*'], 'book_page');

        // Individual Book Copies (show only if they are NOT part of a fully-deleted book group)
        $bookCopiesQuery = BookCopy::onlyTrashed()
            ->with(['book'])
            ->whereDoesntHave('book', function ($q) {
                $q->onlyTrashed()->whereDoesntHave('copies');
            })
            ->latest('deleted_at');
        if ($search) {
            $bookCopiesQuery->where(function ($q) use ($search) {
                $q->where('control_number', 'like', "%{$search}%")
                    ->orWhereHas('book', function ($b) use ($search) {
                        $b->where('title', 'like', "%{$search}%")
                            ->orWhere('author', 'like', "%{$search}%")
                            ->orWhere('isbn', 'like', "%{$search}%")
                            ->orWhere('call_number', 'like', "%{$search}%");
                    });
            });
        }
        $bookCopies = $bookCopiesQuery->paginate(10, ['*'], 'book_copy_page');
        
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

        return view('utilities.archive', compact('books', 'bookCopies', 'students', 'staff', 'teachers'));
    }

    // Restore single item
    public function restore($model, $id)
    {
        $model = strtolower((string) $model);

        if ($model === 'book') {
            $book = Book::onlyTrashed()
                ->whereDoesntHave('copies')
                ->with('deletedCopies')
                ->find($id);
            if (!$book) {
                return back()->with('error', 'Book not found or not deleted.');
            }

            $details = $this->getItemDetails('book', $book);

            DB::transaction(function () use ($book) {
                $book->restore(); // also restores copies via Book::booted()

                $newCopiesCount = $book->copies()->count();
                $newAvailableCount = $book->copies()->available()->count();
                $book->update([
                    'copies' => $newCopiesCount,
                    'available_copies' => $newAvailableCount,
                    'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
                ]);
            });

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Restored Book',
                'target_type' => 'book',
                'target_id' => $book->id,
                'details' => $details,
            ]);

            return back()->with('success', 'Book restored successfully.');
        }

        if (in_array($model, ['book_copy', 'book-copy', 'copy'], true)) {
            $copy = BookCopy::onlyTrashed()->with('book')->find($id);
            if (!$copy) {
                return back()->with('error', 'Book copy not found or not deleted.');
            }

            $book = $copy->book;
            $ctrl = $copy->control_number ?? '(unassigned)';
            $details = "Book Copy: {$ctrl} | Book: " . ($book?->title ?? 'Unknown');

            DB::transaction(function () use ($copy, $book) {
                if ($book && method_exists($book, 'trashed') && $book->trashed()) {
                    Book::withoutEvents(function () use ($book) {
                        $book->restore();
                    });
                }

                $copy->restore();

                if ($book) {
                    $newCopiesCount = $book->copies()->count();
                    $newAvailableCount = $book->copies()->available()->count();
                    $book->update([
                        'copies' => $newCopiesCount,
                        'available_copies' => $newAvailableCount,
                        'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
                    ]);
                }
            });

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Restored Book Copy',
                'target_type' => 'book_copy',
                'target_id' => $copy->id,
                'details' => $details,
            ]);

            return back()->with('success', 'Book copy restored successfully.');
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
        $model = strtolower((string) $model);

        if ($model === 'book') {
            $books = Book::onlyTrashed()
                ->whereDoesntHave('copies')
                ->latest('deleted_at')
                ->get();
            $restored = 0;

            foreach ($books as $book) {
                $details = $this->getItemDetails('book', $book);

                DB::transaction(function () use ($book) {
                    $book->restore(); // also restores copies via Book::booted()

                    $newCopiesCount = $book->copies()->count();
                    $newAvailableCount = $book->copies()->available()->count();
                    $book->update([
                        'copies' => $newCopiesCount,
                        'available_copies' => $newAvailableCount,
                        'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
                    ]);
                });

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'Restored Book',
                    'target_type' => 'book',
                    'target_id' => $book->id,
                    'details' => $details,
                ]);

                $restored++;
            }

            return back()->with('success', "Restored {$restored} book(s) successfully.");
        }

        if (in_array($model, ['book_copy', 'book-copy', 'copy'], true)) {
            $copies = BookCopy::onlyTrashed()
                ->with('book')
                ->whereDoesntHave('book', function ($q) {
                    $q->onlyTrashed()->whereDoesntHave('copies');
                })
                ->latest('deleted_at')
                ->get();

            $restored = 0;

            foreach ($copies as $copy) {
                $book = $copy->book;
                $ctrl = $copy->control_number ?? '(unassigned)';
                $details = "Book Copy: {$ctrl} | Book: " . ($book?->title ?? 'Unknown');

                DB::transaction(function () use ($copy, $book) {
                    if ($book && method_exists($book, 'trashed') && $book->trashed()) {
                        Book::withoutEvents(function () use ($book) {
                            $book->restore();
                        });
                    }

                    $copy->restore();

                    if ($book) {
                        $newCopiesCount = $book->copies()->count();
                        $newAvailableCount = $book->copies()->available()->count();
                        $book->update([
                            'copies' => $newCopiesCount,
                            'available_copies' => $newAvailableCount,
                            'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
                        ]);
                    }
                });

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'Restored Book Copy',
                    'target_type' => 'book_copy',
                    'target_id' => $copy->id,
                    'details' => $details,
                ]);

                $restored++;
            }

            return back()->with('success', "Restored {$restored} book copy/copies successfully.");
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
        $model = strtolower((string) $model);

        if ($model === 'book') {
            $book = Book::onlyTrashed()
                ->whereDoesntHave('copies')
                ->with('deletedCopies')
                ->find($id);
            if (!$book) {
                return back()->with('error', 'Book not found or not deleted.');
            }

            $details = $this->getItemDetails('book', $book);
            $bookId = $book->id;

            $deleted = DB::transaction(function () use ($book) {
                // Also permanently deletes soft-deleted copies via Book::booted()
                return $book->forceDelete();
            });

            if (!$deleted) {
                return back()->with('error', 'Cannot permanently delete this book because at least one copy is currently borrowed. Please return all borrowed copies first.');
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Permanently Deleted Book',
                'target_type' => 'book',
                'target_id' => $bookId,
                'details' => $details,
            ]);

            return back()->with('success', 'Book deleted permanently.');
        }

        if (in_array($model, ['book_copy', 'book-copy', 'copy'], true)) {
            $copy = BookCopy::onlyTrashed()->with('book')->find($id);
            if (!$copy) {
                return back()->with('error', 'Book copy not found.');
            }

            $book = $copy->book;
            $ctrl = $copy->control_number ?? '(unassigned)';
            $details = "Book Copy: {$ctrl} | Book: " . ($book?->title ?? 'Unknown');
            $copyId = $copy->id;

            DB::transaction(function () use ($copy, $book) {
                $copy->forceDelete();

                if ($book && (!method_exists($book, 'trashed') || !$book->trashed())) {
                    $newCopiesCount = $book->copies()->count();
                    $newAvailableCount = $book->copies()->available()->count();
                    $book->update([
                        'copies' => $newCopiesCount,
                        'available_copies' => $newAvailableCount,
                        'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
                    ]);
                }
            });

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Permanently Deleted Book Copy',
                'target_type' => 'book_copy',
                'target_id' => $copyId,
                'details' => $details,
            ]);

            return back()->with('success', 'Book copy deleted permanently.');
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
        $model = strtolower((string) $model);

        if ($model === 'book') {
            $books = Book::onlyTrashed()
                ->whereDoesntHave('copies')
                ->latest('deleted_at')
                ->get();
            $deleted = 0;
            $skipped = 0;

            foreach ($books as $book) {
                $details = $this->getItemDetails('book', $book);
                $bookId = $book->id;

                $didDelete = DB::transaction(function () use ($book) {
                    // Also permanently deletes soft-deleted copies via Book::booted()
                    return $book->forceDelete();
                });

                if (!$didDelete) {
                    $skipped++;
                    continue;
                }

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'Permanently Deleted Book',
                    'target_type' => 'book',
                    'target_id' => $bookId,
                    'details' => $details,
                ]);

                $deleted++;
            }

            $message = "Deleted {$deleted} book(s) permanently.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} book(s) with active borrows.";
            }

            return back()->with('success', $message);
        }

        if (in_array($model, ['book_copy', 'book-copy', 'copy'], true)) {
            $copies = BookCopy::onlyTrashed()
                ->with('book')
                ->whereDoesntHave('book', function ($q) {
                    $q->onlyTrashed()->whereDoesntHave('copies');
                })
                ->latest('deleted_at')
                ->get();

            $deleted = 0;

            foreach ($copies as $copy) {
                $book = $copy->book;
                $ctrl = $copy->control_number ?? '(unassigned)';
                $details = "Book Copy: {$ctrl} | Book: " . ($book?->title ?? 'Unknown');
                $copyId = $copy->id;

                DB::transaction(function () use ($copy, $book) {
                    $copy->forceDelete();

                    if ($book && (!method_exists($book, 'trashed') || !$book->trashed())) {
                        $newCopiesCount = $book->copies()->count();
                        $newAvailableCount = $book->copies()->available()->count();
                        $book->update([
                            'copies' => $newCopiesCount,
                            'available_copies' => $newAvailableCount,
                            'status' => $newAvailableCount > 0 ? 'available' : 'borrowed',
                        ]);
                    }
                });

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'Permanently Deleted Book Copy',
                    'target_type' => 'book_copy',
                    'target_id' => $copyId,
                    'details' => $details,
                ]);

                $deleted++;
            }

            return back()->with('success', "Deleted {$deleted} book copy/copies permanently.");
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

        // Add password protection if configured
        $backupPassword = config('app.backup_password', env('BACKUP_PASSWORD'));
        if ($backupPassword) {
            if (defined('ZipArchive::EM_AES_256')) {
                $zip->setEncryptionName(basename($sqlPath), ZipArchive::EM_AES_256, $backupPassword);
            }
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

    private function deleteAllBookArchives()
    {
        return back()->with('error', 'Legacy BookArchive deletion is disabled.');
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
            $callNumber = $item->call_number ?? null;
            $ctrlPart = $callNumber ? " (Ctrl: {$callNumber})" : '';
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
