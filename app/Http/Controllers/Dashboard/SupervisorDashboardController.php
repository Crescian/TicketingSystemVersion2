<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Tickets;
use App\Models\User;
use App\Models\SlaRule;
use App\Models\SlaCategory;
use App\Models\TicketStatusHistories;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupervisorDashboardController extends Controller
{
    // Support Supervisor Openning
    public function supportIndex(Request $request)
    {
        $status = $request->filled('status')
            ? $request->get('status')
            : 'awaiting-classification';

        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo']);

        switch ($status) {
            case 'awaiting-classification':
                $query->whereIn('status', ['Awaiting Supervisor', 'Escalated'])
                    ->whereNotNull('date_acknowledged')
                    ->whereNull('assigned_to');
                break;
            case 'in-progress':
                $query->where('status', 'In Progress');
                break;
            case 'escalated':
                $query->where('status', 'Escalated');
                break;
            case 'pending-supervisor-approval':
                $query->where('status', 'Pending Supervisor Approval');
                break;
            case 'closed':
                $query->where('status', 'Closed');
                break;
            default:
                // 'all' falls through, no filter
                break;
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('status', 'ilike', "%{$search}%")
                    ->orWhereHas(
                        'user',
                        fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%")
                    );
            });
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'priority':
                $query->orderByRaw("
                    CASE
                        WHEN ticket_type = 'High' THEN 1
                        WHEN ticket_type = 'Medium' THEN 2
                        WHEN ticket_type = 'Low' THEN 3
                        ELSE 4
                    END
                ");
                break;
            default: // newest
                $query->orderBy('created_at', 'desc');
                break;
        }

        $tickets = $query->paginate(10)->withQueryString();

        $counts = [
            'awaiting_classification' => Tickets::where('status', 'Awaiting Supervisor')->whereNotNull('date_acknowledged')->whereNull('assigned_to')->count(),
            'in_progress' => Tickets::where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('status', 'Escalated')->count(),
            'pending_supervisor_approval' => Tickets::where('status', 'Pending Supervisor Approval')->count(),
            'pending_closure' => Tickets::where('status', 'Pending Closure')->count(),
            'closed' => Tickets::where('status', 'Closed')->count(),
        ];

        $technicians = User::whereHas(
            'role',
            fn($q) =>
            $q->where('role_name', 'IT Support Specialist')
        )
            ->withCount([
                'assignedTickets as active_tickets' => fn($q) =>
                    $q->whereIn('status', ['In Progress', 'Escalated'])
            ])
            ->get()
            ->map(function ($tech) {
                $tech->availability = match (true) {
                    $tech->active_tickets === 0 => 'free',
                    $tech->active_tickets <= 2 => 'busy',
                    default => 'full'
                };
                return $tech;
            });

        $slaCategories = SlaCategory::with([
            'rules' => function ($q) {
                $q->where('is_active', true)
                    ->select('id', 'sla_category_id', 'subcategory_name', 'priority', 'response_time_minutes', 'resolution_time_minutes')
                    ->orderBy('subcategory_name');
            }
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $slaCategoriesJson = $slaCategories->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'subs' => $c->rules->map(fn($r) => [
                'rule_id' => $r->id,
                'name' => $r->subcategory_name,
                'priority' => $r->priority,
                'response' => $r->response_time_minutes,
                'resolution' => $r->resolution_time_minutes,
            ])->values()->toArray(),
        ])->values()->toArray();

        return view('dashboard.support.dashboard', compact(
            'tickets',
            'counts',
            'technicians',
            'status',
            'search',
            'sort',
            'slaCategoriesJson'
        ));
    }

    public function supportAcknowledge(Tickets $ticket)
    {
        $alreadyAcknowledged = TicketStatusHistories::where('ticket_id', $ticket->id)
            ->where('notes', 'like', 'Acknowledged by Supervisor%')
            ->exists();

        if ($ticket->status !== 'Awaiting Supervisor' || $alreadyAcknowledged) {
            return back()->with('error', 'This ticket is not awaiting acknowledgment.');
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Awaiting Supervisor',
            'new_status' => 'Awaiting Classification', // keep status unchanged
            'changed_by' => Auth::id(),
            'notes' => 'Acknowledged by Supervisor - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} acknowledged."
        );
    }
    public function classifyAndAssign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'sla_rule_id' => 'required|exists:sla_rules,id',
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($ticket->status !== 'Awaiting Supervisor' || !$ticket->date_acknowledged) {
            return back()->with('error', 'Ticket must be acknowledged before it can be classified and assigned.');
        }

        $slaRule = SlaRule::findOrFail($request->sla_rule_id);
        $technician = User::findOrFail($request->technician_id);

        $oldStatus = $ticket->status;

        $ticket->update([
            'sla_category_id' => $slaRule->sla_category_id,
            'subcategory_name' => $slaRule->subcategory_name,
            'ticket_type' => $slaRule->priority,
            'assigned_to' => $technician->id,
            'assigned_at' => now(),
            'status' => 'Awaiting Support Specialist Acknowledgement',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Awaiting Classification',
            'new_status' => 'Awaiting Support Specialist Acknowledgement',
            'changed_by' => Auth::id(),
            'notes' => "Classified as {$slaRule->subcategory_name} ({$slaRule->priority}) and assigned to {$technician->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} classified and assigned to {$technician->name}.");
    }

    public function reassign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldTech = $ticket->assignedTo?->name ?? 'Unassigned';
        $newTech = User::findOrFail($request->technician_id);

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'assigned_at' => now(),
            'status' => 'In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Reassignment',
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Reassigned from {$oldTech} to {$newTech->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket reassigned to {$newTech->name}.");
    }

    public function takeover(Tickets $ticket)
    {
        if ($ticket->status !== 'Escalated') {
            return back()->with('error', 'Only escalated tickets can be taken over.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => Auth::id(),
            'assigned_at' => now(),
            'status' => 'In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => 'Supervisor took over the ticket directly - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "You have taken over ticket #{$ticket->ticket_number}.");
    }

    public function validateResolution(Request $request, Tickets $ticket)
    {
        $request->validate([
            'validation_notes' => 'required|string|max:1000',
        ]);

        if ($ticket->status !== 'Pending Supervisor Approval') {
            return back()->with('error', 'Only tickets pending closure can be validated.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'validated_at' => now(),
            'validation_notes' => $request->validation_notes,
        ]);

        $ticket->update([
            'status' => 'Pending Closure',
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Pending Closure',
            'changed_by' => Auth::id(),
            'notes' => 'Resolution validated by Supervisor - ' . Auth::user()->name . ". {$request->validation_notes}",
            'changed_at' => now(),
        ]);

        return back()->with('success', "Resolution for ticket #{$ticket->ticket_number} validated. Ready for Helpdesk closure & notification.");
    }

    public function escalateToAdmin(Tickets $ticket)
    {
        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Awaiting Admin Supervisor',
            'escalation_level' => $ticket->escalation_level + 1,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Admin Supervisor',
            'changed_by' => Auth::id(),
            'notes' => 'Escalated to Supervisor - IT Admin by ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} escalated to Supervisor - IT Admin.");
    }
    // Support Supervisor Closing

    // Admin Supervisor Openning
    public function index(Request $request)
    {
        $status = $request->filled('status')
            ? $request->get('status')
            : 'awaiting-admin-supervisor';

        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo'])
            ->orderByRaw("CASE
            WHEN status = 'Awaiting Admin Supervisor' THEN 1
            WHEN status = 'Awaiting Admin Classification' THEN 2
            WHEN status = 'Awaiting Administrator Acknowledgement' THEN 3
            WHEN status = 'Awaiting Administrator SLA Start' THEN 4
            WHEN status = 'Admin In Progress' THEN 5
            WHEN status = 'Pending Admin Supervisor Approval' THEN 6
            WHEN status = 'Closed' THEN 7
            WHEN status = 'Cancelled' THEN 8
            ELSE 8 END");

        if ($status !== 'all') {
            $mappedStatus = match ($status) {
                'awaiting-admin-supervisor' => 'Awaiting Admin Supervisor',
                'awaiting-admin-classification' => 'Awaiting Admin Classification',
                'awaiting-administrator-ack' => 'Awaiting Administrator Acknowledgement',
                'awaiting-administrator-sla-start' => 'Awaiting Administrator SLA Start',
                'admin-in-progress' => 'Admin In Progress',
                'pending-admin-supervisor-approval' => 'Pending Admin Supervisor Approval',
                'closed' => 'Closed',
                'cancelled' => 'Cancelled',
                default => null
            };

            if ($mappedStatus === 'Awaiting Admin Supervisor') {
                $query->where('status', 'Awaiting Admin Supervisor')->whereNull('assigned_to');
            } elseif ($mappedStatus) {
                $query->where('status', $mappedStatus);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('status', 'ilike', "%{$search}%")
                    ->orWhereHas(
                        'user',
                        fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%")
                    );
            });
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'priority':
                $query->reorder()->orderByRaw("
                CASE
                    WHEN ticket_type = 'High' THEN 1
                    WHEN ticket_type = 'Medium' THEN 2
                    WHEN ticket_type = 'Low' THEN 3
                    ELSE 4
                END
            ");
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $tickets = $query->paginate(10)->withQueryString();

        $counts = [
            'awaiting_admin_supervisor' => Tickets::where('status', 'Awaiting Admin Supervisor')->whereNull('assigned_to')->count(),
            'awaiting_admin_classification' => Tickets::where('status', 'Awaiting Admin Classification')->count(),
            'awaiting_administrator_ack' => Tickets::where('status', 'Awaiting Administrator Acknowledgement')->count(),
            'awaiting_administrator_sla_start' => Tickets::where('status', 'Awaiting Administrator SLA Start')->count(),
            'admin_in_progress' => Tickets::where('status', 'Admin In Progress')->count(),
            'pending_admin_supervisor_approval' => Tickets::where('status', 'Pending Admin Supervisor Approval')->count(),
            'closed' => Tickets::where('status', 'Closed')->count(),
            'cancelled' => Tickets::where('status', 'Cancelled')->count(),
        ];

        $technicians = User::whereHas(
            'role',
            fn($q) =>
            $q->where('role_name', 'IT Admin')
        )
            ->withCount([
                'assignedTickets as active_tickets' => fn($q) =>
                    $q->whereIn('status', ['Admin In Progress', 'Awaiting Administrator SLA Start'])
            ])
            ->get()
            ->map(function ($tech) {
                $tech->availability = match (true) {
                    $tech->active_tickets === 0 => 'free',
                    $tech->active_tickets <= 2 => 'busy',
                    default => 'full'
                };
                return $tech;
            });

        $slaCategories = SlaCategory::with([
            'rules' => function ($q) {
                $q->where('is_active', true)
                    ->select('id', 'sla_category_id', 'subcategory_name', 'priority', 'response_time_minutes', 'resolution_time_minutes')
                    ->orderBy('subcategory_name');
            }
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $slaCategoriesJson = $slaCategories->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'icon' => $c->icon,
            'color' => $c->color,
            'subs' => $c->rules->map(fn($r) => [
                'rule_id' => $r->id,
                'name' => $r->subcategory_name,
                'priority' => $r->priority,
                'response' => $r->response_time_minutes,
                'resolution' => $r->resolution_time_minutes,
            ])->values()->toArray(),
        ])->values()->toArray();

        $users = User::select(
            'users.id',
            'users.name',
            'users.position',
            'departments.department_name',
            'companies.company_name',
            'business_units.business_units_name'
        )
            ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
            ->leftJoin('companies', 'companies.id', '=', 'departments.companies_id')
            ->leftJoin('business_units', 'business_units.id', '=', 'companies.business_units_id')
            ->orderBy('users.name')
            ->get();

        return view('dashboard.admin.dashboard', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'technicians',
            'slaCategories',
            'slaCategoriesJson',
            'users'
        ));
    }

    public function adminAcknowledge(Tickets $ticket)
    {
        $alreadyAcknowledged = TicketStatusHistories::where('ticket_id', $ticket->id)
            ->where('notes', 'like', 'Acknowledged by Admin Supervisor%')
            ->exists();

        if ($ticket->status !== 'Awaiting Admin Supervisor' || $alreadyAcknowledged) {
            return back()->with('error', 'This ticket is not awaiting admin supervisor acknowledgment.');
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Awaiting Admin Supervisor',
            'new_status' => 'Awaiting Admin Classification',
            'changed_by' => Auth::id(),
            'notes' => 'Acknowledged by Admin Supervisor - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        $ticket->update(['status' => 'Awaiting Admin Classification']);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} acknowledged."
        );
    }

    public function adminClassifyAndAssign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'sla_rule_id' => 'required|exists:sla_rules,id',
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($ticket->status !== 'Awaiting Admin Classification') {
            return back()->with('error', 'Ticket must be acknowledged before it can be classified and assigned.');
        }

        $slaRule = SlaRule::findOrFail($request->sla_rule_id);
        $technician = User::findOrFail($request->technician_id);

        $ticket->update([
            'sla_category_id' => $slaRule->sla_category_id,
            'subcategory_name' => $slaRule->subcategory_name,
            'ticket_type' => $slaRule->priority,
            'assigned_to' => $technician->id,
            'assigned_at' => now(),
            'status' => 'Awaiting Administrator Acknowledgement',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Awaiting Admin Classification',
            'new_status' => 'Awaiting Administrator Acknowledgement',
            'changed_by' => Auth::id(),
            'notes' => "Classified as {$slaRule->subcategory_name} ({$slaRule->priority}) and assigned to {$technician->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} classified and assigned to {$technician->name}.");
    }

    public function adminReassign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldTech = $ticket->assignedTo?->name ?? 'Unassigned';
        $newTech = User::findOrFail($request->technician_id);

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'assigned_at' => now(),
            'status' => 'Admin In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Reassignment',
            'new_status' => 'Admin In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Reassigned from {$oldTech} to {$newTech->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket reassigned to {$newTech->name}.");
    }

    public function adminTakeover(Tickets $ticket)
    {
        if (!in_array($ticket->status, ['Awaiting Administrator SLA Start', 'Awaiting Administrator Acknowledgement'])) {
            return back()->with('error', 'Only tickets awaiting SLA start or acknowledgment can be taken over/started.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => Auth::id(),
            'assigned_at' => now(),
            'status' => 'Admin In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Admin In Progress',
            'changed_by' => Auth::id(),
            'notes' => 'Admin Supervisor started/took over the ticket directly - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Work started on ticket #{$ticket->ticket_number}.");
    }

    public function adminValidateResolution(Request $request, Tickets $ticket)
    {
        $request->validate([
            'validation_notes' => 'required|string|max:1000',
        ]);

        if ($ticket->status !== 'Pending Admin Supervisor Approval') {
            return back()->with('error', 'Only tickets currently in progress can be validated.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Pending Closure',
            'validated_at' => now(),
            'validation_notes' => $request->validation_notes,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Pending Closure',
            'changed_by' => Auth::id(),
            'notes' => 'Resolution validated and closed by Admin Supervisor - ' . Auth::user()->name . ". {$request->validation_notes}",
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} resolved and closed.");
    }

    // Awaiting Administrator Acknowledgement -> Awaiting Administrator SLA Start
    public function adminAcknowledgeAssignment(Tickets $ticket)
    {
        if ($ticket->status !== 'Awaiting Administrator Acknowledgement') {
            return back()->with('error', 'This ticket is not awaiting administrator acknowledgment.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Awaiting Administrator SLA Start',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Administrator SLA Start',
            'changed_by' => Auth::id(),
            'notes' => 'Acknowledged by IT Admin - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} acknowledged. Ready to start work.");
    }

    // Awaiting Administrator SLA Start -> Admin In Progress
    public function adminStartSla(Tickets $ticket)
    {
        if ($ticket->status !== 'Awaiting Administrator SLA Start') {
            return back()->with('error', 'This ticket is not awaiting SLA start.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Admin In Progress',
            // Optional: add a nullable `sla_started_at` timestamp column via migration
            // and set it here if you want SLA timing to count from this moment
            // rather than from ticket creation. Not required for the transition itself.
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Admin In Progress',
            'changed_by' => Auth::id(),
            'notes' => 'Work started, SLA clock started - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "SLA clock started for ticket #{$ticket->ticket_number}.");
    }
    // Admin Supervisor Closing
}