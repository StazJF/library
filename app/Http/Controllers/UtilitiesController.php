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
     * Database Backup Function (MySQL)
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

        $filename = "mysql_backup_" . date('Y-m-d_H-i-s');
        $sqlPath = $backupDir . '/' . $filename . '.sql';

        $passwordPart = $password !== null && $password !== '' ? "--password={$password}" : '';
        $command = "mysqldump --host={$host} --port={$port} --user={$username} {$passwordPart} {$database} > \"{$sqlPath}\"";

        exec($command);

        $zipPath = $backupDir . '/' . $filename . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            if (file_exists($sqlPath)) {
                $zip->addFile($sqlPath, basename($sqlPath));
            }
            $zip->close();
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Database Backup',
            'target_type' => 'database',
            'details' => 'Created MySQL backup: ' . $filename
        ]);

        // Do not download, just redirect back with success message
        return redirect()->route('utilities.backups')->with('success', 'Backup created successfully.');
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
