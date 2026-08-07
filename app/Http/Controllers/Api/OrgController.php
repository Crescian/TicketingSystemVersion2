<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnits;
use App\Models\Companies;
use App\Models\Departments;
use App\Models\User;
use Illuminate\Http\Request;

// Read-only org/user directory for in-house systems to consume — gated behind
// the 'org:read' Sanctum token ability (see IssueOrgApiToken), independent of
// the session-based web roles used elsewhere in this app.
class OrgController extends Controller
{
    public function businessUnits()
    {
        return response()->json(
            BusinessUnits::orderBy('business_units_name')->get()->map(fn (BusinessUnits $bu) => [
                'id' => $bu->id,
                'name' => $bu->business_units_name,
            ])
        );
    }

    public function companies(Request $request)
    {
        $companies = Companies::with('businessUnit')
            ->when($request->filled('business_unit_id'), fn ($q) => $q->where('business_units_id', $request->business_unit_id))
            ->orderBy('company_name')
            ->get();

        return response()->json($companies->map(fn (Companies $c) => [
            'id' => $c->id,
            'name' => $c->company_name,
            'business_unit_id' => $c->business_units_id,
            'business_unit' => $c->businessUnit?->business_units_name,
        ]));
    }

    public function departments(Request $request)
    {
        $departments = Departments::with('company.businessUnit')
            ->when($request->filled('company_id'), fn ($q) => $q->where('companies_id', $request->company_id))
            ->orderBy('department_name')
            ->get();

        return response()->json($departments->map(fn (Departments $d) => [
            'id' => $d->id,
            'name' => $d->department_name,
            'company_id' => $d->companies_id,
            'company' => $d->company?->company_name,
            'business_unit_id' => $d->company?->business_units_id,
            'business_unit' => $d->company?->businessUnit?->business_units_name,
        ]));
    }

    public function users(Request $request)
    {
        $paginator = User::with('department.company.businessUnit')
            ->when($request->filled('department_id'), fn ($q) => $q->where('department_id', $request->department_id))
            ->when($request->filled('company_id'), fn ($q) => $q->whereHas('department', fn ($q2) => $q2->where('companies_id', $request->company_id)))
            ->when($request->filled('business_unit_id'), fn ($q) => $q->whereHas('department.company', fn ($q2) => $q2->where('business_units_id', $request->business_unit_id)))
            ->when($request->filled('active'), fn ($q) => $q->where('active', filter_var($request->active, FILTER_VALIDATE_BOOLEAN)))
            ->orderBy('name')
            ->paginate(min((int) $request->input('per_page', 50), 200));

        $paginator->getCollection()->transform(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'position' => $u->position,
            'active' => $u->active,
            'department_id' => $u->department_id,
            'department' => $u->department?->department_name,
            'company' => $u->department?->company?->company_name,
            'business_unit' => $u->department?->company?->businessUnit?->business_units_name,
        ]);

        return response()->json($paginator);
    }
}
