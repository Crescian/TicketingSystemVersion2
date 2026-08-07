<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnits;
use App\Models\Companies;
use App\Models\Departments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $businessUnits = BusinessUnits::orderBy('business_units_name')->get();
        $companies = Companies::with('businessUnit')->orderBy('company_name')->get();
        $departments = Departments::with('company.businessUnit')->orderBy('department_name')->get();

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

        $usingDefaultPassword = Hash::check('password', Auth::user()->password);

        return view('profile', compact(
            'businessUnits',
            'companies',
            'departments',
            'companiesData',
            'departmentsData',
            'usingDefaultPassword'
        ));
    }

    public function updateOrgInfo(Request $request)
    {
        $user = Auth::user()->load('role');
        $isEmployee = $user->role?->role_name === 'Employee';

        // Employees get one lifetime self-update; other roles are unrestricted.
        if ($isEmployee && $user->org_info_updated_at !== null) {
            return back()->with('error', 'You have already updated your account information. Please contact your IT Administrator for further changes.');
        }

        $request->validate([
            'department_id' => 'required|uuid|exists:departments,id',
            'position' => 'nullable|string|max:255',
        ]);

        $user->update([
            'department_id' => $request->department_id,
            'position' => $request->position,
            'org_info_updated_at' => $isEmployee ? now() : $user->org_info_updated_at,
        ]);

        return back()->with('success', 'Your account information has been updated.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Check current password is correct
        if (! Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Your current password is incorrect.'])
                ->with('error', 'Your current password is incorrect.');
        }

        // Check new password is not the same as current
        if (Hash::check($request->password, $user->password)) {
            return back()
                ->with('error', 'Your new password cannot be the same as your current password.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '✅ Password updated successfully! Please keep it safe.');
    }
}
