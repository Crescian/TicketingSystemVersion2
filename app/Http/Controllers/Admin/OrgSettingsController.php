<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessUnits;
use App\Models\Companies;
use App\Models\Departments;
use Illuminate\Http\Request;

class OrgSettingsController extends Controller
{
    // ── Main settings page
    public function index()
    {
        $businessUnits = BusinessUnits::withCount('companies')->orderBy('business_units_name')->get();
        $companies = Companies::with('businessUnit')->withCount('departments')->orderBy('company_name')->get();
        $departments = Departments::with('company.businessUnit')->withCount('users')->orderBy('department_name')->get();

        return view('admin.organization', compact('businessUnits', 'companies', 'departments'));
    }

    // ══════════════════════════════════════
    // ── BUSINESS UNITS
    // ══════════════════════════════════════

    public function storeBU(Request $request)
    {
        $request->validate([
            'business_units_name' => 'required|string|max:255|unique:business_units,business_units_name',
        ]);

        BusinessUnits::create([
            'business_units_name' => $request->business_units_name,
        ]);

        return back()->with('success', "Business Unit '{$request->business_units_name}' created successfully.");
    }

    public function showBU(BusinessUnits $businessUnit)
    {
        return response()->json($businessUnit);
    }

    public function updateBU(Request $request, BusinessUnits $businessUnit)
    {
        $request->validate([
            'business_units_name' => 'required|string|max:255|unique:business_units,business_units_name,' . $businessUnit->id,
        ]);

        $businessUnit->update([
            'business_units_name' => $request->business_units_name,
        ]);

        return back()->with('success', "Business Unit updated successfully.");
    }

    public function destroyBU(BusinessUnits $businessUnit)
    {
        if ($businessUnit->companies()->count() > 0) {
            return back()->with('error', "Cannot delete '{$businessUnit->business_units_name}' — it has companies assigned to it.");
        }

        $businessUnit->delete();
        return back()->with('success', "Business Unit deleted successfully.");
    }

    // ══════════════════════════════════════
    // ── COMPANIES
    // ══════════════════════════════════════

    public function storeCompany(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'business_units_id' => 'required|exists:business_units,id',
        ]);

        Companies::create([
            'company_name' => $request->company_name,
            'business_units_id' => $request->business_units_id,
        ]);

        return back()->with('success', "Company '{$request->company_name}' created successfully.");
    }

    public function showCompany(Companies $company)
    {
        return response()->json($company->load('businessUnit'));
    }

    public function updateCompany(Request $request, Companies $company)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'business_units_id' => 'required|exists:business_units,id',
        ]);

        $company->update([
            'company_name' => $request->company_name,
            'business_units_id' => $request->business_units_id,
        ]);

        return back()->with('success', "Company updated successfully.");
    }

    public function destroyCompany(Companies $company)
    {
        if ($company->departments()->count() > 0) {
            return back()->with('error', "Cannot delete '{$company->company_name}' — it has departments assigned to it.");
        }

        $company->delete();
        return back()->with('success', "Company deleted successfully.");
    }

    // ══════════════════════════════════════
    // ── DEPARTMENTS
    // ══════════════════════════════════════

    public function storeDept(Request $request)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
            'companies_id' => 'required|exists:companies,id',
        ]);

        Departments::create([
            'department_name' => $request->department_name,
            'companies_id' => $request->companies_id,
        ]);

        return back()->with('success', "Department '{$request->department_name}' created successfully.");
    }

    public function showDept(Departments $department)
    {
        return response()->json($department->load('company.businessUnit'));
    }

    public function updateDept(Request $request, Departments $department)
    {
        $request->validate([
            'department_name' => 'required|string|max:255',
            'companies_id' => 'required|exists:companies,id',
        ]);

        $department->update([
            'department_name' => $request->department_name,
            'companies_id' => $request->companies_id,
        ]);

        return back()->with('success', "Department updated successfully.");
    }

    public function destroyDept(Departments $department)
    {
        if ($department->users()->count() > 0) {
            return back()->with('error', "Cannot delete '{$department->department_name}' — it has users assigned to it.");
        }

        $department->delete();
        return back()->with('success', "Department deleted successfully.");
    }
}
