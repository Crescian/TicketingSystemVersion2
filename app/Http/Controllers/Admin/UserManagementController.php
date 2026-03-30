<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Departments;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $role = $request->get('role', '');
        $dept = $request->get('dept', '');
        $status = $request->get('status', '');

        $query = User::with(['role', 'department'])
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('position', 'ilike', "%{$search}%");
            });
        }

        if ($role) {
            $query->whereHas('role', fn($q) =>
                $q->where('role_name', $role));
        }

        if ($dept) {
            $query->whereHas('department', fn($q) =>
                $q->where('department_name', $dept));
        }

        if ($status !== '') {
            $query->where('active', $status === 'active');
        }

        $users = $query->paginate(10)->withQueryString();

        // Counts
        $counts = [
            'total' => User::count(),
            'active' => User::where('active', true)->count(),
            'inactive' => User::where('active', false)->count(),
            'techs' => User::whereHas('role', fn($q) =>
                $q->where('role_name', 'IT Support Specialist'))->count(),
        ];

        // Role counts for tabs
        $roleCounts = [
            'Employee' => User::whereHas('role', fn($q) =>
                $q->where('role_name', 'Employee'))->count(),
            'Helpdesk' => User::whereHas('role', fn($q) =>
                $q->where('role_name', 'Helpdesk'))->count(),
            'IT Support Specialist' => User::whereHas('role', fn($q) =>
                $q->where('role_name', 'IT Support Specialist'))->count(),
            'IT Admin' => User::whereHas('role', fn($q) =>
                $q->where('role_name', 'IT Admin'))->count(),
            'Executive' => User::whereHas('role', fn($q) =>
                $q->where('role_name', 'Executive'))->count(),
        ];

        $roles = Role::all();
        $departments = Departments::all();

        return view('admin.user-management', compact(
            'users',
            'counts',
            'roleCounts',
            'roles',
            'departments',
            'search',
            'role',
            'dept',
            'status'
        ));
    }

    // Store new user
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|uuid|exists:roles,id',
            'department_id' => 'required|uuid|exists:departments,id',
            'position' => 'nullable|string|max:255',
            'active' => 'required|boolean',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make("lgticketing"),
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'position' => $request->position,
            'active' => $request->active,
        ]);

        return back()->with(
            'success',
            "User {$request->name} added successfully."
        );
    }

    // Update user
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|uuid|exists:roles,id',
            'department_id' => 'required|uuid|exists:departments,id',
            'position' => 'nullable|string|max:255',
            'active' => 'required|boolean',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'position' => $request->position,
            'active' => $request->active,
        ]);

        return back()->with(
            'success',
            "User {$user->name} updated successfully."
        );
    }

    // Deactivate user
    public function deactivate(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'nullable|string',
        ]);

        $user->update(['active' => false]);

        return back()->with(
            'success',
            "{$user->name} has been deactivated."
        );
    }

    // Reactivate user
    public function reactivate(User $user)
    {
        $user->update(['active' => true]);

        return back()->with(
            'success',
            "{$user->name} has been reactivated."
        );
    }

    // Reset password
    public function resetPassword(Request $request, User $user)
    {
        $tempPassword = Str::random(10);
        $user->update(['password' => Hash::make("lgticketing")]);

        // In production: send email with $tempPassword
        // Mail::to($user->email)->send(new PasswordResetMail($tempPassword));

        return back()->with(
            'success',
            "Password reset for {$user->name}. Temp password: {$tempPassword}"
        );
    }

    // Get user data for edit modal (JSON)
    public function show(User $user)
    {
        return response()->json($user->load(['role', 'department']));
    }
}