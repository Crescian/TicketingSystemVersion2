<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TicketAssignedMail;
use App\Models\ReclassificationRequest;
use App\Models\SlaRule;
use App\Models\TicketAttachment;
use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use App\Models\User;
use App\Services\TicketScheduler;
use App\Support\BusinessClock;
use App\Support\TicketReportProgress;
use App\Support\TicketResolutionRules;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'active');
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'priority');

        // Escalating clears assigned_to (see escalate() below) — a ticket the admin
        // escalated no longer belongs to their "assigned to me" queue, so that tab
        // has to look it up via the escalations table instead, keyed by who raised
        // it rather than who currently holds it.
        $query = ($status === 'escalated')
            ? Tickets::whereHas('escalations', fn ($q) => $q->where('escalated_by', $user->id))
            : Tickets::where('assigned_to', $user->id);

        $query->with(['user.department', 'statusHistories.changedBy'])
            ->orderByRaw("CASE
                WHEN status = 'Assigned'                    THEN 1
                WHEN status = 'In Progress Service Request' THEN 2
                WHEN status = 'In Progress Service Report'  THEN 3
                WHEN status = 'Closed'                       THEN 4
                ELSE 5 END")
            ->orderByRaw("CASE
                WHEN ticket_type = 'Critical' THEN 1
                WHEN ticket_type = 'High'     THEN 2
                WHEN ticket_type = 'Medium'   THEN 3
                WHEN ticket_type = 'Low'      THEN 4
                ELSE 5 END");

        // "active" = everything still actionable by this admin before it's closed.
        // Already scoped to this admin's own assignments (see $query above), so
        // In Progress Service Report (shared across every resolver track, see
        // TicketReportProgress) isn't ambiguous here the way it is on dashboards
        // that mix multiple admins/tracks together.
        $activeStatuses = [
            TicketStatus::ASSIGNED,
            TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            TicketStatus::IN_PROGRESS_SERVICE_REPORT,
            TicketStatus::REPORT_FOR_REVIEW,
            TicketStatus::REQUESTOR_CONFIRMATION,
        ];

        if ($status === 'active') {
            $query->whereIn('status', $activeStatuses);
        } elseif ($status === 'in-progress') {
            $query->where('status', TicketStatus::IN_PROGRESS_SERVICE_REQUEST);
        } elseif ($status === 'in-progress-report') {
            $query->where('status', TicketStatus::IN_PROGRESS_SERVICE_REPORT);
        } elseif ($status === 'escalated') {
            $query->where('status', TicketStatus::ESCALATED);
        } elseif ($status === 'report-for-review') {
            $query->where('status', TicketStatus::REPORT_FOR_REVIEW);
        } elseif ($status === 'awaiting-requestor') {
            $query->where('status', TicketStatus::REQUESTOR_CONFIRMATION);
        } elseif ($status === 'awaiting-ack') {
            // Not yet acknowledged — same tech_acknowledged_at field/meaning as
            // the Technician dashboard, just reused for the Admin track's own
            // acknowledge() action.
            $query->where('status', TicketStatus::ASSIGNED)->whereNull('tech_acknowledged_at');
        } elseif ($status === 'ready-start') {
            // Acknowledged, not yet started — must be acknowledged first.
            $query->where('status', TicketStatus::ASSIGNED)->whereNotNull('tech_acknowledged_at');
        } else {
            $mappedStatus = match ($status) {
                'closed' => TicketStatus::CLOSED,
                default => null
            };
            if ($mappedStatus) {
                $query->where('status', $mappedStatus);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('request_category', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        match ($sort) {
            'newest' => $query->reorder()->orderByDesc('created_at'),
            'oldest' => $query->reorder()->orderBy('created_at'),
            'priority' => null, // already applied above as the default secondary sort
            default => null
        };

        $tickets = $query->paginate(10)->withQueryString();

        // Counts — scoped to this admin's own assignments. awaiting_ack and
        // ready_start both draw from the Assigned status, told apart by
        // tech_acknowledged_at — a ticket must be acknowledged before it can be
        // started, so these are mutually exclusive, not the same bucket twice.
        $counts = [
            'awaiting_ack' => Tickets::where('assigned_to', $user->id)
                ->where('status', TicketStatus::ASSIGNED)->whereNull('tech_acknowledged_at')->count(),
            'ready_start' => Tickets::where('assigned_to', $user->id)
                ->where('status', TicketStatus::ASSIGNED)->whereNotNull('tech_acknowledged_at')->count(),
            'in_progress' => Tickets::where('assigned_to', $user->id)
                ->where('status', TicketStatus::IN_PROGRESS_SERVICE_REQUEST)->count(),
            'in_progress_report' => Tickets::where('assigned_to', $user->id)
                ->where('status', TicketStatus::IN_PROGRESS_SERVICE_REPORT)->count(),
            'escalated' => Tickets::whereHas('escalations', fn ($q) => $q->where('escalated_by', $user->id))
                ->where('status', TicketStatus::ESCALATED)->count(),
            'report_for_review' => Tickets::where('assigned_to', $user->id)
                ->where('status', TicketStatus::REPORT_FOR_REVIEW)->count(),
            'awaiting_requestor' => Tickets::where('assigned_to', $user->id)
                ->where('status', TicketStatus::REQUESTOR_CONFIRMATION)->count(),
            'closed' => Tickets::where('assigned_to', $user->id)
                ->where('status', TicketStatus::CLOSED)->count(),
        ];
        // 'escalated' is deliberately excluded here — those tickets are unassigned
        // (see escalate() below), so they can never actually appear in this
        // assigned_to-scoped "Active" list; counting them in the sum would make
        // the Active badge show a number the tab itself could never produce.
        $counts['active'] = $counts['awaiting_ack'] + $counts['ready_start'] + $counts['in_progress'] + $counts['in_progress_report']
            + $counts['report_for_review'] + $counts['awaiting_requestor'];

        // Technicians (other IT Admins) — used for reassign
        $technicians = User::whereHas('role', fn($q) =>
            $q->where('role_name', 'IT Admin'))
            ->withCount([
                'assignedTickets as active_tickets' => fn($q) =>
                    $q->whereIn('status', [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::ASSIGNED, TicketStatus::IN_PROGRESS_SERVICE_REPORT])
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

        // System overview stats (kept from original, 'Resolved' → 'Closed')
        $systemStats = [
            'avg_resolution' => Tickets::where('status', TicketStatus::CLOSED)
                ->whereDate('resolved_at', today())
                ->whereNotNull('started_at')
                ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
                ->value('avg_hours'),
            'total_open' => Tickets::where(function ($q) {
                $q->whereIn('status', [
                    TicketStatus::ASSIGNED,
                    TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
                ])->orWhere(
                    fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::IN_PROGRESS_SERVICE_REPORT, ['IT Admin'])
                );
            })->count(),
            'avg_rating' => DB::table('ticket_feed_backs')
                ->whereDate('created_at', today())
                ->avg('rating'),
        ];

        $weekStart = now()->startOfWeek();
        $weekStats = [
            'resolved' => Tickets::where('assigned_to', $user->id)
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', $weekStart)
                ->count(),
            'avg_time' => Tickets::where('assigned_to', $user->id)
                ->whereNotNull('resolved_at')
                ->whereNotNull('started_at')
                ->where('resolved_at', '>=', $weekStart)
                ->selectRaw("AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600) as avg_hours")
                ->value('avg_hours'),
            'avg_rating' => DB::table('ticket_feed_backs')
                ->join('tickets', 'tickets.id', '=', 'ticket_feed_backs.ticket_id')
                ->where('tickets.assigned_to', $user->id)
                ->where('ticket_feed_backs.created_at', '>=', $weekStart)
                ->avg('ticket_feed_backs.rating'),
        ];

        // Needed for the Request Re-classification modal's category/subcategory
        // picker — same shape Technician\TicketController builds for its own
        // reclassify modal.
        $slaCategories = \App\Models\SlaCategory::with([
            'rules' => function ($q) {
                $q->where('is_active', true)
                    ->select('id', 'sla_category_id', 'subcategory_name', 'priority', 'response_time_minutes', 'resolution_time_minutes', 'description')
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
                'description' => $r->description,
            ])->values()->toArray(),
        ])->values()->toArray();

        return view('dashboard.admin', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'technicians',
            'systemStats',
            'weekStats',
            'slaCategoriesJson'
        ));
    }

    // -----------------------------
    // STEP 1: Acknowledge assignment
    // -----------------------------
    public function acknowledge(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if ($ticket->status !== TicketStatus::ASSIGNED) {
            return back()->with('error', 'Only newly assigned tickets can be acknowledged.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        // Acknowledging no longer moves status — Assigned already covers both
        // "just assigned" and "acknowledged, ready to start" (see TicketStatus).
        $ticket->update(['tech_acknowledged_at' => now()]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::ASSIGNED,
            'new_status' => TicketStatus::ASSIGNED,
            'changed_by' => Auth::id(),
            'notes' => "Assignment acknowledged by " . Auth::user()->name .
                ($request->notes ? ". Notes: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} acknowledged. Start the ticket when you're ready to begin work."
        );
    }

    // -----------------------------
    // STEP 2: Start ticket (SLA resolution clock begins)
    // -----------------------------
    public function start(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if ($ticket->status !== TicketStatus::ASSIGNED) {
            return back()->with('error', 'Ticket must be acknowledged before it can be started.');
        }

        // One active ticket at a time — same anchor-slot rule Technician::start()
        // enforces, and the same guard reassign()/takeover() already use here.
        if ($activeTicket = $this->activeTicketFor(Auth::user(), $ticket->id)) {
            return back()->with(
                'error',
                "Resolve or escalate ticket #{$activeTicket->ticket_number} before starting another ticket."
            );
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;
        $startedAt = now();
        $resolutionMinutes = $ticket->effectiveResolutionTimeMinutes();

        $ticket->update([
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'started_at' => $startedAt, // SLA resolution clock starts here
            'sla_due_at' => $resolutionMinutes
                ? BusinessClock::addBusinessMinutes($startedAt->copy(), $resolutionMinutes)
                : null,
            'sla_risk_notified_at' => null,
            'sla_breached_notified_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => "Ticket started by " . Auth::user()->name . "." .
                ($request->notes ? " Notes: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} started. SLA resolution timer is now running."
        );
    }

    // The technical fix is done, separate from writing it up — isolates "still
    // fixing it" from "preparing the service report" as two deliberate actions
    // instead of one combined submit (see TicketReportProgress). Resolve only
    // becomes available after this.
    public function startReport(Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REQUEST) {
            return back()->with('error', 'Only started tickets can be marked fixed.');
        }

        TicketReportProgress::markStarted($ticket);

        return back()->with('success', "Ticket #{$ticket->ticket_number} marked fixed — prepare the service report when ready.");
    }

    // -----------------------------
    // STEP 3: Submit the service report — stops at Report For Review (the
    // Admin Supervisor approves it from here via
    // SupervisorDashboardController::adminValidateResolution()).
    // -----------------------------
    // Only reachable after startReport() (In Progress Service Report) — the fix
    // itself has to already be marked done.
    public function resolve(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REPORT) {
            return back()->with('error', 'Mark the ticket fixed before preparing the service report.');
        }

        $request->validate(array_merge(TicketResolutionRules::BASE, [
            'root_cause' => 'required|string',
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]));

        $now = now();

        $ticket->update([
            'status' => TicketStatus::DONE_SERVICE_REPORT,
            'resolved_at' => $now,
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
            'old_status' => TicketStatus::IN_PROGRESS_SERVICE_REPORT,
            'new_status' => TicketStatus::DONE_SERVICE_REPORT,
            'changed_by' => Auth::id(),
            'notes' => "Resolved by IT Admin " . Auth::user()->name
                . ". Root cause: {$request->root_cause}."
                . " Resolution: {$request->resolution_notes}",
            'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::REPORT_FOR_REVIEW]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::DONE_SERVICE_REPORT,
            'new_status' => TicketStatus::REPORT_FOR_REVIEW,
            'changed_by' => Auth::id(),
            'notes' => 'Service report submitted — awaiting Admin Supervisor review.',
            'changed_at' => $now,
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved — service report sent for supervisor review."
        );
    }

    // Decline ticket → unassign, return to Admin Supervisor for reassignment
    // (the ticket keeps its classification — only the assignee is undone).
    public function decline(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if ($ticket->status !== TicketStatus::ASSIGNED) {
            return back()->with('error', 'Tickets already in progress cannot be declined — reassign instead.');
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => null,
            'status' => TicketStatus::CLASSIFIED,
            'pending_role' => TicketStatus::QUEUE_ADMIN_SUPERVISOR,
            'tech_acknowledged_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CLASSIFIED,
            'changed_by' => Auth::id(),
            'notes' => "Declined by " . Auth::user()->name .
                ". Reason: {$request->reason}. Returned to Admin Supervisor for reassignment.",
            'changed_at' => now(),
        ]);

        return redirect()
            ->route('admin.dashboard')
            ->with('success', "Ticket #{$ticket->ticket_number} declined and returned to Admin Supervisor.");
    }

    // Reassign to another IT Admin
    public function reassign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        $oldTechUser = $ticket->assignedTo;
        $oldTech = $oldTechUser?->name ?? 'Unassigned';
        $newTech = User::findOrFail($request->technician_id);

        if ($activeTicket = $this->activeTicketFor($newTech, $ticket->id)) {
            return back()->with(
                'error',
                "{$newTech->name} is already working on ticket #{$activeTicket->ticket_number} — they need to resolve or escalate it before taking on another."
            );
        }

        $slot = TicketScheduler::commitDirectSlot(
            $ticket,
            $newTech,
            TicketScheduler::effortMinutes($ticket),
            $request->input('schedule_decision', 'auto')
        );

        if ($slot['needs_decision']) {
            return back()->withInput()->with('scheduleConflict', [
                'action_url' => route('admin.tickets.reassign', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $newTech->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'assigned_at' => now(),
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'pending_role' => null,
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
        ]);

        // Ticket just left the outgoing admin's active slot and/or not-started
        // queue — their remaining queued tickets need to slide to match reality.
        if ($oldTechUser && $oldTechUser->id !== $newTech->id) {
            TicketScheduler::resequence($oldTechUser);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => "Reassigned by IT Admin from {$oldTech} to {$newTech->name}."
                . ($request->notes ? " Notes: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} reassigned to {$newTech->name}."
        );
    }

    // Take over ticket directly (e.g. picking up an unassigned queue ticket)
    public function takeover(Request $request, Tickets $ticket)
    {
        $request->validate([
            'reason' => 'required|string',
            'assessment' => 'nullable|string|max:500',
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        $admin = Auth::user();

        if ($activeTicket = $this->activeTicketFor($admin, $ticket->id)) {
            return back()->with(
                'error',
                "You're already working on ticket #{$activeTicket->ticket_number} — resolve or escalate it before taking over another."
            );
        }

        $slot = TicketScheduler::commitDirectSlot(
            $ticket,
            $admin,
            TicketScheduler::effortMinutes($ticket),
            $request->input('schedule_decision', 'auto')
        );

        if ($slot['needs_decision']) {
            return back()->withInput()->with('scheduleConflict', [
                'action_url' => route('admin.tickets.takeover', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $admin->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => Auth::id(),
            'assigned_at' => now(),
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'pending_role' => null,
            'started_at' => $ticket->started_at ?? now(),
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => "IT Admin " . Auth::user()->name . " took over directly."
                . " Reason: {$request->reason}."
                . ($request->assessment ? " Assessment: {$request->assessment}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "You have taken ownership of ticket #{$ticket->ticket_number}."
        );
    }

    // Escalate to Admin Supervisor (only while In Progress)
    public function escalate(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if (!in_array($ticket->status, [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT], true)) {
            return back()->with('error', 'Only started tickets can be escalated.');
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => TicketStatus::ESCALATED,
            'assigned_to' => null,
            'pending_role' => TicketStatus::QUEUE_ADMIN_SUPERVISOR,
            'escalation_level' => $ticket->escalation_level + 1,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::ESCALATED,
            'changed_by' => Auth::id(),
            'notes' => "Escalated to Supervisor by " . Auth::user()->name .
                ". Reason: {$request->reason}",
            'changed_at' => now(),
        ]);

        $adminSupervisors = User::withActiveRole('Supervisor - IT Admin')->get();
        foreach ($adminSupervisors as $adminSupervisor) {
            if ($adminSupervisor->email) {
                Mail::to($adminSupervisor->email)->send(
                    new TicketAssignedMail($ticket, 'A ticket has been escalated to your team and needs classification & assignment.', 'supervisor.dashboard')
                );
            }
        }

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} escalated to Admin Supervisor."
        );
    }

    // The IT Admin thinks this ticket was miscategorized — propose a corrected
    // classification for the Admin Supervisor to approve or reject. Mirrors
    // Technician\TicketController::requestReclassification() — the ticket itself
    // isn't touched until approved (see SupervisorDashboardController::
    // adminApproveReclassification()), it just keeps being worked as-is.
    public function requestReclassification(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if (!in_array($ticket->status, [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT], true)) {
            return back()->with('error', 'Only started tickets can request re-classification.');
        }

        $request->validate([
            'sla_rule_id' => 'required|exists:sla_rules,id',
            'reason' => 'required|string',
            'priority' => 'nullable|in:Critical,High,Medium,Low',
            'response_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'resolution_time_minutes' => 'nullable|numeric|min:5|max:43200',
        ]);

        $slaRule = SlaRule::findOrFail($request->sla_rule_id);

        $priority = $request->priority ?: $slaRule->priority;
        $responseTime = $request->filled('response_time_minutes')
            ? (int) $request->response_time_minutes
            : $slaRule->response_time_minutes;
        $resolutionTime = $request->filled('resolution_time_minutes')
            ? (int) $request->resolution_time_minutes
            : $slaRule->resolution_time_minutes;

        if ($responseTime >= $resolutionTime) {
            return back()->with('error', 'Response time must be less than resolution time.');
        }

        ReclassificationRequest::create([
            'ticket_id' => $ticket->id,
            'requested_by' => Auth::id(),
            'current_sla_category_id' => $ticket->sla_category_id,
            'current_subcategory_name' => $ticket->subcategory_name,
            'current_priority' => $ticket->ticket_type,
            'proposed_sla_category_id' => $slaRule->sla_category_id,
            'proposed_subcategory_name' => $slaRule->subcategory_name,
            'proposed_priority' => $priority,
            'proposed_response_time_minutes' => $responseTime,
            'proposed_resolution_time_minutes' => $resolutionTime,
            'reason' => $request->reason,
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => Auth::id(),
            'notes' => "Re-classification requested by " . Auth::user()->name .
                ". Proposed: {$slaRule->subcategory_name} ({$priority}). Reason: {$request->reason}",
            'changed_at' => now(),
        ]);

        $supervisors = User::withActiveRole('Supervisor - IT Admin')->get();
        foreach ($supervisors as $supervisor) {
            if ($supervisor->email) {
                Mail::to($supervisor->email)->send(
                    new TicketAssignedMail($ticket, 'An IT Admin has requested re-classification and needs your approval.', 'supervisor.dashboard')
                );
            }
        }

        return back()->with(
            'success',
            "Re-classification requested for ticket #{$ticket->ticket_number}. Awaiting Admin Supervisor approval."
        );
    }

    // Full-page support request details for the assigned admin
    public function show(Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        $ticket->load(['user.department', 'assignedTo', 'statusHistories.changedBy', 'feedback', 'attachments.uploader', 'slaCategory']);

        return view('admin.ticket-detail', compact('ticket'));
    }

    // View full ticket history (returns JSON for modal)
    public function history(Tickets $ticket)
    {
        $history = $ticket->load([
            'statusHistories.changedBy',
            'user.department',
            'assignedTo',
            'attachments.uploader',
        ]);

        return response()->json($history);
    }

    // Authorize that only the assigned admin can act
    private function authorizeAdmin(Tickets $ticket): void
    {
        if ($ticket->assigned_to !== Auth::id()) {
            abort(403, 'You are not assigned to this ticket.');
        }
    }

    // An admin can only ever have one ticket in their "in progress" anchor slot
    // at a time — same guard SupervisorDashboardController uses before handing
    // them another one via reassign/takeover.
    private function activeTicketFor(User $admin, ?string $excludeTicketId = null): ?Tickets
    {
        return Tickets::where('assigned_to', $admin->id)
            ->whereIn('status', [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT])
            ->when($excludeTicketId, fn ($q) => $q->where('id', '!=', $excludeTicketId))
            ->first();
    }
}