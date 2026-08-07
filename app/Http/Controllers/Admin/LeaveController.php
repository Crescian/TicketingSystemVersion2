<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function index()
    {
        $leaves = Leave::with('technician')->orderBy('date')->get();
        $technicians = User::whereHas('role', fn ($q) => $q->where('role_name', 'IT Support Specialist'))
            ->orderBy('name')
            ->get();

        return view('admin.leaves', compact('leaves', 'technicians'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $leave = Leave::create($data);

        return back()->with('success', "Leave added for {$leave->technician->name}.");
    }

    public function update(Request $request, Leave $leave)
    {
        $leave->update($this->validated($request, $leave));

        return back()->with('success', "Leave updated for {$leave->technician->name}.");
    }

    public function destroy(Leave $leave)
    {
        $name = $leave->technician->name;
        $leave->delete();

        return back()->with('success', "Leave deleted for {$name}.");
    }

    public function toggle(Leave $leave)
    {
        $leave->update(['is_active' => !$leave->is_active]);

        return back()->with('success', 'Leave ' . ($leave->is_active ? 'activated' : 'deactivated') . '.');
    }

    private function validated(Request $request, ?Leave $existing = null): array
    {
        return $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            // Uniqueness is scoped to (technician_id, date) — a bare unique on `date`
            // would wrongly forbid two different technicians taking leave the same day.
            'date' => [
                'required',
                'date',
                Rule::unique('leaves', 'date')
                    ->where(fn ($q) => $q->where('technician_id', $request->input('technician_id')))
                    ->ignore($existing?->id),
            ],
            'reason' => 'nullable|string|max:255',
        ]);
    }
}
