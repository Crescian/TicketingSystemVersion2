<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tickets;
use Illuminate\Http\Request;

// Read-only ticket export for analysis (SLA compliance, workload/category
// distribution, resolution-time patterns) — gated behind the 'tickets:read'
// Sanctum token ability (see IssueTicketsApiToken), separate from 'org:read'.
// Deliberately excludes free-text fields (concern, resolution_notes, findings,
// recommendation) — those carry the most sensitive content and aren't needed
// for aggregate analysis; expose them later as a dedicated endpoint if a real
// use case needs them.
class TicketsController extends Controller
{
    public function index(Request $request)
    {
        $paginator = Tickets::with(['slaCategory', 'assignedTo', 'user.department.company.businessUnit'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('ticket_type'), fn ($q) => $q->where('ticket_type', $request->ticket_type))
            ->when($request->filled('sla_category_id'), fn ($q) => $q->where('sla_category_id', $request->sla_category_id))
            ->when($request->filled('assigned_to'), fn ($q) => $q->where('assigned_to', $request->assigned_to))
            ->when($request->filled('department_id'), fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('department_id', $request->department_id)))
            ->when($request->filled('company_id'), fn ($q) => $q->whereHas('user.department', fn ($q2) => $q2->where('companies_id', $request->company_id)))
            ->when($request->filled('business_unit_id'), fn ($q) => $q->whereHas('user.department.company', fn ($q2) => $q2->where('business_units_id', $request->business_unit_id)))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->orderByDesc('created_at')
            ->paginate(min((int) $request->input('per_page', 50), 200));

        $paginator->getCollection()->transform(fn (Tickets $t) => [
            'id' => $t->id,
            'ticket_number' => $t->ticket_number,
            'status' => $t->status,
            'priority' => $t->ticket_type,
            'category' => $t->slaCategory?->name,
            'subcategory' => $t->subcategory_name,
            'assigned_to' => $t->assigned_to,
            'assigned_technician' => $t->assignedTo?->name,
            'requester_department' => $t->user?->department?->department_name,
            'requester_company' => $t->user?->department?->company?->company_name,
            'requester_business_unit' => $t->user?->department?->company?->businessUnit?->business_units_name,
            'created_at' => $t->created_at,
            'started_at' => $t->started_at,
            'resolved_at' => $t->resolved_at,
            'sla_due_at' => $t->sla_due_at,
            'response_time_minutes' => $t->response_time_minutes,
            'resolution_time_minutes' => $t->resolution_time_minutes,
            'actual_resolution_minutes' => ($t->started_at && $t->resolved_at)
                ? max(0, $t->started_at->diffInMinutes($t->resolved_at, true) - $t->total_hold_minutes)
                : null,
            'sla_met' => ($t->resolved_at && $t->sla_due_at)
                ? $t->resolved_at->lte($t->sla_due_at)
                : null,
            'is_overtime' => $t->is_overtime,
            'escalation_level' => $t->escalation_level,
            'cannot_resolve' => $t->cannot_resolve,
        ]);

        return response()->json($paginator);
    }
}
