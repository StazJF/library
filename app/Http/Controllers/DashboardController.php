<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\User;
use App\Models\Borrow;
use App\Models\LostDamagedItem;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Total counts
        $totalBookTitles = Book::count();
        $totalBooks = Book::sum('copies');
        $totalUsers = User::count();
        $totalBorrows = Borrow::count();
        // Borrows with due date within 3 days and not returned
        // Match the Returns page behavior: teacher borrows are `role = teacher`, everything else is student/legacy.
        $nearDueStudentBorrows = Borrow::whereNull('returned_at')
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(3))
            ->where(function ($q) {
                $q->whereNull('role')->orWhere('role', '!=', 'teacher');
            })
            ->with(['book', 'student'])
            ->get();

        $nearDueTeacherBorrows = Borrow::whereNull('returned_at')
            ->whereDate('due_date', '>=', now())
            ->whereDate('due_date', '<=', now()->addDays(3))
            ->where('role', 'teacher')
            ->with(['book', 'teacher'])
            ->get();

        $nearDueBorrows = $nearDueStudentBorrows->concat($nearDueTeacherBorrows);
        // Students with unreturned books (paginated)
        $studentsWithUnreturned = User::whereHas('borrows', function ($q) {
                $q->whereNull('returned_at');
            })
            ->with(['borrows' => function ($q) {
                $q->whereNull('returned_at')->with('book');
            }])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(5, ['*'], 'studentsPage');

        // Available books (paginated)
        $availableBooks = Book::where('status', 'available')
            ->orderBy('title')
            ->paginate(5, ['*'], 'booksPage');
        
        // Enrich available books with accurate copy counts from BookCopy table
        $availableBooks->getCollection()->transform(function ($book) {
            $book->total_copies_actual = \App\Models\BookCopy::where('book_id', $book->id)->count();
            $book->available_copies_actual = \App\Models\BookCopy::where('book_id', $book->id)
                ->where('status', 'available')
                ->where('is_lost_damaged', false)
                ->count();
            return $book;
        });

        // Prepare enhanced data for Most Borrowed Books chart
        $mostBorrowedBooks = Borrow::select('book_id')
            ->whereNotNull('book_id')
            ->get()
            ->groupBy('book_id')
            ->map(fn($group) => count($group))
            ->sortDesc();

        $mostBorrowedBookLabels = [];
        $mostBorrowedBookData = [];
        $mostBorrowedBookDetails = [];
        $totalBorrowsCount = $totalBorrows;
        
        // Limit to top 10 for better readability
        $topCount = 0;
        foreach ($mostBorrowedBooks as $bookId => $count) {
            if ($topCount >= 10) break;
            
            $book = Book::find($bookId);
            if ($book) {
                $mostBorrowedBookLabels[] = strlen($book->title) > 30 ? substr($book->title, 0, 27) . '...' : $book->title;
                $mostBorrowedBookData[] = $count;
                
                // Calculate additional metrics
                $percentageOfTotal = $totalBorrowsCount > 0 ? round(($count / $totalBorrowsCount) * 100, 1) : 0;
                $availableCopies = \App\Models\BookCopy::where('book_id', $book->id)
                    ->where('status', 'available')
                    ->where('is_lost_damaged', false)
                    ->count();
                $totalCopies = \App\Models\BookCopy::where('book_id', $book->id)->count();
                
                $mostBorrowedBookDetails[] = [
                    'title' => $book->title,
                    'author' => $book->author,
                    'borrows' => $count,
                    'percentage' => $percentageOfTotal,
                    'available_copies' => $availableCopies,
                    'total_copies' => $totalCopies
                ];
                
                $topCount++;
            }
        }
        
        // Calculate aggregate statistics
        $totalUniqueBooksInCatalog = Book::count();
        $totalUniqueBooksBorrowed = $mostBorrowedBooks->count();
        $avgBorrowsPerBook = $totalUniqueBooksBorrowed > 0 ? round($totalBorrowsCount / $totalUniqueBooksBorrowed, 1) : 0;
        $mostBorrowedBookRecord = $mostBorrowedBookDetails[0] ?? null;

        // Prepare monthly activity data with enhanced statistics
        $monthlyLabelsSafe = [];
        $monthlyDataSafe = [];
        $monthlyActiveData = [];
        $monthlyCompletedData = [];
        $monthlyStats = [];
        
        // Get the last 12 months of borrow activity
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyLabelsSafe[] = $date->format('M');
            
            // Total new borrows created in this month
            $totalBorrows = Borrow::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            // Active borrows (not yet returned) created in this month
            $activeBorrows = Borrow::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->whereNull('returned_at')
                ->count();
            
            // Completed returns in this month
            $completedReturns = Borrow::whereYear('returned_at', $date->year)
                ->whereMonth('returned_at', $date->month)
                ->whereNotNull('returned_at')
                ->count();
            
            $monthlyDataSafe[] = $totalBorrows;
            $monthlyActiveData[] = $activeBorrows;
            $monthlyCompletedData[] = $completedReturns;
            
            $monthlyStats[] = [
                'month' => $date->format('M Y'),
                'total' => $totalBorrows,
                'active' => $activeBorrows,
                'completed' => $completedReturns
            ];
        }
        
        // Calculate aggregate statistics
        $avgMonthlyActivity = !empty($monthlyDataSafe) ? array_sum($monthlyDataSafe) / count($monthlyDataSafe) : 0;
        $peakMonthActivity = !empty($monthlyDataSafe) ? max($monthlyDataSafe) : 0;
        $lowestMonthActivity = !empty($monthlyDataSafe) ? min($monthlyDataSafe) : 0;
        $peakMonthIndex = !empty($monthlyDataSafe) ? array_key_last(array_filter($monthlyDataSafe, fn($v) => $v == $peakMonthActivity)) : -1;

        return view('dashboard', compact(
            'totalBooks',
            'totalUsers',
            'totalBorrows',
            'studentsWithUnreturned',
            'availableBooks',
            'mostBorrowedBookLabels',
            'mostBorrowedBookData',
            'mostBorrowedBookDetails',
            'totalUniqueBooksBorrowed',
            'avgBorrowsPerBook',
            'mostBorrowedBookRecord',
            'monthlyLabelsSafe',
            'monthlyDataSafe',
            'monthlyActiveData',
            'monthlyCompletedData',
            'avgMonthlyActivity',
            'peakMonthActivity',
            'lowestMonthActivity',
            'peakMonthIndex',
            'monthlyStats',
            'nearDueBorrows',
            'nearDueStudentBorrows',
            'nearDueTeacherBorrows'
        ));
    }

    public function reports(Request $request)
    {
        // Sample metrics/data for the reports view. Replace with real queries as needed.
        $totalTransactions = Borrow::count();
        $totalStudents = User::whereNull('deleted_at')->count();
        $totalTeachers = \App\Models\Teacher::whereNull('deleted_at')->count();
        $booksInCirculation = Borrow::whereNull('returned_at')->count();
        $overdueItems = Borrow::whereNull('returned_at')->where('due_date', '<', now())->count();

        // Popular books
        $popular = Borrow::select('book_id')
            ->whereNotNull('book_id')
            ->get()
            ->groupBy('book_id')
            ->map(fn($group) => count($group))
            ->sortDesc();

        $popularLabels = [];
        $popularData = [];
        foreach ($popular as $bookId => $count) {
            $book = Book::find($bookId);
            if ($book) {
                $popularLabels[] = $book->title;
                $popularData[] = $count;
            }
        }

        // Categories sample (aggregate from books)
        $categoryCounts = Book::select('category')
            ->get()
            ->groupBy('category')
            ->map(fn($g) => count($g));

        $categoryLabels = $categoryCounts->keys()->toArray();
        $categoryData = array_values($categoryCounts->toArray());

        // Monthly activity: last 12 months of borrowing activity
        $monthlyData = [];
        $monthlyLabels = [];
        
        // Start from 12 months ago through today
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $label = $month->format('M');
            $monthlyLabels[] = $label;
            
            // Count borrows created in this month (use created_at or borrowed_at if available)
            $count = Borrow::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
            
            $monthlyData[] = $count;
        }

        // Detailed transactions with pagination and sorting
        $sortBy = $request->get('sort', 'borrowed_at');
        $sortOrder = $request->get('order', 'desc');
        $statusFilter = $request->get('status', 'all');
        $remarksSearch = trim((string) $request->get('remarks', ''));

        // Validate sort parameters for security
        $sortBy = in_array($sortBy, ['id', 'borrowed_at', 'due_date', 'returned_at']) ? $sortBy : 'borrowed_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

        // Use eager loading to improve performance - include lost/damaged items and their histories
        $transactionsQuery = Borrow::with([
            'book',
            'bookCopy',
            'creator',
            'returner',
            'lostDamagedItem' => function ($query) {
                $query->with('histories')->latest('created_at');
            }
        ])
            ->select('borrows.*');

        // Apply remarks/status search (matches return_status, remark, notes, and lost/damaged history fields)
        if ($remarksSearch !== '') {
            $normalizedRemarks = strtolower($remarksSearch);
            $normalizedRemarks = preg_replace('/[^a-z0-9]+/', '_', $normalizedRemarks) ?? '';
            $normalizedRemarks = trim($normalizedRemarks, '_');

            $likeRaw = '%' . $remarksSearch . '%';
            $likeNormalized = $normalizedRemarks !== '' ? ('%' . $normalizedRemarks . '%') : $likeRaw;

            $historyActionCandidates = [];
            if ($normalizedRemarks !== '') {
                if (str_contains($normalizedRemarks, 'found')) {
                    $historyActionCandidates[] = 'returned';
                }
                if (str_contains($normalizedRemarks, 'repair') || str_contains($normalizedRemarks, 'repaired')) {
                    $historyActionCandidates[] = 'repaired';
                    $historyActionCandidates[] = 'resolved';
                    $historyActionCandidates[] = 'replaced';
                }
                if (str_contains($normalizedRemarks, 'lost') || str_contains($normalizedRemarks, 'damage') || str_contains($normalizedRemarks, 'damaged')) {
                    $historyActionCandidates[] = 'created';
                }
                if (str_contains($normalizedRemarks, 'pending') || str_contains($normalizedRemarks, 'active')) {
                    $historyActionCandidates[] = 'pending';
                }
            }
            $historyActionCandidates = array_values(array_unique(array_filter($historyActionCandidates)));

            $transactionsQuery->where(function ($query) use ($likeRaw, $likeNormalized, $historyActionCandidates) {
                $query
                    ->where('borrows.return_status', 'like', $likeNormalized)
                    ->orWhere('borrows.return_status', 'like', $likeRaw)
                    ->orWhere('borrows.remark', 'like', $likeRaw)
                    ->orWhere('borrows.remark', 'like', $likeNormalized)
                    ->orWhere('borrows.notes', 'like', $likeRaw)
                    ->orWhere('borrows.notes', 'like', $likeNormalized)
                    ->orWhereHas('lostDamagedItem', function ($lostQuery) use ($likeRaw, $likeNormalized, $historyActionCandidates) {
                        $lostQuery
                            ->where('type', 'like', $likeRaw)
                            ->orWhere('type', 'like', $likeNormalized)
                            ->orWhere('status', 'like', $likeRaw)
                            ->orWhere('status', 'like', $likeNormalized)
                            ->orWhere('remarks', 'like', $likeRaw)
                            ->orWhere('remarks', 'like', $likeNormalized)
                            ->orWhereHas('histories', function ($historyQuery) use ($likeRaw, $likeNormalized, $historyActionCandidates) {
                                $historyQuery
                                    ->where('action', 'like', $likeRaw)
                                    ->orWhere('action', 'like', $likeNormalized)
                                    ->orWhere('remarks', 'like', $likeRaw)
                                    ->orWhere('remarks', 'like', $likeNormalized);

                                if (!empty($historyActionCandidates)) {
                                    $historyQuery->orWhereIn('action', $historyActionCandidates);
                                }
                            });
                    });
            });
        }

        // Apply status filter
        if ($statusFilter === 'active') {
            $transactionsQuery->whereNull('returned_at');
        } elseif ($statusFilter === 'completed') {
            $transactionsQuery->whereNotNull('returned_at');
        }

        // Apply sorting
        $transactions = $transactionsQuery->orderBy($sortBy, $sortOrder)
            ->paginate(10, ['*'], 'transactionsPage');

        // Enrich transactions with borrower names and status information
        $transactions->getCollection()->transform(function ($transaction) {
            $borrower = $transaction->role === 'teacher' 
                ? \App\Models\Teacher::withTrashed()->find($transaction->user_id)
                : User::withTrashed()->find($transaction->user_id);
            
            $transaction->borrower_name = $borrower 
                ? trim(($borrower->first_name ?? '') . ' ' . ($borrower->last_name ?? ''))
                : 'Unknown';
            
            $transaction->borrower_type = $transaction->role === 'teacher' ? 'Teacher' : 'Student';
            
            // Get the transaction status including lost/damaged/repaired/found transitions
            $transaction->transaction_status = $transaction->getTransactionStatus();
            $transaction->transaction_status_label = $transaction->getTransactionStatusLabel();
            $transaction->transaction_loss_type = $transaction->getLossType();
            
            // Add flag to indicate if this is a lost/damaged transaction
            $transaction->is_lost_or_damaged = $transaction->isLostOrDamaged();

            $isReturned = !is_null($transaction->returned_at);
            $actorUser = $isReturned ? ($transaction->returner ?? null) : ($transaction->creator ?? null);
            $actorRole = $isReturned ? ($transaction->returned_by_role ?? null) : ($transaction->created_by_role ?? null);
            $actorRole = $actorRole ?: ($actorUser?->role ?: null);

            $actorName = $actorUser?->name ?: ($actorUser?->email ?: null);
            if ($actorName) {
                $transaction->processed_by_display = $actorRole ? (ucfirst($actorRole) . ': ' . $actorName) : $actorName;
            } else {
                $transaction->processed_by_display = '—';
            }
            
            return $transaction;
        });

        // ===== BOOKS CIRCULATION REPORT DATA =====
        // Use the same total books calculation as the dashboard
        $totalBooks = Book::sum('copies');
        
        // Get currently borrowed book copies
        $borrowedBooks = \App\Models\BookCopy::whereHas('borrows', function ($query) {
            $query->whereNull('returned_at');
        })->count();
        
        // Get repaired items - check LostDamagedItem status field for 'repaired'
        $repairedBooks = LostDamagedItem::where('status', 'repaired')->count();
        
        // Get lost items - check LostDamagedItem type field for 'lost'
        $lostBooks = LostDamagedItem::where('type', 'lost')->count();
        
        // Get damaged copies (marked as damaged, excluding those that are repaired or lost)
        // Get IDs of books that are repaired or lost to exclude them from damaged count
        $excludeBorrowIds = LostDamagedItem::whereIn('type', ['lost'])
            ->where('status', '!=', 'repaired')
            ->pluck('borrow_id')
            ->toArray();
        
        $damagedBooks = \App\Models\BookCopy::where('status', 'damaged')
            ->orWhere('is_lost_damaged', true);
        
        if (!empty($excludeBorrowIds)) {
            $damagedBooks = $damagedBooks->whereDoesntHave('borrows', function ($query) use ($excludeBorrowIds) {
                $query->whereIn('id', $excludeBorrowIds);
            });
        }
        
        $damagedBooks = $damagedBooks->count();
        
        // Calculate available books as the remainder
        // Available = Total - (Borrowed + Damaged + Lost + Repaired)
        $availableBooks = max(0, $totalBooks - ($borrowedBooks + $damagedBooks + $lostBooks + $repairedBooks));

        return view('reports', compact(
            'totalTransactions','totalStudents','totalTeachers','booksInCirculation','overdueItems',
            'popularLabels','popularData','categoryLabels','categoryData','monthlyLabels','monthlyData',
            'totalBooks', 'availableBooks', 'borrowedBooks', 'damagedBooks', 'lostBooks', 'repairedBooks',
            'transactions', 'sortBy', 'sortOrder', 'statusFilter', 'remarksSearch'
        ));
    }
}
