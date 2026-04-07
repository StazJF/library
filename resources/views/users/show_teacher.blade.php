@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0" style="color:#111;">Teacher Details</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('teachers.edit', $teacher->id) }}" class="btn btn-dark">
                <i class="bi bi-pencil me-2"></i>Edit
            </a>
            <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Teachers
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Name</h6>
                    <p class="fw-semibold">{{ $teacher->name }}</p>
                </div>
                <div class="col-md-6 mb-4">
    <h6 class="text-muted mb-2">Email</h6>
    <p class="text-dark">{{ $teacher->email }}</p>
</div>
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Employee ID</h6>
                    <p class="fw-semibold">{{ $teacher->employee_id }}</p>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Rank/Position</h6>
                    <p class="fw-semibold">{{ $teacher->rank_position }}</p>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Gender</h6>
                    <p><span class="">{{ ucfirst($teacher->gender) }}</span></p>
                </div>
                <div class="col-md-6 mb-4">
                    <h6 class="text-muted mb-2">Address</h6>
                    <p class="fw-semibold">{{ $teacher->address }}</p>
                </div>
               <div class="col-md-6 mb-4">
    <h6 class="text-muted mb-2">Phone Number</h6>
    <p class="text-dark">{{ $teacher->phone_number }}</p>
</div>
            </div>

            <hr>

            <div class="mt-4">
                <h5 class="fw-bold mb-3">Borrow History</h5>
                @php
                    $allBorrows = $teacher->borrows;
                @endphp
                @if($allBorrows->count() > 0)
                    <div style="max-height: 450px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem;">
                        <table class="table table-bordered mb-0">
                            <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>ISBN</th>
                                    <th>Borrowed At</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allBorrows as $borrow)
                                    @php
                                        $bookTitle = $borrow->book ? $borrow->book->title : 'Book not found';
                                        $bookAuthor = $borrow->book ? ($borrow->book->author ?? 'N/A') : 'N/A';
                                        $bookIsbn = $borrow->book ? ($borrow->book->isbn ?? 'N/A') : 'N/A';
                                        $borrowedAt = $borrow->borrowed_at ? \Carbon\Carbon::parse($borrow->borrowed_at)->format('M d, Y') : 'N/A';
                                        $dueDate = $borrow->due_date ? \Carbon\Carbon::parse($borrow->due_date)->format('M d, Y') : 'N/A';
                                        $status = $borrow->returned_at ? 'Returned' : 'Active';
                                    @endphp
                                    <tr>
                                        <td>{{ $bookTitle }}</td>
                                        <td>{{ $bookAuthor }}</td>
                                        <td>{{ $bookIsbn }}</td>
                                        <td>{{ $borrowedAt }}</td>
                                        <td>{{ $dueDate }}</td>
                                        <td>
                                            @if($status === 'Returned')
                                                <span class="badge bg-success">Returned</span>
                                            @else
                                                <span class="badge bg-primary">Active</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-muted">No borrow history.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
