<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index()
    {
        $holidays = Holiday::orderBy('date')->get();

        return view('admin.holidays', compact('holidays'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Holiday::create($data);

        return back()->with('success', "Holiday '{$data['name']}' added.");
    }

    public function update(Request $request, Holiday $holiday)
    {
        $holiday->update($this->validated($request, $holiday));

        return back()->with('success', "Holiday '{$holiday->name}' updated.");
    }

    public function destroy(Holiday $holiday)
    {
        $name = $holiday->name;
        $holiday->delete();

        return back()->with('success', "Holiday '{$name}' deleted.");
    }

    public function toggle(Holiday $holiday)
    {
        $holiday->update(['is_active' => !$holiday->is_active]);

        return back()->with('success', 'Holiday ' . ($holiday->is_active ? 'activated' : 'deactivated') . '.');
    }

    private function validated(Request $request, ?Holiday $existing = null): array
    {
        return $request->validate([
            'date' => 'required|date|unique:holidays,date' . ($existing ? ',' . $existing->id : ''),
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:regular,special-non-working',
        ]);
    }
}
