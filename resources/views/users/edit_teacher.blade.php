@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0" style="color:#111;">Edit Teacher</h2>
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
            <form method="POST" action="{{ route('teachers.update', $teacher->id) }}">
                @csrf
                @method('PUT')
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $teacher->name) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $teacher->email) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" value="{{ old('employee_id', $teacher->employee_id) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="rank_position" class="form-label">Rank/Position <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rank_position" name="rank_position" value="{{ old('rank_position', $teacher->rank_position) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $teacher->gender) == 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $teacher->gender) == 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="address" class="form-label">Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="address" name="address" value="{{ old('address', $teacher->address) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number" value="{{ old('phone_number', $teacher->phone_number) }}" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Update Teacher</button>
            </form>
        </div>
    </div>
</div>
@endsection
