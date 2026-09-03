<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\BusinessUnits;
use App\Models\Companies;
use App\Models\Departments;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    // Roles the "IT Team — Online Now" panel surfaces, per admin's request
    // (helpdesk, IT tech, admin, managers — including their supervisor tiers).
    private const IT_TEAM_ROLES = [
        'Helpdesk',
        'IT Support Specialist',
        'Supervisor - Support Specialist',
        'IT Admin',
        'Supervisor - IT Admin',
        'Manager',
    ];

    private const ONLINE_WINDOW_MINUTES = 5;

    private function onlineUserIds(): \Illuminate\Support\Collection
    {
        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(self::ONLINE_WINDOW_MINUTES)->timestamp)
            ->distinct()
            ->pluck('user_id');
    }

    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $role = $request->get('role', '');
        $dept = $request->get('dept', '');
        $status = $request->get('status', '');

        $query = User::with(['role', 'department.company.businessUnit'])
            ->orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('position', 'ilike', "%{$search}%");
            });
        }

        if ($role) {
            $query->whereHas('role', fn($q) => $q->where('role_name', $role));
        }

        if ($dept) {
            $query->whereHas('department', fn($q) => $q->where('department_name', $dept));
        }

        if ($status !== '') {
            $query->where('active', $status === 'active');
        }

        $users = $query->paginate(10)->withQueryString();

        $onlineUserIds = $this->onlineUserIds();

        $lastActivityMap = DB::table('sessions')
            ->whereIn('user_id', $users->pluck('id'))
            ->selectRaw('user_id, MAX(last_activity) as last_activity')
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');

        $itTeamOnline = User::with('role')
            ->whereIn('id', $onlineUserIds)
            ->whereHas('role', fn($q) => $q->whereIn('role_name', self::IT_TEAM_ROLES))
            ->orderBy('name')
            ->get();

        $counts = [
            'total' => User::count(),
            'active' => User::where('active', true)->count(),
            'inactive' => User::where('active', false)->count(),
            'techs' => User::whereHas('role', fn($q) =>
                $q->where('role_name', 'IT Support Specialist'))->count(),
            'online' => $onlineUserIds->count(),
        ];

        $roleCounts = [
            'Employee' => User::whereHas('role', fn($q) => $q->where('role_name', 'Employee'))->count(),
            'Helpdesk' => User::whereHas('role', fn($q) => $q->where('role_name', 'Helpdesk'))->count(),
            'IT Support Specialist' => User::whereHas('role', fn($q) => $q->where('role_name', 'IT Support Specialist'))->count(),
            'IT Admin' => User::whereHas('role', fn($q) => $q->where('role_name', 'IT Admin'))->count(),
            'Manager' => User::whereHas('role', fn($q) => $q->where('role_name', 'Manager'))->count(),
        ];

        $roles = Role::all();
        $departments = Departments::with('company.businessUnit')->orderBy('department_name')->get();
        $businessUnits = BusinessUnits::orderBy('business_units_name')->get();
        $companies = Companies::with('businessUnit')->orderBy('company_name')->get();

        $companiesData = $companies->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->company_name,
            'business_unit_id' => $c->business_units_id,
        ])->values();

        $departmentsData = $departments->map(fn($d) => [
            'id' => $d->id,
            'name' => $d->department_name,
            'company_id' => $d->companies_id,
        ])->values();

        return view('admin.user-management', compact(
            'users',
            'counts',
            'roleCounts',
            'roles',
            'departments',
            'businessUnits',
            'companies',
            'companiesData',      // ← add
            'departmentsData',    // ← add
            'search',
            'role',
            'dept',
            'status',
            'onlineUserIds',
            'lastActivityMap',
            'itTeamOnline'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
            'position' => 'nullable|string|max:255',
            'active' => 'required|boolean',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('password'),
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'position' => $request->position,
            'active' => $request->active,
            'must_change_password' => true,
            'needs_account_setup' => true,
        ]);

        return back()->with('success', "User {$request->name} added successfully.");
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
            'department_id' => 'required|exists:departments,id',
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

        return back()->with('success', "User {$user->name} updated successfully.");
    }

    public function deactivate(Request $request, User $user)
    {
        $user->update(['active' => false]);
        return back()->with('success', "{$user->name} has been deactivated.");
    }

    public function reactivate(User $user)
    {
        $user->update(['active' => true]);
        return back()->with('success', "{$user->name} has been reactivated.");
    }

    public function resetPassword(Request $request, User $user)
    {
        if (!$user->email) {
            return back()->with('error', "Cannot reset password for {$user->name} — no email address on file.");
        }

        $tempPassword = Str::random(10);
        $user->update([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
            'needs_account_setup' => true,
        ]);

        Mail::to($user->email)->send(new TemporaryPasswordMail($user, $tempPassword));

        return back()->with('success', "Password reset for {$user->name}. A temporary password was emailed to them.");
    }

    // Polled every 30s from the user-management page to refresh who's online
    // without a full page reload — see resources/views/admin/user-management.blade.php.
    public function presence()
    {
        $onlineUserIds = $this->onlineUserIds();

        $itTeam = User::with('role')
            ->whereIn('id', $onlineUserIds)
            ->whereHas('role', fn($q) => $q->whereIn('role_name', self::IT_TEAM_ROLES))
            ->orderBy('name')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'role_name' => $u->role?->role_name,
            ]);

        return response()->json([
            'online_count' => $onlineUserIds->count(),
            'online_user_ids' => $onlineUserIds->values(),
            'it_team' => $itTeam,
        ]);
    }

    public function show(User $user)
    {
        $user->load(['role', 'department.company.businessUnit']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'position' => $user->position,
            'role_id' => $user->role_id,
            'department_id' => $user->department_id,
            'active' => $user->active,
            'department' => [
                'id' => $user->department?->id,
                'name' => $user->department?->department_name,
                'companies_id' => $user->department?->companies_id,
                'company' => [
                    'id' => $user->department?->company?->id,
                    'business_unit_id' => $user->department?->company?->business_units_id,
                    'businessUnit' => [
                        'id' => $user->department?->company?->businessUnit?->id,
                    ],
                ],
            ],
        ]);
    }
}