<?php

namespace App\Http\Resources\Mobile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// The signed-in employee, as the mobile app needs it: identity, org placement,
// and the account-setup flags that decide whether the app opens the setup
// screens (org info + password) before anything else.
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $department = $this->department;
        $company = $department?->company;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'position' => $this->position,
            'role' => $this->role?->role_name,
            'department' => $department ? ['id' => $department->id, 'name' => $department->department_name] : null,
            'company' => $company ? ['id' => $company->id, 'name' => $company->company_name] : null,
            'business_unit' => $company?->businessUnit
                ? ['id' => $company->businessUnit->id, 'name' => $company->businessUnit->business_units_name]
                : null,
            'signs_in_with_microsoft' => !is_null($this->microsoft_id),
            'needs_account_setup' => (bool) $this->needs_account_setup,
            'must_change_password' => (bool) $this->must_change_password,
            // Employees get one lifetime self-update of department/position;
            // staff roles can always update theirs (as on the web).
            'can_update_org_info' => $this->role?->role_name !== 'Employee' || is_null($this->org_info_updated_at),
            'onboarded' => !is_null($this->onboarded_at),
        ];
    }
}
