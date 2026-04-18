@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0" style="color:#111;">Add Teacher</h2>
        <a href="{{ route('teachers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Teachers
        </a>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('teachers.store') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" value="{{ old('employee_id') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="rank_position" class="form-label">Rank/Position <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rank_position" name="rank_position" value="{{ old('rank_position') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="address" name="address" value="{{ old('address') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number') }}" required>
                    </div>
                </div>

                <div class="border rounded p-3 mb-3 bg-light">
                    <div class="form-check">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            id="data_privacy_agreement"
                            name="data_privacy_agreement"
                            value="1"
                            {{ old('data_privacy_agreement') ? 'checked' : '' }}
                            required
                        >
                        <label class="form-check-label" for="data_privacy_agreement">
                            I confirm that the teacher has been informed and agrees to the collection and use of their personal data for library management purposes.
                        </label>
                    </div>
                    <div class="small text-muted mt-2">
                        Required before saving. This is used for creating and managing teacher records (e.g., borrowing history, contact details, and reporting).
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Save Teacher</button>
            </form>
        </div>
    </div>
</div>
@endsection
