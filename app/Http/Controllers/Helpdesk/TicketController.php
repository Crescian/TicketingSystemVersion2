<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketResolvedMail;
use App\Models\Tickets;
use App\Models\SlaCategory;
use App\Models\SlaRule;
use App\Models\TicketStatusHistories;
use App\Models\User;
use App\Models\WorkloadClass;
use App\Services\TicketScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->filled('status')
            ? $request->get('status')
            : 'new-request';
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy'])
            ->orderByRaw("CASE
                WHEN status = 'New Request'        THEN 1
                WHEN status = 'Awaiting Supervisor' THEN 2
                WHEN status = 'In Progress'   THEN 3
                WHEN status = 'Escalated'    THEN 4
                WHEN status = 'Pending Closure'    THEN 5
                WHEN status = 'Awaiting Requestor'    THEN 6
                WHEN status = 'Closed'    THEN 7
                ELSE 8 END")
            ->orderByDesc('created_at');

        // 'active' = everything still open, before it lands on Closed
        $activeStatuses = [
            'New Request',
            'L1 In Progress',
            'Awaiting Supervisor',
            'In Progress',
            'Escalated',
            'Pending Closure',
            'Awaiting Requestor',
        ];

        if ($status === 'active') {
            $query->whereIn('status', $activeStatuses);
        } elseif ($status !== 'all') {
            $mappedStatus = match ($status) {
                'new-request' => 'New Request',
                'l1-in-progress' => 'L1 In Progress',
                'awaiting-supervisor' => 'Awaiting Supervisor',
                'in-progress' => 'In Progress',
                'escalated' => 'Escalated',
                'pending-closure' => 'Pending Closure',
                'awaiting-requestor' => 'Awaiting Requestor',
                'closed' => 'Closed',
                'cancelled' => 'Cancelled',
                default => null
            };
            if ($mappedStatus === 'New Request') {
                $query->where('status', 'New Request')->whereNull('assigned_to');
            } elseif ($mappedStatus) {
                $query->where('status', $mappedStatus);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('status', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        match ($sort) {
            'oldest' => $query->reorder()->orderBy('created_at', 'asc'),
            'priority' => $query->reorder()->orderByRaw("CASE
                WHEN ticket_type = 'Critical' THEN 1
                WHEN ticket_type = 'High'     THEN 2
                WHEN ticket_type = 'Medium'   THEN 3
                WHEN ticket_type = 'Low'      THEN 4
                ELSE 5 END"),
            default => null
        };

        $tickets = $query->paginate(10)->withQueryString();

        // Counts
        $counts = [
            'new_request' => Tickets::where('status', 'New Request')->whereNull('assigned_to')->count(),
            'l1_in_progress' => Tickets::where('status', 'L1 In Progress')->count(),
            'awaiting_supervisor' => Tickets::where('status', 'Awaiting Supervisor')->count(),
            'in_progress' => Tickets::where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('status', 'Escalated')->count(),
            'pending_closure' => Tickets::where('status', 'Pending Closure')->count(),
            'awaiting_requestor' => Tickets::where('status', 'Awaiting Requestor')->count(),
            'closed' => Tickets::where('status', 'Closed')->count(),
            'cancelled' => Tickets::where('status', 'Cancelled')->count(),
        ];
        $counts['active'] = Tickets::whereIn('status', $activeStatuses)->count();

        // Technicians with real time-slot capacity (App\Services\TicketScheduler)
        $technicians = User::whereHas('role', fn($q) =>
            $q->where('role_name', 'IT Support Specialist'))
            ->get()
            ->map(function ($tech) {
                $status = TicketScheduler::statusFor($tech);
                $tech->schedule_status = $status;
                $tech->availability = match ($status) {
                    'available' => 'free',
                    'busy' => 'busy',
                    'overtime', 'on_leave' => 'full',
                };
                $tech->free_minutes_today = TicketScheduler::freeMinutesToday($tech);
                $tech->free_time_label = TicketScheduler::freeTimeLabel($tech);
                return $tech;
            });


        // ── Load SLA categories with their active rules for the ticket modal +
        // the Acknowledge & Classify modal (needs rule_id/response/resolution to
        // submit a classification, not just tag a category)
        $slaCategories = \App\Models\SlaCategory::with([
            'rules' => function ($q) {
                $q->where('is_active', true)
                    ->select('id', 'sla_category_id', 'subcategory_name', 'priority', 'response_time_minutes', 'resolution_time_minutes', 'helpdesk_resolvable')
                    ->orderBy('subcategory_name');
            }
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Add this RIGHT AFTER $slaCategories is built, before the return statement
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
                'helpdesk_resolvable' => $r->helpdesk_resolvable,
            ])->values()->toArray(),
        ])->values()->toArray();

        $users = \App\Models\User::select(
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

        // Workload class presets — picking one in the classify form overrides the
        // subcategory's default response/resolution minutes for this ticket only.
        $workloadClasses = \App\Models\WorkloadClass::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $workloadClassesJson = $workloadClasses->map(fn($w) => [
            'id' => $w->id,
            'name' => $w->name,
            'response_minutes' => $w->response_minutes,
            'resolution_minutes' => $w->resolution_minutes,
            'requires_manual_resolution' => $w->requires_manual_resolution,
        ])->values()->toArray();

        return view('dashboard.helpdesk', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'technicians',
            'slaCategories',
            'slaCategoriesJson',
            'users',
            'workloadClasses',
            'workloadClassesJson'
        ));
    }

    // Acknowledge ticket (New Request stays New Request — just stamps when Helpdesk
    // received it). Mirrors SupervisorDashboardController::supportAcknowledge(): no
    // status change, no classification. Classification is a separate step — see
    // classify() below — so the acknowledgment timestamp isn't held hostage by
    // however long triage takes.
    public function acknowledge(Tickets $ticket)
    {
        if ($ticket->status !== 'New Request') {
            return back()->with('error', 'Only New Request tickets can be acknowledged.');
        }

        $ticket->update([
            'date_acknowledged' => now()->toDateString(),   // YYYY-MM-DD
            'time_acknowledged' => now()->toTimeString(),   // HH:MM:SS
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => Auth::id(),
            'notes' => 'Acknowledged by Helpdesk - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} acknowledged.");
    }

    // Start L1 investigation (optional) — makes "Helpdesk is actively working this /
    // talking to the employee" visible in the queue. Not required before classify().
    public function startL1(Tickets $ticket)
    {
        if ($ticket->status !== 'New Request' || is_null($ticket->date_acknowledged)) {
            return back()->with('error', 'Only acknowledged New Request tickets can start L1.');
        }

        $oldStatus = $ticket->status;

        $ticket->update(['status' => 'L1 In Progress']);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'L1 In Progress',
            'changed_by' => Auth::id(),
            'notes' => 'L1 investigation started by Helpdesk - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} moved to L1 In Progress.");
    }

    // Classify (New Request or L1 In Progress → Awaiting Supervisor). Requires the
    // ticket to already be acknowledged. The Supervisor's Classify & Assign step
    // still owns the final call and can freely override any of this — see
    // SupervisorDashboardController::classifyAndAssign().
    public function classify(Request $request, Tickets $ticket)
    {
        if (is_null($ticket->date_acknowledged) || !in_array($ticket->status, ['New Request', 'L1 In Progress'])) {
            return back()->with('error', 'Ticket must be acknowledged before it can be classified.');
        }

        $request->validate([
            'sla_rule_id' => 'required|exists:sla_rules,id',
            'notes' => 'nullable|string|max:500',
            'priority' => 'nullable|in:Critical,High,Medium,Low',
            'workload_class_id' => 'nullable|exists:workload_classes,id',
            'response_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'resolution_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'handle_myself' => 'nullable|boolean',
        ]);

        $slaRule = SlaRule::findOrFail($request->sla_rule_id);
        $workloadClass = $request->filled('workload_class_id')
            ? WorkloadClass::find($request->workload_class_id)
            : null;

        if ($workloadClass && $workloadClass->requires_manual_resolution && !$request->filled('resolution_time_minutes')) {
            return back()->with('error', "The \"{$workloadClass->name}\" workload class has no fixed resolution target — enter the agreed resolution time in minutes.");
        }

        // ── L1 self-resolve: only for subcategories IT Admin explicitly marked
        // helpdesk_resolvable — checked server-side, not just hidden in the UI, since
        // trusting a client-submitted flag here would let anyone bypass the gate.
        $handleMyself = $request->boolean('handle_myself');
        if ($handleMyself && !$slaRule->helpdesk_resolvable) {
            return back()->with('error', "\"{$slaRule->subcategory_name}\" isn't marked as Helpdesk-resolvable — send it to the Supervisor for assignment instead.");
        }

        $priority = $request->priority ?: $slaRule->priority;
        $responseTime = $request->filled('response_time_minutes')
            ? (int) $request->response_time_minutes
            : ($workloadClass->response_minutes ?? $slaRule->response_time_minutes);
        $resolutionTime = $request->filled('resolution_time_minutes')
            ? (int) $request->resolution_time_minutes
            : ($workloadClass->resolution_minutes ?? $slaRule->resolution_time_minutes);

        if ($responseTime >= $resolutionTime) {
            return back()->with('error', 'Response time must be less than resolution time.');
        }

        $oldStatus = $ticket->status;
        $newStatus = $handleMyself ? 'In Progress' : 'Awaiting Supervisor';

        $ticket->update([
            'status' => $newStatus,
            'sla_category_id' => $slaRule->sla_category_id,
            'subcategory_name' => $slaRule->subcategory_name,
            'workload_class_id' => $workloadClass?->id,
            'ticket_type' => $priority,
            'response_time_minutes' => $responseTime,
            'resolution_time_minutes' => $resolutionTime,
            'assigned_to' => $handleMyself ? Auth::id() : $ticket->assigned_to,
            'assigned_at' => $handleMyself ? now() : $ticket->assigned_at,
            'started_at' => $handleMyself ? now() : $ticket->started_at,
        ]);

        $overrideNote = ($priority !== $slaRule->priority
                || $responseTime != $slaRule->response_time_minutes
                || $resolutionTime != $slaRule->resolution_time_minutes)
            ? " (SLA customized by Helpdesk: {$priority} priority, {$responseTime}m response / {$resolutionTime}m resolution"
                . ($workloadClass ? ", workload class: {$workloadClass->name}" : '') . '.)'
            : '';

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => Auth::id(),
            'notes' => "Classified by Helpdesk - " . Auth::user()->name
                . ". Classified as {$slaRule->subcategory_name} ({$priority})."
                . $overrideNote
                . ($handleMyself ? ' Kept by Helpdesk for direct (L1) resolution.' : '')
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        if ($handleMyself) {
            return back()->with('success', "Ticket #{$ticket->ticket_number} classified as {$slaRule->subcategory_name} and kept for you to resolve.");
        }

        $supervisors = User::withActiveRole('Supervisor - Support Specialist')->get();
        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->send(
                new TicketAssignedMail($ticket, 'A ticket has been acknowledged and classified by Helpdesk and is ready for review & assignment.', 'supervisor.support.dashboard')
            );
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} classified as {$slaRule->subcategory_name}.");
    }

    // Cancel (New Request or L1 In Progress → Cancelled) — for duplicates, spam,
    // or non-issues Helpdesk can identify before classification. Requires a reason,
    // same accountability trail as decline()/escalate() elsewhere in this app.
    public function cancel(Request $request, Tickets $ticket)
    {
        if (!in_array($ticket->status, ['New Request', 'L1 In Progress'])) {
            return back()->with('error', 'Only unclassified tickets can be cancelled by Helpdesk.');
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update(['status' => 'Cancelled']);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Cancelled',
            'changed_by' => Auth::id(),
            'notes' => 'Cancelled by Helpdesk - ' . Auth::user()->name . ". Reason: {$request->reason}",
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} cancelled.");
    }
    public function closenotify(Tickets $ticket)
    {
        // Only allow Pending Closure
        if ($ticket->status !== 'Pending Closure') {
            return back()->with('error', 'Only Pending Closure tickets can be closed.');
        }

        $oldStatus = $ticket->status;

        // Update ticket
        $ticket->update([
            'status' => 'Awaiting Requestor',
            'closed_at' => now(), // optional if you have this column
        ]);

        // Log history (FIXED)
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Requestor',
            'changed_by' => Auth::id(),
            'notes' => 'Ticket closed by helpdesk and notification sent by ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        // Send email to requester (employee) — resolved, awaiting their confirmation
        if ($ticket->user && $ticket->user->email) {
            $ticket->loadMissing('assignedTo');
            Mail::to($ticket->user->email)->send(new TicketResolvedMail($ticket));
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} closed and user notified.");
    }
    // Assign technician
    // public function assign(Request $request, Tickets $ticket)
    // {
    //     $request->validate([
    //         'technician_id' => 'required|uuid|exists:users,id',
    //         'notes' => 'nullable|string|max:500',
    //     ]);

    //     $oldStatus = $ticket->status;
    //     $tech = User::findOrFail($request->technician_id);

    //     $ticket->update([
    //         'assigned_to' => $request->technician_id,
    //         'status' => 'In Progress',
    //         'started_at' => now(),
    //     ]);

    //     TicketStatusHistories::create([
    //         'ticket_id' => $ticket->id,
    //         'old_status' => $oldStatus,
    //         'new_status' => 'In Progress',
    //         'changed_by' => Auth::id(),
    //         'notes' => "Assigned to {$tech->name} by Helpdesk."
    //             . ($request->notes ? " Note: {$request->notes}" : ''),
    //         'changed_at' => now(),
    //     ]);

    //     return back()->with(
    //         'success',
    //         "Ticket #{$ticket->ticket_number} assigned to {$tech->name}."
    //     );
    // }

    // Reassign technician
    public function reassign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldTech = $ticket->assignedTo?->name ?? 'New Request';
        $newTech = User::findOrFail($request->technician_id);

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'status' => 'In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Reassigned from {$oldTech} to {$newTech->name} by Helpdesk."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} reassigned to {$newTech->name}."
        );
    }

    // Escalate to IT Admin
    public function escalate(Request $request, Tickets $ticket)
    {
        $request->validate([
            'reason' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Escalated',
            'escalation_level' => $ticket->escalation_level + 1,
        ]);

        // Log escalation record
        DB::table('escalations')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'ticket_id' => $ticket->id,
            'escalation_level' => $ticket->escalation_level,
            'escalated_by' => Auth::id(),
            'previous_tech_id' => $ticket->assigned_to,
            'reassigned_to' => null,
            'reason' => $request->reason,
            'resolution_notes' => $request->notes,
            'escalated_at' => now(),
            'resolved_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Escalated',
            'changed_by' => Auth::id(),
            'notes' => "Escalated to IT Admin. Reason: {$request->reason}."
                . ($request->notes ? " Notes: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} escalated to IT Admin."
        );
    }

    // Mark as resolved — closes out a ticket Helpdesk classified and kept for
    // themselves via classify()'s "handle_myself" option (see there). Requires
    // classification to have already happened, unlike the old version of this
    // action — that's the whole point of the change: an L1 self-resolve now
    // always carries real category/priority/SLA data instead of none at all.
    // Lands on Pending Closure — the same status a Technician's resolution
    // reaches after Supervisor validation — so it flows into Helpdesk's own
    // closenotify() step next, same as any other resolution path.
    public function resolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'In Progress' || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only tickets you classified and kept for yourself can be resolved this way.');
        }

        $request->validate([
            'resolution_notes' => 'required|string',
            'service_type' => 'required|in:Onsite,Remote,Preventive',
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Pending Closure',
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
            'resolution_notes' => $request->resolution_notes,
            'service_type' => $request->service_type,
            'findings' => $request->findings,
            'recommendation' => $request->recommendation,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Pending Closure',
            'changed_by' => Auth::id(),
            'notes' => "Resolved directly by Helpdesk - " . Auth::user()->name . ". {$request->resolution_notes}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved. Ready to Close & Notify."
        );
    }
    // Add this static helper at the bottom of the class
    public static function formatMinsLeft(float $m): string
    {
        if ($m <= 0)
            return 'Overdue';
        if ($m < 60)
            return intval($m) . 'm left';
        $h = floor($m / 60);
        $min = intval($m % 60);
        return $h . 'h' . ($min > 0 ? ' ' . $min . 'm' : '') . ' left';
    }
}