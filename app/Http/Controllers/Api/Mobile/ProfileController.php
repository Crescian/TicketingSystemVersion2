<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\UserResource;
use App\Models\BusinessUnits;
use App\Models\Companies;
use App\Models\Departments;
use App\Services\RequestorTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// Profile, account setup (org info + password) and the small bits of
// dashboard state the mobile app needs. Same rules as the web
// App\Http\Controllers\ProfileController.
class ProfileController extends Controller
{
    public function me(Request $request)
    {
        return new UserResource($request->user()->load('role', 'department.company.businessUnit'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        if (Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['password' => 'Your new password cannot be the same as your current password.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        $this->maybeCompleteAccountSetup($user);

        return $this->me($request);
    }

    // Employees get one lifetime self-update; after that only IT can change it.
    public function updateOrgInfo(Request $request)
    {
        $user = $request->user();

        if ($user->org_info_updated_at !== null) {
            return response()->json([
                'message' => 'You have already updated your account information. Please contact your IT Administrator for further changes.',
            ], 422);
        }

        $request->validate([
            'department_id' => 'required|uuid|exists:departments,id',
            'position' => 'nullable|string|max:255',
        ]);

        $user->update([
            'department_id' => $request->department_id,
            'position' => $request->position,
            'org_info_updated_at' => now(),
        ]);

        $this->maybeCompleteAccountSetup($user);

        return $this->me($request);
    }

    // Business unit -> company -> department picker for the org-info form.
    public function hierarchy()
    {
        $businessUnits = BusinessUnits::with([
            'companies' => fn ($q) => $q->orderBy('company_name'),
            'companies.departments' => fn ($q) => $q->orderBy('department_name'),
        ])->orderBy('business_units_name')->get();

        return response()->json($businessUnits->map(fn (BusinessUnits $bu) => [
            'id' => $bu->id,
            'name' => $bu->business_units_name,
            'companies' => $bu->companies->map(fn (Companies $c) => [
                'id' => $c->id,
                'name' => $c->company_name,
                'departments' => $c->departments->map(fn (Departments $d) => [
                    'id' => $d->id,
                    'name' => $d->department_name,
                ])->values(),
            ])->values(),
        ]));
    }

    public function completeOnboarding(Request $request)
    {
        $request->user()->update(['onboarded_at' => now()]);

        return response()->noContent();
    }

    // The "Available IT" panel from the web dashboard.
    public function itTeam(RequestorTicketService $tickets)
    {
        return response()->json($tickets->itTeamStatus()->map(fn ($member) => [
            'id' => $member->id,
            'name' => $member->name,
            'role' => $member->role?->role_name,
            'online' => $member->online,
        ]));
    }

    // Releases the account-setup gate once both org info and password are done
    // (same rule as the web ProfileController).
    private function maybeCompleteAccountSetup($user): void
    {
        if ($user->needs_account_setup && !$user->must_change_password && $user->org_info_updated_at !== null) {
            $user->update(['needs_account_setup' => false]);
        }
    }
}
