<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SlaCategory;
use App\Models\SlaRule;
use App\Models\Tickets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SlaRuleController extends Controller
{
    public function index()
    {
        $categories = SlaCategory::with(['rules'])
            ->orderBy('sort_order')->orderBy('name')->get();

        $start        = now()->startOfMonth();
        $totalTickets = Tickets::where('created_at', '>=', $start)->count();
        $breachedAll  = DB::table('escalations')->where('escalated_at', '>=', $start)->count();
        $slaRate      = $totalTickets > 0 ? round((($totalTickets - $breachedAll) / $totalTickets) * 100) : 100;

        $priorityCounts = [
            'High'   => Tickets::where('ticket_type', 'High')->whereNotIn('status', ['Resolved','Cancelled'])->count(),
            'Medium' => Tickets::where('ticket_type', 'Medium')->whereNotIn('status', ['Resolved','Cancelled'])->count(),
            'Low'    => Tickets::where('ticket_type', 'Low')->whereNotIn('status', ['Resolved','Cancelled'])->count(),
        ];

        return view('admin.sla-rules', compact(
            'categories', 'slaRate', 'totalTickets', 'breachedAll', 'priorityCounts'
        ));
    }

    // ── Categories
    public function storeCategory(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255|unique:sla_categories,name',
            'icon'  => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);
        SlaCategory::create([
            'name'       => $request->name,
            'icon'       => $request->icon  ?: 'bi-tag',
            'color'      => $request->color ?: '#1a4a8a',
            'is_active'  => true,
            'sort_order' => SlaCategory::max('sort_order') + 1,
        ]);
        return back()->with('success', "Category '{$request->name}' created.");
    }

    public function updateCategory(Request $request, SlaCategory $slaCategory)
    {
        $request->validate([
            'name'  => 'required|string|max:255|unique:sla_categories,name,' . $slaCategory->id,
            'icon'  => 'nullable|string|max:100',
            'color' => 'nullable|string|max:20',
        ]);
        $slaCategory->update([
            'name'  => $request->name,
            'icon'  => $request->icon  ?: $slaCategory->icon,
            'color' => $request->color ?: $slaCategory->color,
        ]);
        return back()->with('success', "Category updated.");
    }

    public function destroyCategory(SlaCategory $slaCategory)
    {
        $name = $slaCategory->name;
        $slaCategory->delete();
        return back()->with('success', "Category '{$name}' and all its rules deleted.");
    }

    // ── SLA Rules
    public function storeRule(Request $request)
    {
        $request->validate([
            'sla_category_id'        => 'required|exists:sla_categories,id',
            'subcategory_name'        => 'required|string|max:255',
            'priority'                => 'required|in:High,Medium,Low',
            'response_time_minutes'   => 'required|numeric|min:5|max:43200',
            'resolution_time_minutes' => 'required|numeric|min:5|max:43200',
            'description'             => 'nullable|string|max:500',
        ]);
        if ($request->response_time_minutes >= $request->resolution_time_minutes) {
            return back()->with('error', 'Response time must be less than resolution time.');
        }
        SlaRule::create($request->only([
            'sla_category_id', 'subcategory_name', 'priority',
            'response_time_minutes', 'resolution_time_minutes', 'description'
        ]) + ['is_active' => true]);
        return back()->with('success', "SLA rule for '{$request->subcategory_name}' created.");
    }

    public function showRule(SlaRule $slaRule)
    {
        return response()->json($slaRule);
    }

    public function updateRule(Request $request, SlaRule $slaRule)
    {
        $request->validate([
            'sla_category_id'        => 'required|exists:sla_categories,id',
            'subcategory_name'        => 'required|string|max:255',
            'priority'                => 'required|in:High,Medium,Low',
            'response_time_minutes'   => 'required|numeric|min:5|max:43200',
            'resolution_time_minutes' => 'required|numeric|min:5|max:43200',
            'description'             => 'nullable|string|max:500',
        ]);
        if ($request->response_time_minutes >= $request->resolution_time_minutes) {
            return back()->with('error', 'Response time must be less than resolution time.');
        }
        $slaRule->update($request->only([
            'sla_category_id', 'subcategory_name', 'priority',
            'response_time_minutes', 'resolution_time_minutes', 'description'
        ]));
        return back()->with('success', "SLA rule updated.");
    }

    public function destroyRule(SlaRule $slaRule)
    {
        $name = $slaRule->subcategory_name;
        $slaRule->delete();
        return back()->with('success', "SLA rule for '{$name}' deleted.");
    }

    public function toggleRule(SlaRule $slaRule)
    {
        $slaRule->update(['is_active' => !$slaRule->is_active]);
        return back()->with('success', "Rule " . ($slaRule->is_active ? 'activated' : 'deactivated') . ".");
    }
}