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

        [$synced, $skipped] = $this->syncLinkedRules($workloadClass);

        $message = "Workload class '{$workloadClass->name}' updated.";
        if ($synced > 0) {
            $message .= " {$synced} linked SLA " . ($synced === 1 ? 'rule' : 'rules') . ' updated.';
        }
        if ($skipped > 0) {
            $message .= " {$skipped} linked " . ($skipped === 1 ? 'rule was' : 'rules were')
                . ' left unchanged because the new response time would not be less than its resolution time.';
        }

        return back()->with('success', $message);
    }

    // Pushes the class's response/resolution minutes onto every SLA rule that uses it
    // as its default, so the subcategory catalog never drifts from the workload matrix.
    // Manual-resolution classes (no fixed resolution) only push the response time —
    // each rule keeps its own resolution — and rules where that would leave
    // response >= resolution are skipped rather than saved in an invalid state.
    private function syncLinkedRules(WorkloadClass $workloadClass): array
    {
        $response = $workloadClass->response_minutes;
        $resolution = $workloadClass->resolution_minutes;

        $linked = $workloadClass->slaRules();

        if ($resolution !== null) {
            $total = (clone $linked)->count();
            $synced = $response < $resolution
                ? $linked->update(['response_time_minutes' => $response, 'resolution_time_minutes' => $resolution])
                : 0;

            return [$synced, $total - $synced];
        }

        $total = (clone $linked)->count();
        $synced = $linked->where('resolution_time_minutes', '>', $response)
            ->update(['response_time_minutes' => $response]);

        return [$synced, $total - $synced];
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
