<?php

namespace App\Http\Controllers;

use App\Models\BusinessUnits;
use App\Models\Companies;
use App\Models\Departments;
use App\Models\Tickets;
use Illuminate\Support\Facades\Auth;

// Renders the progress-tracker gate that App\Http\Middleware\
// RequirePasswordChange sends needs_account_setup Employees to. Lives on its
// own standalone page/layout (resources/views/account/setup.blade.php) rather
// than the shared dashboard shell, and does not touch the profile page. The
// org-info and password forms embedded here post to the same
// ProfileController routes (profile.org-info / profile.password) the profile
// page itself uses, so all the validation/one-time-lock logic stays in one
// place — this page just gives the same forms a focused, single-page home.
class AccountSetupController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('department.company.businessUnit');

        $orgComplete = $user->org_info_updated_at !== null;
        $passwordComplete = ! $user->must_change_password;

        $steps = [
            ['key' => 'business_unit', 'label' => 'Business Unit', 'icon' => 'bi-diagram-3', 'done' => $orgComplete],
            ['key' => 'company', 'label' => 'Company', 'icon' => 'bi-building', 'done' => $orgComplete],
            ['key' => 'department', 'label' => 'Department', 'icon' => 'bi-people', 'done' => $orgComplete],
            ['key' => 'position', 'label' => 'Position', 'icon' => 'bi-briefcase', 'done' => $orgComplete],
            ['key' => 'password', 'label' => 'Change Password', 'icon' => 'bi-shield-lock', 'done' => $passwordComplete],
        ];

        // Business Unit/Company/Department/Position are one combined form (see
        // ProfileController::updateOrgInfo), so they move together: all four
        // read as "current" while org info is outstanding, not one-at-a-time.
        foreach ($steps as &$step) {
            if ($step['done']) {
                $step['status'] = 'done';
            } elseif (! $orgComplete && $step['key'] !== 'password') {
                $step['status'] = 'current';
            } elseif ($orgComplete && $step['key'] === 'password') {
                $step['status'] = 'current';
            } else {
                $step['status'] = 'pending';
            }
        }
        unset($step);

        $allComplete = $orgComplete && $passwordComplete;
        $ticketCount = Tickets::where('users_id', $user->id)->count();

        $businessUnits = BusinessUnits::orderBy('business_units_name')->get();
        $companies = Companies::with('businessUnit')->orderBy('company_name')->get();
        $departments = Departments::with('company.businessUnit')->orderBy('department_name')->get();

        $companiesData = $companies->map(fn ($c) => [
            'id' => $c->id,
            'name' => $c->company_name,
            'business_unit_id' => $c->business_units_id,
        ])->values();

        $departmentsData = $departments->map(fn ($d) => [
            'id' => $d->id,
            'name' => $d->department_name,
            'company_id' => $d->companies_id,
        ])->values();

        return view('account.setup', compact(
            'user', 'steps', 'orgComplete', 'passwordComplete', 'allComplete', 'ticketCount',
            'businessUnits', 'companiesData', 'departmentsData'
        ));
    }
}
