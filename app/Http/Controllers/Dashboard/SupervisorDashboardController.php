<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\TicketAssignedMail;
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

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy']);

        // 'active' = everything still in flight for this supervisor, before it lands on Closed
        $activeStatuses = ['Awaiting Supervisor', 'Escalated', 'In Progress', 'Pending Supervisor Approval'];

        switch ($status) {
            case 'active':
                $query->whereIn('status', $activeStatuses);
                break;
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
        $counts['active'] = Tickets::whereIn('status', $activeStatuses)->count();

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

        if ($technician->email) {
            Mail::to($technician->email)->send(
                new TicketAssignedMail($ticket, 'This ticket has been assigned to you and needs your acknowledgement.', 'technician.dashboard')
            );
        }

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

    // Add update / progress note (only while In Progress)
    public function supportUpdate(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'In Progress') {
            return back()->with('error', 'Only in-progress tickets can receive progress updates.');
        }

        $request->validate([
            'progress_notes' => 'required|string',
            'work_status' => 'required|string',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "[{$request->work_status}] " . $request->progress_notes,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Progress update logged for #{$ticket->ticket_number}.");
    }

    // Mark resolved (only while In Progress)
    public function supportResolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'In Progress') {
            return back()->with('error', 'Only in-progress tickets can be marked resolved.');
        }

        $request->validate([
            'resolution_notes' => 'required|string',
            'time_spent' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Pending Closure',
            'resolved_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Pending Closure',
            'changed_by' => Auth::id(),
            'notes' => "Resolved by " . Auth::user()->name .
                ". Time spent: {$request->time_spent}. " .
                $request->resolution_notes,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} resolved. Sent for closure.");
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

    public function escalateToAdmin(Request $request, Tickets $ticket)
    {
        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Awaiting Admin Supervisor',
            'escalation_level' => $ticket->escalation_level + 1,
            'assigned_to' => null,
        ]);

        $notes = 'Escalated to Supervisor - IT Admin by ' . Auth::user()->name;
        if ($request->filled('reason')) {
            $notes .= '. Reason: ' . $request->reason;
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Admin Supervisor',
            'changed_by' => Auth::id(),
            'notes' => $notes,
            'changed_at' => now(),
        ]);

        $adminSupervisors = User::withActiveRole('Supervisor - IT Admin')->get();
        foreach ($adminSupervisors as $adminSupervisor) {
            Mail::to($adminSupervisor->email)->send(
                new TicketAssignedMail($ticket, 'A ticket has been escalated to your team and needs classification & assignment.', 'supervisor.dashboard')
            );
        }

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

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy'])
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

        // 'active' = everything still in flight across the admin escalation pipeline
        $activeStatuses = [
            'Awaiting Admin Supervisor',
            'Awaiting Admin Classification',
            'Awaiting Administrator Acknowledgement',
            'Awaiting Administrator SLA Start',
            'Admin In Progress',
            'Pending Admin Supervisor Approval',
        ];

        if ($status === 'active') {
            $query->whereIn('status', $activeStatuses);
        } elseif ($status !== 'all') {
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
        $counts['active'] = Tickets::whereIn('status', $activeStatuses)->count();

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

        if ($technician->email) {
            Mail::to($technician->email)->send(
                new TicketAssignedMail($ticket, 'This ticket has been assigned to you and needs your acknowledgement.', 'admin.dashboard')
            );
        }

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

    // Awaiting Administrator SLA Start -> Admin In Progress
    public function adminStartSla(Tickets $ticket)
    {
        if ($ticket->status !== 'Awaiting Administrator SLA Start') {
            return back()->with('error', 'This ticket is not awaiting SLA start.');
        }

        $oldStatus = $ticket->status;
        $startedAt = now();
        $slaRule = $ticket->activeSlaRule();

        $ticket->update([
            'status' => 'Admin In Progress',
            'started_at' => $startedAt, // SLA resolution clock starts here
            'sla_due_at' => $slaRule
                ? $startedAt->copy()->addMinutes($slaRule->resolution_time_minutes)
                : null,
            'sla_risk_notified_at' => null,
            'sla_breached_notified_at' => null,
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

    public function escalateToManager(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'Admin In Progress') {
            return back()->with('error', 'Only tickets you are actively working on can be escalated to the Manager.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Awaiting Manager',
            'assigned_to' => null,
            'escalation_level' => $ticket->escalation_level + 1,
        ]);

        $notes = 'Escalated to Manager by ' . Auth::user()->name;
        if ($request->filled('reason')) {
            $notes .= '. Reason: ' . $request->reason;
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Manager',
            'changed_by' => Auth::id(),
            'notes' => $notes,
            'changed_at' => now(),
        ]);

        $managers = User::withActiveRole('Manager')->get();
        foreach ($managers as $manager) {
            if ($manager->email) {
                Mail::to($manager->email)->send(
                    new TicketAssignedMail($ticket, 'A ticket has been escalated to you and needs acknowledgement.', 'executive.tickets.index')
                );
            }
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} escalated to Manager.");
    }
    // Admin Supervisor Closing
}