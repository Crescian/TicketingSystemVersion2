<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Mail\TicketAssignedMail;
use App\Models\Tickets;
use App\Models\SlaCategory;
use App\Models\SlaRule;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistories;
use App\Models\User;
use App\Models\WorkloadClass;
use App\Services\TicketScheduler;
use App\Support\TicketReportProgress;
use App\Support\TicketStatus;
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
                WHEN status = 'For Acknowledgment' AND pending_role = 'Helpdesk' THEN 1
                WHEN status = 'For Acknowledgment' AND pending_role = 'Supervisor - Support Specialist' THEN 2
                WHEN status = 'In Progress Service Request' AND assigned_to IS NOT NULL THEN 3
                WHEN status = 'In Progress Service Report' THEN 5
                WHEN status = 'Escalated'    THEN 6
                WHEN status = 'Report For Review'    THEN 7
                WHEN status = 'Requestor Confirmation'    THEN 8
                WHEN status = 'Closed'    THEN 9
                ELSE 10 END")
            ->orderByDesc('created_at');

        if ($status === 'active') {
            $query = $this->scopeActive($query);
        } elseif ($status === 'in-progress') {
            // In Progress Service Request, regardless of which level/track is
            // actually working it (Helpdesk L1 self-resolve, Technician,
            // IT Admin, Manager) — no role scoping, Helpdesk wants visibility
            // across every tier here, not just its own L1 track. Drafting the
            // report (In Progress Service Report) still gets its own tab below.
            $query->whereIn('status', [
                TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
                TicketStatus::CLOSED_SERVICE_REQUEST,
            ]);
        } elseif ($status === 'l1-preparing-report') {
            $query->where('status', TicketStatus::IN_PROGRESS_SERVICE_REPORT)
                ->whereHas('assignedTo.role', fn($q) => $q->where('role_name', 'Helpdesk'));
        } elseif ($status !== 'all') {
            switch ($status) {
                case 'new-request':
                    $query->where('status', TicketStatus::FOR_ACKNOWLEDGMENT)
                        ->where('pending_role', TicketStatus::QUEUE_HELPDESK)
                        ->whereNull('assigned_to');
                    break;
                case 'for-classification':
                    // Acknowledged by Helpdesk, not yet classified — see
                    // acknowledge() below.
                    $query->where('status', TicketStatus::FOR_CLASSIFICATION)
                        ->where('pending_role', TicketStatus::QUEUE_HELPDESK);
                    break;
                case 'awaiting-supervisor':
                    // Helpdesk's classify() lands these straight on Classified now
                    // (routing to the Supervisor ready for assignment, not a fresh
                    // acknowledgment) — see classify() below.
                    $query->where('status', TicketStatus::CLASSIFIED)
                        ->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR);
                    break;
                case 'escalated':
                    $query->where('status', TicketStatus::ESCALATED);
                    break;
                case 'pending-supervisor-approval':
                    $query->where('status', TicketStatus::REPORT_FOR_REVIEW)
                        ->whereHas('assignedTo.role', fn($q) => $q->where('role_name', 'Helpdesk'));
                    break;
                case 'awaiting-requestor':
                    $query->where('status', TicketStatus::REQUESTOR_CONFIRMATION);
                    break;
                case 'closed':
                    $query->where('status', TicketStatus::CLOSED);
                    break;
                case 'cancelled':
                    $query->where('status', TicketStatus::CANCELLED);
                    break;
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
            'new_request' => Tickets::where('status', TicketStatus::FOR_ACKNOWLEDGMENT)
                ->where('pending_role', TicketStatus::QUEUE_HELPDESK)
                ->count(),
            'for_classification' => Tickets::where('status', TicketStatus::FOR_CLASSIFICATION)
                ->where('pending_role', TicketStatus::QUEUE_HELPDESK)
                ->count(),
            'awaiting_supervisor' => Tickets::where('status', TicketStatus::CLASSIFIED)
                ->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR)
                ->count(),
            'in_progress' => Tickets::whereIn('status', [
                    TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
                    TicketStatus::CLOSED_SERVICE_REQUEST,
                ])->count(),
            'l1_preparing_report' => Tickets::where('status', TicketStatus::IN_PROGRESS_SERVICE_REPORT)
                ->whereHas('assignedTo.role', fn($q) => $q->where('role_name', 'Helpdesk'))
                ->count(),
            'escalated' => Tickets::where('status', TicketStatus::ESCALATED)->count(),
            'pending_supervisor_approval' => Tickets::where('status', TicketStatus::REPORT_FOR_REVIEW)
                ->whereHas('assignedTo.role', fn($q) => $q->where('role_name', 'Helpdesk'))
                ->count(),
            'awaiting_requestor' => Tickets::where('status', TicketStatus::REQUESTOR_CONFIRMATION)->count(),
            'closed' => Tickets::where('status', TicketStatus::CLOSED)->count(),
            'cancelled' => Tickets::where('status', TicketStatus::CANCELLED)->count(),
        ];
        $counts['active'] = $this->scopeActive(Tickets::query())->count();

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

        // IT Admins + their Supervisors — no real time-slot scheduling on this
        // track yet (admin-side assignment never calls TicketScheduler), so this
        // is the same open-ticket-count gauge IT Admin's own dashboard already
        // uses for its peer roster, not a TicketScheduler free-time label.
        $itAdmins = User::whereHas('role', fn($q) =>
            $q->whereIn('role_name', ['IT Admin', 'Supervisor - IT Admin', 'Supervisor - Support Specialist']))
            ->with('role')
            ->withCount([
                'assignedTickets as active_tickets' => fn($q) =>
                    $q->whereIn('status', [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::ASSIGNED, TicketStatus::IN_PROGRESS_SERVICE_REPORT])
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($admin) {
                $admin->availability = match (true) {
                    $admin->active_tickets === 0 => 'free',
                    $admin->active_tickets <= 2 => 'busy',
                    default => 'full'
                };
                return $admin;
            });

        // ── Load SLA categories with their active rules for the ticket modal +
        // the Acknowledge & Classify modal (needs rule_id/response/resolution to
        // submit a classification, not just tag a category)
        $slaCategories = \App\Models\SlaCategory::with([
            'rules' => function ($q) {
                $q->where('is_active', true)
                    ->select('id', 'sla_category_id', 'subcategory_name', 'priority', 'response_time_minutes', 'resolution_time_minutes', 'helpdesk_resolvable', 'admin_only', 'description')
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
                'admin_only' => $r->admin_only,
                'description' => $r->description,
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
            'itAdmins',
            'slaCategories',
            'slaCategoriesJson',
            'users',
            'workloadClasses',
            'workloadClassesJson'
        ));
    }

    // Full-page support request details for Helpdesk — the Helpdesk queue is a
    // shared inbox across every agent (see index() above), not "assigned to me
    // only", so unlike Admin\TicketController::show() there's no assigned_to
    // ownership gate here — the role middleware is the boundary.
    public function show(Tickets $ticket)
    {
        $ticket->load(['user.department', 'assignedTo', 'statusHistories.changedBy', 'feedback', 'attachments.uploader', 'slaCategory']);

        return view('helpdesk.ticket-detail', compact('ticket'));
    }

    // "Active" = everything still open, before it lands on Closed. In Progress
    // Service Request / In Progress Service Report are shared across every
    // track now (see App\Support\TicketStatus), so they're scoped here to just
    // Helpdesk's own world (L1 self-resolve, still-unassigned L1 investigation,
    // or a Technician's own in-progress work) — otherwise Admin/Manager-track
    // active tickets would leak into Helpdesk's active count, which the old
    // per-track status strings never allowed.
    private function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->where(function ($q1) {
                $q1->where('status', TicketStatus::FOR_ACKNOWLEDGMENT)
                    ->whereIn('pending_role', [TicketStatus::QUEUE_HELPDESK, TicketStatus::QUEUE_SUPPORT_SUPERVISOR]);
            })->orWhere(function ($q1a) {
                $q1a->where('status', TicketStatus::FOR_CLASSIFICATION)
                    ->where('pending_role', TicketStatus::QUEUE_HELPDESK);
            })->orWhere(function ($q1b) {
                // Helpdesk's classify() lands a ticket straight on Classified when
                // routing to the Supervisor — still Helpdesk's ticket to keep an eye
                // on until the Supervisor acts.
                $q1b->where('status', TicketStatus::CLASSIFIED)
                    ->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR);
            })->orWhereIn('status', [
                TicketStatus::CLOSED_SERVICE_REQUEST,
                TicketStatus::ESCALATED,
                TicketStatus::DONE_SERVICE_REPORT,
                TicketStatus::REPORT_FOR_REVIEW,
                TicketStatus::REQUESTOR_CONFIRMATION,
            ])->orWhere(function ($q2) {
                $q2->whereIn('status', [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT])
                    ->whereHas('assignedTo.role', fn($r) => $r->whereIn('role_name', ['Helpdesk', 'IT Support Specialist']));
            });
        });
    }

    // Acknowledge ticket — moves status to For Classification (Helpdesk-only,
    // see App\Support\TicketStatus) so the dashboard shows it's been seen and
    // now needs triaging, distinct from a still-untouched fresh ticket.
    public function acknowledge(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::FOR_ACKNOWLEDGMENT || $ticket->pending_role !== TicketStatus::QUEUE_HELPDESK) {
            return back()->with('error', 'Only new tickets can be acknowledged.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            // Plain strings, not Carbon-cast — computed as Asia/Manila wall-clock
            // values directly, since app.timezone is UTC and these would otherwise
            // store (and later display) UTC time mislabeled as local time.
            'date_acknowledged' => now()->timezone('Asia/Manila')->toDateString(),   // YYYY-MM-DD
            'time_acknowledged' => now()->timezone('Asia/Manila')->toTimeString(),   // HH:MM:SS
            'status' => TicketStatus::FOR_CLASSIFICATION,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::FOR_CLASSIFICATION,
            'changed_by' => Auth::id(),
            'notes' => 'Acknowledged by Helpdesk - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} acknowledged.");
    }

    // Classify (For Classification → routed to a Supervisor queue, or straight
    // through to In Progress Service Request on L1 self-resolve). Requires the
    // ticket to already be acknowledged. The Supervisor's Classify & Assign step
    // still owns the final call and can freely override any of this — see
    // SupervisorDashboardController::classifyAndAssign().
    public function classify(Request $request, Tickets $ticket)
    {
        $isReadyToClassify = $ticket->status === TicketStatus::FOR_CLASSIFICATION && $ticket->pending_role === TicketStatus::QUEUE_HELPDESK;

        if (is_null($ticket->date_acknowledged) || !$isReadyToClassify) {
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

        // ── L3-only subcategories skip the Support Supervisor queue entirely and go
        // straight to Supervisor - IT Admin — set on the sla_rule by IT Admin (see
        // SlaRule::admin_only), not a per-ticket Helpdesk choice.
        $routedToAdmin = !$handleMyself && $slaRule->admin_only;
        $oldStatus = $ticket->status;

        $classificationAttributes = [
            'sla_category_id' => $slaRule->sla_category_id,
            'subcategory_name' => $slaRule->subcategory_name,
            'workload_class_id' => $workloadClass?->id,
            'ticket_type' => $priority,
            'response_time_minutes' => $responseTime,
            'resolution_time_minutes' => $resolutionTime,
        ];

        $overrideNote = ($priority !== $slaRule->priority
                || $responseTime != $slaRule->response_time_minutes
                || $resolutionTime != $slaRule->resolution_time_minutes)
            ? " (SLA customized by Helpdesk: {$priority} priority, {$responseTime}m response / {$resolutionTime}m resolution"
                . ($workloadClass ? ", workload class: {$workloadClass->name}" : '') . '.)'
            : '';

        if ($handleMyself) {
            // Classify + self-assign + start, all in this one click — cascade
            // through Classified/Assigned so the audit trail still shows every
            // standard step even though the UI collapses them into one action.
            $ticket->update($classificationAttributes + ['status' => TicketStatus::CLASSIFIED, 'pending_role' => null]);
            TicketStatusHistories::create([
                'ticket_id' => $ticket->id, 'old_status' => $oldStatus, 'new_status' => TicketStatus::CLASSIFIED,
                'changed_by' => Auth::id(),
                'notes' => "Classified by Helpdesk - " . Auth::user()->name . ". Classified as {$slaRule->subcategory_name} ({$priority})." . $overrideNote,
                'changed_at' => now(),
            ]);

            $ticket->update(['status' => TicketStatus::ASSIGNED, 'assigned_to' => Auth::id(), 'assigned_at' => now()]);
            TicketStatusHistories::create([
                'ticket_id' => $ticket->id, 'old_status' => TicketStatus::CLASSIFIED, 'new_status' => TicketStatus::ASSIGNED,
                'changed_by' => Auth::id(), 'notes' => 'Kept by Helpdesk for direct (L1) resolution.', 'changed_at' => now(),
            ]);

            $ticket->update(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'started_at' => now()]);
            TicketStatusHistories::create([
                'ticket_id' => $ticket->id, 'old_status' => TicketStatus::ASSIGNED, 'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
                'changed_by' => Auth::id(), 'notes' => 'Started by Helpdesk - ' . Auth::user()->name . '.'
                    . ($request->notes ? " Note: {$request->notes}" : ''),
                'changed_at' => now(),
            ]);

            return back()->with('success', "Ticket #{$ticket->ticket_number} classified as {$slaRule->subcategory_name} and kept for you to resolve.");
        }

        // Not self-handling — this classification is authoritative (not a proposal
        // the Supervisor has to redo): the ticket moves straight to Classified and
        // lands in the appropriate Supervisor's queue ready for assignment. The
        // Supervisor can still override the SLA rule from there if needed, but
        // doesn't have to re-acknowledge or re-classify from scratch.
        $pendingRole = $routedToAdmin ? TicketStatus::QUEUE_ADMIN_SUPERVISOR : TicketStatus::QUEUE_SUPPORT_SUPERVISOR;

        $ticket->update($classificationAttributes + ['status' => TicketStatus::CLASSIFIED, 'pending_role' => $pendingRole]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CLASSIFIED,
            'changed_by' => Auth::id(),
            'notes' => "Classified by Helpdesk - " . Auth::user()->name
                . ". Classified as {$slaRule->subcategory_name} ({$priority})."
                . $overrideNote
                . ($routedToAdmin ? ' Routed directly to Supervisor - IT Admin (L3-only subcategory).' : '')
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        if ($routedToAdmin) {
            $adminSupervisors = User::withActiveRole('Supervisor - IT Admin')->get();
            foreach ($adminSupervisors as $adminSupervisor) {
                Mail::to($adminSupervisor->email)->send(
                    new TicketAssignedMail($ticket, 'A ticket has been acknowledged and classified by Helpdesk as L3-only and is ready for review & assignment.', 'supervisor.dashboard')
                );
            }

            return back()->with('success', "Ticket #{$ticket->ticket_number} classified as {$slaRule->subcategory_name} and sent directly to Supervisor - IT Admin.");
        }

        $supervisors = User::withActiveRole('Supervisor - Support Specialist')->get();
        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->send(
                new TicketAssignedMail($ticket, 'A ticket has been acknowledged and classified by Helpdesk and is ready for review & assignment.', 'supervisor.support.dashboard')
            );
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} classified as {$slaRule->subcategory_name}.");
    }

    // Cancel (For Acknowledgment or For Classification [Helpdesk queue] →
    // Cancelled) — for duplicates, spam, or non-issues Helpdesk can identify
    // before classification. Requires a reason, same accountability trail as
    // decline()/escalate() elsewhere in this app.
    public function cancel(Request $request, Tickets $ticket)
    {
        $isUnclassified = in_array($ticket->status, [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::FOR_CLASSIFICATION], true)
            && $ticket->pending_role === TicketStatus::QUEUE_HELPDESK;

        if (!$isUnclassified) {
            return back()->with('error', 'Only unclassified tickets can be cancelled by Helpdesk.');
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update(['status' => TicketStatus::CANCELLED, 'pending_role' => null]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CANCELLED,
            'changed_by' => Auth::id(),
            'notes' => 'Cancelled by Helpdesk - ' . Auth::user()->name . ". Reason: {$request->reason}",
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} cancelled.");
    }

    // Reassign technician
    public function reassign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldTech = $ticket->assignedTo?->name ?? 'Unassigned';
        $newTech = User::findOrFail($request->technician_id);
        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'pending_role' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
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
            'status' => TicketStatus::ESCALATED,
            'pending_role' => TicketStatus::QUEUE_ADMIN_SUPERVISOR,
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
            'new_status' => TicketStatus::ESCALATED,
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

    // Add update / progress note (while resolving or drafting the report —
    // opening the Resolve modal shouldn't lock Helpdesk out of logging more
    // progress if they end up not submitting it) — mirrors
    // Technician\TicketController::update() so an L1 self-resolve (via
    // classify()'s "handle_myself" option) has the same "log as you go" flow as
    // a Technician's support request. old_status === new_status is exactly how
    // these entries are marked, which is what lets the Resolve form later draft
    // its Service Details from them.
    public function update(Request $request, Tickets $ticket)
    {
        if (!in_array($ticket->status, [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT], true) || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only tickets you classified and kept for yourself can receive progress updates.');
        }

        $request->validate([
            'progress_notes' => 'required|string',
            'work_status' => 'required|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $storedPath = $file->store('ticket-attachments/' . $ticket->id, 'local');

            TicketAttachment::create([
                'ticket_id'     => $ticket->id,
                'uploaded_by'   => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'stored_path'   => $storedPath,
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => Auth::id(),
            'notes' => "[{$request->work_status}] " . $request->progress_notes,
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Progress update logged for #{$ticket->ticket_number}."
        );
    }

    // The technical fix is done, separate from writing it up — isolates "still
    // fixing it" from "preparing the service report" as two deliberate actions
    // instead of one combined submit (see TicketReportProgress). Resolve only
    // becomes available after this.
    public function startReport(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REQUEST || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only tickets you classified and kept for yourself can be marked fixed.');
        }

        TicketReportProgress::markStarted($ticket);

        return back()->with('success', "Ticket #{$ticket->ticket_number} marked fixed — prepare the service report when ready.");
    }

    // Mark as resolved — closes out a ticket Helpdesk classified and kept for
    // themselves via classify()'s "handle_myself" option (see there). Requires
    // classification to have already happened, unlike the old version of this
    // action — that's the whole point of the change: an L1 self-resolve now
    // always carries real category/priority/SLA data instead of none at all.
    // Only reachable after startReport() (In Progress Service Report) — the fix
    // itself has to already be marked done. Cascades to Report For Review — same
    // as a Technician's resolution — so an L1 self-resolve gets the same
    // Supervisor sign-off via SupervisorDashboardController::validateResolution().
    public function resolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REPORT || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Mark the ticket fixed before preparing the service report.');
        }

        $request->validate([
            'resolution_notes' => 'required|string',
            'service_type' => 'required|in:Onsite,Remote,Preventive',
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => TicketStatus::DONE_SERVICE_REPORT,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
            'resolution_notes' => $request->resolution_notes,
            'service_type' => $request->service_type,
            'findings' => $request->findings,
            'recommendation' => $request->recommendation,
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $storedPath = $file->store('ticket-attachments/' . $ticket->id, 'local');

            TicketAttachment::create([
                'ticket_id'     => $ticket->id,
                'uploaded_by'   => Auth::id(),
                'original_name' => $file->getClientOriginalName(),
                'stored_path'   => $storedPath,
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::DONE_SERVICE_REPORT,
            'changed_by' => Auth::id(),
            'notes' => "Resolved directly by Helpdesk - " . Auth::user()->name . ". {$request->resolution_notes}",
            'changed_at' => now(),
        ]);

        $ticket->update(['status' => TicketStatus::REPORT_FOR_REVIEW]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::DONE_SERVICE_REPORT,
            'new_status' => TicketStatus::REPORT_FOR_REVIEW,
            'changed_by' => Auth::id(),
            'notes' => 'Sent to Supervisor - Support Specialist for approval.',
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved. Sent to your Supervisor for approval."
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
