<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkloadClass;
use Illuminate\Http\Request;

class WorkloadClassController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);

        WorkloadClass::create($data + ['sort_order' => WorkloadClass::max('sort_order') + 1]);

        return back()->with('success', "Workload class '{$data['name']}' created.");
    }

    public function update(Request $request, WorkloadClass $workloadClass)
    {
        $workloadClass->update($this->validated($request, $workloadClass));

        return back()->with('success', "Workload class '{$workloadClass->name}' updated.");
    }

    public function destroy(WorkloadClass $workloadClass)
    {
        $name = $workloadClass->name;
        $workloadClass->delete();

        return back()->with('success', "Workload class '{$name}' deleted.");
    }

    public function toggle(WorkloadClass $workloadClass)
    {
        $workloadClass->update(['is_active' => !$workloadClass->is_active]);

        return back()->with('success', 'Workload class ' . ($workloadClass->is_active ? 'activated' : 'deactivated') . '.');
    }

    private function validated(Request $request, ?WorkloadClass $existing = null): array
    {
        $requiresManual = $request->boolean('requires_manual_resolution');

        $data = $request->validate([
            'name' => 'required|string|max:255|unique:workload_classes,name' . ($existing ? ',' . $existing->id : ''),
            'typical_application' => 'nullable|string|max:1000',
            'response_minutes' => 'required|integer|min:1|max:43200',
            'resolution_minutes' => $requiresManual ? 'nullable|integer|min:1|max:43200' : 'required|integer|min:1|max:43200',
            'response_label' => 'required|string|max:255',
            'resolution_label' => 'required|string|max:255',
            'requires_manual_resolution' => 'nullable|boolean',
        ]);

        $data['requires_manual_resolution'] = $requiresManual;
        if ($requiresManual) {
            $data['resolution_minutes'] = null;
        }

        return $data;
    }
}
