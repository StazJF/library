<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\ActivityLog;
use App\Models\BookCopy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    private function hydrateMissingBookCopy($borrows): void
    {
        foreach ($borrows as $borrow) {
            if ($borrow->bookCopy) {
                continue;
            }

            $copyNumber = $borrow->copy_number ? trim((string) $borrow->copy_number) : null;
            if (!$copyNumber) {
                continue;
            }

            $copy = BookCopy::where('book_id', $borrow->book_id)
                ->where('control_number', $copyNumber)
                ->first();

            if (!$copy && preg_match('/^\d{3}$/', $copyNumber) === 1) {
                $candidates = BookCopy::where('book_id', $borrow->book_id)
                    ->where('control_number', 'like', $copyNumber . '-%')
                    ->limit(2)
                    ->get();
                if ($candidates->count() === 1) {
                    $copy = $candidates->first();
                }
            }

            if ($copy) {
                $borrow->setRelation('bookCopy', $copy);
            }
        }
    }

    public function index(Request $request)
    {
        $query = Teacher::whereNull('deleted_at');
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('gender', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('rank_position', 'like', "%{$search}%");
            });
        }
        $teachers = $query->with('borrows.book')->orderBy('name')->paginate(10);
        return view('users.teachers', compact('teachers'));
    }

    public function create()
    {
        return view('users.create_teacher');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:teachers,email',
            'employee_id' => 'required|string|max:255',
            'rank_position' => 'required|string|max:255',
            'gender'      => 'required|string',
            'address'     => 'required|string',
            'phone_number'=> 'required|string|max:20',
            'data_privacy_agreement' => 'accepted',
        ], [
            'data_privacy_agreement.accepted' => 'You must agree to the Data Privacy statement before saving.',
        ]);
        $teacher = Teacher::create($request->only(['name','email','employee_id','rank_position','gender','address','phone_number']));
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Added Teacher',
            'details' => "Teacher '{$teacher->name}' added by " . Auth::user()->name,
        ]);
        return redirect()->route('teachers.index')->with('success', 'Teacher created successfully.');
    }

    public function edit(Teacher $teacher)
    {
        return view('users.edit_teacher', compact('teacher'));
    }

    public function show(Teacher $teacher)
    {
        $origin = request()->query('origin'); // personal|distribution|null
        $status = request()->query('status'); // lost|damaged|repaired|found|issues|null
        $search = request()->query('search');

        $origin = in_array($origin, ['personal', 'distribution'], true) ? $origin : null;
        $status = in_array($status, ['lost', 'damaged', 'repaired', 'found', 'issues'], true) ? $status : null;

        $query = $teacher->borrows()
            ->with([
                'book',
                'bookCopy',
                'lostDamagedItem' => function($q) {
                    $q->with('histories');
                },
            ])
            ->latest('borrowed_at');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('book', function ($bookQ) use ($search) {
                    $bookQ->where('title', 'like', "%{$search}%")
                          ->orWhere('author', 'like', "%{$search}%");
                })
                ->orWhere('copy_number', 'like', "%{$search}%")
                ->orWhereHas('bookCopy', function ($copyQ) use ($search) {
                    $copyQ->where('control_number', 'like', "%{$search}%");
                });
            });
        }

        if ($origin) {
            $query->where('origin', $origin);
        }

        if ($status === 'issues') {
            $query->where(function ($q) {
                $q->whereHas('lostDamagedItem')
                    ->orWhereIn('remark', ['Lost', 'Damage']);
            });
        } elseif ($status === 'lost') {
            $query->where(function ($q) {
                $q->whereHas('lostDamagedItem', function ($inner) {
                    $inner->where('type', 'lost')->where('status', 'active');
                })->orWhere('remark', 'Lost');
            });
        } elseif ($status === 'damaged') {
            $query->where(function ($q) {
                $q->whereHas('lostDamagedItem', function ($inner) {
                    $inner->where('type', 'damaged')->where('status', 'active');
                })->orWhere('remark', 'Damage');
            });
        } elseif ($status === 'repaired') {
            $query->whereHas('lostDamagedItem', function ($inner) {
                $inner->where('type', 'damaged')
                    ->whereIn('status', ['repaired', 'returned']);
            });
        } elseif ($status === 'found') {
            $query->whereHas('lostDamagedItem', function ($inner) {
                $inner->where('type', 'lost')->where('status', 'returned');
            });
        }

        $borrows = $query->get();
        $this->hydrateMissingBookCopy($borrows);
        $teacher->setRelation('borrows', $borrows);

        // Counts (for filter UI badges) based on all borrows
        $allBorrows = $teacher->borrows()
            ->with(['lostDamagedItem' => function($q) {
                $q->with('histories');
            }])
            ->get();
        $this->hydrateMissingBookCopy($allBorrows);

        $statusCounts = [
            'lost' => 0,
            'damaged' => 0,
            'repaired' => 0,
            'found' => 0,
            'issues' => 0,
        ];
        foreach ($allBorrows as $b) {
            $lossType = $b->getLossType();
            if (!$lossType) {
                if (($b->remark ?? '') === 'Lost') {
                    $lossType = 'lost';
                } elseif (($b->remark ?? '') === 'Damage') {
                    $lossType = 'damaged';
                }
            }

            if ($lossType === 'lost') {
                $statusCounts['lost']++;
            } elseif ($lossType === 'damaged') {
                $statusCounts['damaged']++;
            } elseif ($lossType === 'repaired') {
                $statusCounts['repaired']++;
            } elseif ($lossType === 'found') {
                $statusCounts['found']++;
            }
        }
        $statusCounts['issues'] = $statusCounts['lost'] + $statusCounts['damaged'] + $statusCounts['repaired'] + $statusCounts['found'];

        $filterState = [
            'origin' => $origin ?? 'all',
            'status' => $status ?? 'all',
            'search' => $search ?? '',
        ];

        return view('users.show_teacher', compact('teacher', 'filterState', 'statusCounts'));
    }

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:teachers,email,' . $teacher->id,
            'employee_id' => 'required|string|max:255',
            'rank_position' => 'required|string|max:255',
            'gender'      => 'required|string',
            'address'     => 'required|string',
            'phone_number'=> 'required|string|max:20',
        ]);
        $teacher->update($request->only(['name','email','employee_id','rank_position','gender','address','phone_number']));
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Updated Teacher',
            'details' => "Teacher '{$teacher->name}' updated by " . Auth::user()->name,
        ]);
        return redirect()->route('teachers.index')->with('success', 'Teacher updated successfully.');
    }

    public function updateRemark(Request $request, Teacher $teacher)
    {
        $request->validate([
            'remark' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:255',
        ]);

        $remark = $request->input('remark');
        if ($request->filled('comment')) {
            $remark = 'Special Notes: ' . $request->input('comment');
        }

        $teacher->update(['remark' => $remark]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Updated Teacher Remark',
            'details' => "Remark for teacher '{$teacher->name}' updated to '{$remark}' by " . Auth::user()->name,
        ]);

        return redirect()->route('teachers.index')->with('success', 'Remark updated successfully.');
    }

    public function showBorrowHistory(Teacher $teacher, Request $request)
    {
        // Back-compat: old UI used `filter=all|personal|distribution|damaged`
        $legacyFilter = $request->query('filter', 'all');

        $origin = $request->query('origin'); // personal|distribution|null
        $status = $request->query('status'); // lost|damaged|repaired|found|issues|null

        if (!$origin && in_array($legacyFilter, ['personal', 'distribution'], true)) {
            $origin = $legacyFilter;
        }
        if (!$status && $legacyFilter === 'damaged') {
            $status = 'issues';
        }

        $origin = in_array($origin, ['personal', 'distribution'], true) ? $origin : null;
        $status = in_array($status, ['lost', 'damaged', 'repaired', 'found', 'issues'], true) ? $status : null;
        
        $query = $teacher->borrows()
            ->with([
                'book',
                'bookCopy',
                'lostDamagedItem' => function($q) {
                    $q->with('histories');
                },
            ])
            ->latest('borrowed_at');
        
        if (in_array($origin, ['personal', 'distribution'], true)) {
            $query->where('origin', $origin);
        }

        if ($status === 'issues') {
            $query->where(function ($q) {
                $q->whereHas('lostDamagedItem')
                    ->orWhereIn('remark', ['Lost', 'Damage']);
            });
        } elseif ($status === 'lost') {
            $query->where(function ($q) {
                $q->whereHas('lostDamagedItem', function ($inner) {
                    $inner->where('type', 'lost')->where('status', 'active');
                })->orWhere('remark', 'Lost');
            });
        } elseif ($status === 'damaged') {
            $query->where(function ($q) {
                $q->whereHas('lostDamagedItem', function ($inner) {
                    $inner->where('type', 'damaged')->where('status', 'active');
                })->orWhere('remark', 'Damage');
            });
        } elseif ($status === 'repaired') {
            $query->whereHas('lostDamagedItem', function ($inner) {
                $inner->where('type', 'damaged')
                    ->whereIn('status', ['repaired', 'returned']);
            });
        } elseif ($status === 'found') {
            $query->whereHas('lostDamagedItem', function ($inner) {
                $inner->where('type', 'lost')->where('status', 'returned');
            });
        }
        
        $borrows = $query->get();
        $this->hydrateMissingBookCopy($borrows);
        
        // Counts for status filter badges
        $allBorrows = $teacher->borrows()
            ->with(['lostDamagedItem' => function($q) {
                $q->with('histories');
            }])
            ->get();
        $this->hydrateMissingBookCopy($allBorrows);

        $statusCounts = [
            'lost' => 0,
            'damaged' => 0,
            'repaired' => 0,
            'found' => 0,
            'issues' => 0,
        ];

        foreach ($allBorrows as $b) {
            $lossType = $b->getLossType();
            if (!$lossType) {
                if (($b->remark ?? '') === 'Lost') {
                    $lossType = 'lost';
                } elseif (($b->remark ?? '') === 'Damage') {
                    $lossType = 'damaged';
                }
            }

            if ($lossType === 'lost') {
                $statusCounts['lost']++;
            } elseif ($lossType === 'damaged') {
                $statusCounts['damaged']++;
            } elseif ($lossType === 'repaired') {
                $statusCounts['repaired']++;
            } elseif ($lossType === 'found') {
                $statusCounts['found']++;
            }
        }
        $statusCounts['issues'] = $statusCounts['lost'] + $statusCounts['damaged'] + $statusCounts['repaired'] + $statusCounts['found'];
        
        $filterState = [
            'origin' => $origin ?? 'all',
            'status' => $status ?? 'all',
        ];
        
        return view('users.teacher-borrow-history', compact('teacher', 'borrows', 'legacyFilter', 'filterState', 'statusCounts'));
    }

    public function destroy(Teacher $teacher)
    {
        // Only admins can delete teachers
        if (Auth::user() && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized. Only administrators can delete teachers.');
        }

        $name = $teacher->name;
        $teacher->delete();
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Deleted Teacher',
            'details' => "Teacher '{$name}' deleted by " . Auth::user()->name,
        ]);
        return redirect()->route('teachers.index')->with('success', 'Teacher deleted successfully.');
    }

    // Import teachers from CSV
    public function importForm()
    {
        return view('users.teachers_import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $imported = 0;
        $errors = [];

        if (($handle = fopen($file->getPathname(), 'r')) !== false) {
            // Skip header row
            $header = fgetcsv($handle, 1000, ',');
            
            $rowNum = 2; // Start from row 2 (after header)
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                try {
                    if (count($row) < 5) {
                        $errors[] = "Row {$rowNum}: Insufficient columns.";
                        $rowNum++;
                        continue;
                    }

                    $name = trim($row[0] ?? '');
                    $email = trim($row[1] ?? '');
                    $gender = trim($row[2] ?? '');
                    $address = trim($row[3] ?? '');
                    $phone_number = trim($row[4] ?? '');

                    if (!$name || !$email) {
                        $errors[] = "Row {$rowNum}: Name and Email are required.";
                        $rowNum++;
                        continue;
                    }

                    // Check if teacher already exists
                    if (Teacher::where('email', $email)->exists()) {
                        $errors[] = "Row {$rowNum}: Email '{$email}' already exists.";
                        $rowNum++;
                        continue;
                    }

                    Teacher::create([
                        'name' => $name,
                        'email' => $email,
                        'gender' => $gender,
                        'address' => $address,
                        'phone_number' => $phone_number,
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$rowNum}: " . $e->getMessage();
                }
                $rowNum++;
            }
            fclose($handle);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action'  => 'Imported Teachers',
            'details' => "Imported {$imported} teacher(s) by " . Auth::user()->name,
        ]);

        session()->flash('import_summary', [
            'imported' => $imported,
            'errors' => $errors,
        ]);

        return redirect()->route('teachers.index');
    }
}
