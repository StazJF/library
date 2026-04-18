<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Borrow;
use App\Models\LostDamagedItem;
use Illuminate\Support\Facades\DB;
use App\Models\Teacher;
use Carbon\Carbon;

class TeacherBorrowController extends Controller
{
    private function syncBookCounts(Book $book): void
    {
        $total = $book->copies()->count();
        $available = $book->copies()
            ->where('status', 'available')
            ->where('is_lost_damaged', false)
            ->count();

        $book->forceFill([
            'copies' => $total,
            'available_copies' => $available,
            'status' => $available > 0 ? 'available' : 'borrowed',
        ])->save();
    }

    public function create()
    {
        $settings = DB::table('penalty_settings')->first();
        // teachers come from dedicated Teacher model
        $maxPersonalBorrows = 3;
        $teachers = Teacher::whereNull('deleted_at')
            ->withCount([
                'activeBorrows as active_personal_borrows_count' => function ($q) {
                    $q->where(function ($qq) {
                        $qq->whereNull('origin')->orWhere('origin', 'personal');
                    });
                },
            ])
            ->orderBy('name')
            ->get();
        // Filter books: only include those with available copies AND at least one non-lost control number
        $books = Book::all()
            ->filter(function($book) {
                $availableCtrls = $book->getAvailableControlNumbers();
                return $book->available_copies > 0 && !empty($availableCtrls);
            });
        $users = collect(); // empty for student form
        return view('borrow.create', compact('settings', 'teachers', 'books', 'users', 'maxPersonalBorrows'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required',
            'book_ids' => 'required|array|min:1',
            'book_ids.*' => 'required|integer',
            'borrowed_at' => 'required|date',
            'due_date' => 'required|date|after_or_equal:borrowed_at',
        ]);

        $teacher = Teacher::find($request->user_id);
        if (!$teacher) {
            return back()->with('error', 'Teacher not found.');
        }

        $maxPersonalBorrows = 3;
        $activePersonalBorrowCount = Borrow::where('user_id', $request->user_id)
            ->where('role', 'teacher')
            ->where(function ($q) {
                $q->whereNull('origin')->orWhere('origin', 'personal');
            })
            ->whereNull('returned_at')
            ->count();

        if ($activePersonalBorrowCount >= $maxPersonalBorrows) {
            return back()->with('error', "You can only have {$maxPersonalBorrows} active personal book borrows at a time. Please return some books first.");
        }

        if ($activePersonalBorrowCount + count($request->book_ids) > $maxPersonalBorrows) {
            return back()->with('error', 'You can only borrow ' . ($maxPersonalBorrows - $activePersonalBorrowCount) . ' more personal book(s). Currently borrowed: ' . $activePersonalBorrowCount);
        }

        $borrowDate = Carbon::parse($request->borrowed_at);
        $returnDate = Carbon::parse($request->due_date);

        $success = 0;
        $errors = [];

        foreach ($request->book_ids as $bookId) {
            $book = Book::find($bookId);
            if (!$book) {
                $errors[] = "Book {$bookId} not found.";
                continue;
            }
            if (($book->available_copies ?? 0) < 1) {
                $errors[] = "No available copies for '{$book->title}'.";
                continue;
            }

            $alreadyBorrowed = Borrow::where('user_id', $request->user_id)
                ->where('book_id', $bookId)
                ->where('role', 'teacher')
                ->whereNull('returned_at')
                ->exists();
            if ($alreadyBorrowed) {
                $errors[] = "You have already borrowed '{$book->title}' and not returned it yet.";
                continue;
            }

            try {
                $borrow = DB::transaction(function () use ($teacher, $book, $borrowDate, $returnDate) {
                    $bookCopy = $book->copies()
                        ->where('status', 'available')
                        ->where('is_lost_damaged', false)
                        ->orderByRaw("CASE WHEN control_number IS NULL THEN 1 ELSE 0 END")
                        ->orderBy('control_number')
                        ->lockForUpdate()
                        ->first();

                    if (!$bookCopy || !$bookCopy->isAvailable()) {
                        return null;
                    }

                    $borrow = Borrow::create([
                        'user_id' => $teacher->id,
                        'book_id' => $book->id,
                        'book_copy_id' => $bookCopy->id,
                        'borrowed_at' => $borrowDate,
                        'due_date' => $returnDate,
                        'returned_at' => null,
                        'role' => 'teacher',
                        'origin' => 'personal',
                        'copy_number' => $bookCopy->control_number,
                    ]);

                    $bookCopy->markAsBorrowed();
                    $this->syncBookCounts($book);

                    return $borrow;
                });

                if (!$borrow) {
                    $errors[] = "No available copy for '{$book->title}'.";
                    continue;
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to borrow '{$book->title}': " . $e->getMessage();
                continue;
            }

            $success++;
        }

        if ($success > 0) {
            $message = "{$success} book(s) borrowed successfully.";
            if (!empty($errors)) {
                $message .= ' Errors: ' . implode(' ', $errors);
            }
            return redirect()->route('teachers.index')->with('success', $message);
        }

        return back()->with('error', 'No books were borrowed. ' . implode(' ', $errors));
    }

    // Process a teacher book return
    public function processReturn(Request $request, $borrowId)
    {
        $borrow = Borrow::with('book')->where('id', $borrowId)->firstOrFail();

        $request->validate([
            'remark' => ['nullable', 'in:No Remarks,On Time,Late Return,Lost,Damage'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $dueDate = $borrow->due_date ? Carbon::parse($borrow->due_date) : null;
        $today = Carbon::now();
        $computedRemark = 'No Remarks';
        if ($dueDate && $today->gt($dueDate)) {
            $overdueDays = (int) ceil($today->diffInDays($dueDate));
            $computedRemark = "{$overdueDays} day(s) overdue";
        }
        $inputRemark = trim((string) $request->input('remark', ''));
        $borrow->remark = $inputRemark !== '' ? $inputRemark : $computedRemark;
        $borrow->notes = trim(($borrow->notes ? $borrow->notes . "\n" : '') . $request->input('notes', ''));
        
        // Determine return_status based on remark and due date
        $returnStatus = $this->determineReturnStatus($borrow->remark, $dueDate, $today);
        $borrow->return_status = $returnStatus;
        
        // Record lost or damaged items
        if ($borrow->remark === 'Lost' || $borrow->remark === 'Damage') {
            LostDamagedItem::create([
                'borrow_id' => $borrow->id,
                'book_id' => $borrow->book_id,
                'user_id' => $borrow->user_id,
                'type' => $borrow->remark === 'Lost' ? 'lost' : 'damaged',
                'copy_number' => $borrow->copy_number ?? 'BK-' . $borrow->book_id,
                'remarks' => $borrow->notes,
                'due_date' => $borrow->due_date,
                'status' => 'active',
                'role' => 'teacher',
            ]);
        }
        
        $borrow->returned_at = now();
        $borrow->save();

        // Update teacher's remark if there's a remark from return (except 'No Remarks')
        if ($borrow->remark && $borrow->remark !== 'No Remarks') {
            $teacher = $borrow->getBorrower();
            if ($teacher) {
                $teacher->remark = $borrow->remark;
                $teacher->save();
            }
        }

        // Update book status
        if ($borrow->book) {
            $borrow->book->status = 'available';
            $borrow->book->save();
        }
        return redirect()->route('books.catalog')->with('success', 'Book returned successfully!');
    }

    /**
     * Determine the return status based on remark and due date
     *
     * @param string $remark The return remark
     * @param \Carbon\Carbon|null $dueDate The due date
     * @param \Carbon\Carbon $today Current date
     * @return string The return status
     */
    private function determineReturnStatus($remark, $dueDate = null, $today = null)
    {
        if (!$today) {
            $today = Carbon::now();
        }

        // Check for specific remarks that map to statuses
        if ($remark === 'Damage') {
            return Borrow::STATUS_DAMAGED_FOR_REPAIR;
        }

        if ($remark === 'Lost') {
            return Borrow::STATUS_LOST_AND_FOUND;
        }

        // If no due date or already returned, check if it's overdue
        if ($dueDate && $today->gt($dueDate)) {
            return Borrow::STATUS_LATE_RETURN;
        }

        // Default to returned on time
        return Borrow::STATUS_RETURNED_ON_TIME;
    }
}
