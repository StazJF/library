@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Edit Staff</h3>

    {{-- Display Validation Errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Old password incorrect error --}}
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('staff.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')

        {{-- Email --}}
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input 
                type="email" 
                name="email" 
                class="form-control @error('email') is-invalid @enderror" 
                value="{{ old('email', $user->email) }}" 
                required
            >
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Name --}}
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input
                type="text"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name', $user->name) }}"
                required
            >
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Employee ID --}}
        <div class="mb-3">
            <label class="form-label">Employee ID</label>
            <input 
                type="text" 
                name="employee_id" 
                class="form-control @error('employee_id') is-invalid @enderror" 
                value="{{ old('employee_id', $user->employee_id) }}" 
                required
            >
            @error('employee_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Role --}}
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select @error('role') is-invalid @enderror" required>
                <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff</option>
                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
            @error('role')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Password Change Section --}}
        <div class="mb-3">
            <label class="form-label">Old Password 
                <small class="text-muted">(required if changing password)</small>
            </label>
            <input 
                type="password" 
                name="old_password" 
                class="form-control @error('old_password') is-invalid @enderror" 
                placeholder="Enter old password"
            >
            @error('old_password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input 
                type="password" 
                name="new_password" 
                class="form-control @error('new_password') is-invalid @enderror" 
                placeholder="Enter new password"
            >
            @error('new_password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        {{-- Form Buttons --}}
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Update Staff</button>
            <a href="{{ route('staff.index') }}" class="btn btn-secondary ms-2">Cancel</a>
        </div>
    </form>
</div>
@endsection
