<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SystemUser;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UserManagementController extends Controller
{
    // Show all staff and admin
    public function index()
    {
        $users = SystemUser::whereIn('role', ['staff', 'admin'])
                            ->paginate(10);

        return view('staff.index', compact('users'));
    }

    // Show create form
    public function create()
    {
        return view('staff.create');
    }

    // Store staff/admin
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:system_users,email',
            'name' => 'required|string|max:255',
            'employee_id' => 'required|string|max:255|unique:system_users,employee_id',
            'role' => 'required|in:staff,admin',
            'password' => 'required|min:6',
        ]);

        if (!Schema::hasColumn('system_users', 'name')) {
            return back()
                ->withInput()
                ->with('error', "Database schema mismatch: missing `system_users.name`. Run `php artisan migrate` to apply pending migrations.");
        }

        $user = SystemUser::create([
            'email' => $request->email,
            'name' => $request->name,
            'employee_id' => $request->employee_id,
            'role'  => $request->role,
            'password' => bcrypt($request->password),
        ]);

        // Log activity
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'create',
            'target_type' => 'staff',
            'target_id' => $user->id,
            'details' => "Created staff/admin with name: {$user->name}, email: {$user->email}, employee_id: {$user->employee_id} and role: {$user->role}",
        ]);

        return redirect()->route('staff.index')
                         ->with('success', 'Staff created successfully.');
    }

    // Edit staff/admin
    public function edit($id)
    {
        $user = SystemUser::findOrFail($id);
        return view('staff.edit', compact('user'));
    }

    // Update staff/admin
    public function update(Request $request, $id)
    {
        $user = SystemUser::findOrFail($id);

        // Validate role + email + employee_id
        $request->validate([
            'email' => 'required|email|unique:system_users,email,' . $id . ',id',
            'name' => 'required|string|max:255',
            'employee_id' => 'required|string|max:255|unique:system_users,employee_id,' . $id . ',id',
            'role'  => 'required|in:staff,admin',
        ]);

        if (!Schema::hasColumn('system_users', 'name')) {
            return back()
                ->withInput()
                ->with('error', "Database schema mismatch: missing `system_users.name`. Run `php artisan migrate` to apply pending migrations.");
        }

        $changes = [];

        if ($user->email !== $request->email) {
            $changes[] = "email from {$user->email} to {$request->email}";
            $user->email = $request->email;
        }

        if (($user->name ?? '') !== ($request->name ?? '')) {
            $changes[] = "name from {$user->name} to {$request->name}";
            $user->name = $request->name;
        }

        if ($user->employee_id !== $request->employee_id) {
            $changes[] = "employee_id from {$user->employee_id} to {$request->employee_id}";
            $user->employee_id = $request->employee_id;
        }

        if ($user->role !== $request->role) {
            $changes[] = "role from {$user->role} to {$request->role}";
            $user->role = $request->role;
        }

        // Password update logic
        if ($request->filled('old_password') || $request->filled('new_password')) {
            $request->validate([
                'old_password' => 'required',
                'new_password' => 'required|min:6',
            ]);

            if (!Hash::check($request->old_password, $user->password)) {
                return back()->withInput()->with('error', 'Old password is incorrect.');
            }

            $user->password = bcrypt($request->new_password);
            $changes[] = 'password updated';
        }

        $user->save();

        // Log activity if there were any changes
        if (!empty($changes)) {
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'update',
                'target_type' => 'staff',
                'target_id' => $user->id,
                'details' => 'Updated staff/admin: ' . implode(', ', $changes),
            ]);
        }

        return redirect()->route('staff.index')
                         ->with('success', 'Staff updated successfully.');
    }

    // Delete staff/admin
    public function destroy($id)
    {
        // Only admins can delete staff
        if (Auth::user() && Auth::user()->role !== 'admin') {
            abort(403, 'Unauthorized. Only administrators can delete staff.');
        }

        $user = SystemUser::findOrFail($id);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'delete',
            'target_type' => 'staff',
            'target_id' => $user->id,
            'details' => "Deleted staff/admin with email: {$user->email}",
        ]);

        $user->delete();

        return redirect()->route('staff.index')
                         ->with('success', 'Staff deleted successfully.');
    }
}
