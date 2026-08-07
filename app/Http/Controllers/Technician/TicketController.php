<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Mail\TicketAssignedMail;
use App\Models\ReclassificationRequest;
use App\Models\SlaRule;
use App\Models\Tickets;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistories;
use App\Models\User;
use App\Services\TicketScheduler;
use App\Support\BusinessClock;
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

        $query = Tickets::where('assigned_to', $user->id)
            ->with(['user.department', 'statusHistories.changedBy', 'attachments', 'slaCategory', 'workloadClass'])
            ->orderByRaw("CASE
                WHEN status = 'Awaiting Support Specialist Acknowledgement'  THEN 1
                WHEN status = 'Awaiting Start SLA' THEN 2
                WHEN status = 'Open'         THEN 3
                WHEN status = 'Escalated'    THEN 4
                WHEN status = 'Closed'       THEN 5
                ELSE 6 END")
            ->orderByRaw("CASE
                WHEN ticket_type = 'Critical' THEN 1
                WHEN ticket_type = 'High'     THEN 2
                WHEN ticket_type = 'Medium'   THEN 3
                WHEN ticket_type = 'Low'      THEN 4
                ELSE 5 END");

        // Status filter — the 4 actionable states, plus the post-resolution states
        // (Pending Supervisor Approval → Pending Closure → Awaiting Requestor) the
        // technician no longer acts on but still needs to track, up until the ticket
        // actually lands on Closed.
        $awaitingClosureStatuses = ['Pending Supervisor Approval', 'Pending Closure', 'Awaiting Requestor'];
        $activeStatuses = array_merge(
            ['Awaiting Support Specialist Acknowledgement', 'Awaiting Start SLA', 'In Progress', 'Escalated'],
            $awaitingClosureStatuses
        );

        if ($status === 'active') {
            $query->whereIn('status', $activeStatuses);
        } elseif ($status === 'awaiting-closure') {
            $query->whereIn('status', $awaitingClosureStatuses);
        } else {
            $mappedStatus = match ($status) {
                'awaiting-ack' => 'Awaiting Support Specialist Acknowledgement',
                'ready-start' => 'Awaiting Start SLA',
                'in-progress' => 'In Progress',
                'escalated' => 'Escalated',
                'closed' => 'Closed',
                default => null
            };
            if ($mappedStatus) {
                $query->where('status', $mappedStatus);
            }
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('request_category', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        $tickets = $query->paginate(10)->withQueryString();

        // Counts — only for this technician
        $counts = [
            'awaiting_ack' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Awaiting Support Specialist Acknowledgement')->count(),
            'ready_start' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Awaiting Start SLA')->count(),
            'in_progress' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Escalated')->count(),
            'awaiting_closure' => Tickets::where('assigned_to', $user->id)
                ->whereIn('status', $awaitingClosureStatuses)->count(),
            'closed' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Closed')->count(),
        ];
        $counts['active'] = $counts['awaiting_ack'] + $counts['ready_start']
            + $counts['in_progress'] + $counts['escalated'] + $counts['awaiting_closure'];

        $freeMinutesToday = TicketScheduler::freeMinutesToday($user);
        $freeTimeLabel = TicketScheduler::freeTimeLabel($user);
        $withinBusinessHours = TicketScheduler::isWithinBusinessHours();

        // Queried directly (not read off $tickets) so the header banner is accurate
        // even when the In Progress ticket is filtered/paginated out of the list below —
        // only one can ever exist per specialist, so this is always at most one row.
        $activeTicket = Tickets::where('assigned_to', $user->id)
            ->where('status', 'In Progress')
            ->first();

        // Saturday Mine-site coverage gap — see selfTriage(). Not scoped to this
        // technician (it's unassigned/unacknowledged), so it's a global queue, not
        // per-user like everything else on this dashboard.
        $selfTriageQueue = $this->isSelfTriageWindow()
            ? Tickets::where('status', 'New Request')
                ->whereNull('date_acknowledged')
                ->whereIn('location', self::MINE_SITE_LOCATIONS)
                ->with('user')
                ->orderBy('created_at')
                ->get()
            : collect();

        // Weekly stats
        // Note: resolved_at is stamped once when the tech resolves the ticket, and never
        // cleared afterward — so we key off the timestamp rather than a status string,
        // since the ticket keeps moving through supervisor-side statuses after that.
        $weekStart = now()->startOfWeek();
        $weekStats = [
            'resolved' => Tickets::where('assigned_to', $user->id)
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', $weekStart)
                ->count(),
            'escalated' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Escalated')
                ->where('updated_at', '>=', $weekStart)
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

        // ── SLA categories with active rules, for the Request Re-classification modal
        // (same rich shape used by Helpdesk's Acknowledge & Classify and the Supervisor's
        // Classify & Assign modals — rule_id/response/resolution needed to submit a proposal).
        $slaCategories = \App\Models\SlaCategory::with([
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

        return view('dashboard.technician', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'weekStats',
            'slaCategoriesJson',
            'freeMinutesToday',
            'freeTimeLabel',
            'withinBusinessHours',
            'activeTicket',
            'selfTriageQueue'
        ));
    }

    // -----------------------------
    // STEP 1: Acknowledge assignment (SLA Response Time #5 ends here)
    // -----------------------------
    public function acknowledge(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'Awaiting Support Specialist Acknowledgement') {
            return back()->with('error', 'Only newly assigned tickets can be acknowledged.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Awaiting Start SLA',
            'tech_acknowledged_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Start SLA',
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
    // STEP 2: Start ticket (SLA Resolution Time officially begins here)
    // -----------------------------
    public function start(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'Awaiting Start SLA') {
            return back()->with('error', 'Ticket must be acknowledged before it can be started.');
        }

        // The time-slot queue assumes serialized work — a specialist can only ever be
        // actively working one ticket at a time. Starting a second one while the first
        // is still open would make the schedule/calendar inaccurate and defeats the
        // point of the queue. Resolve or escalate the current one first.
        $activeTicket = Tickets::where('assigned_to', $ticket->assigned_to)
            ->where('status', 'In Progress')
            ->where('id', '!=', $ticket->id)
            ->first();

        if ($activeTicket) {
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
            'status' => 'In Progress',
            'started_at' => $startedAt, // SLA resolution clock starts here
            'sla_due_at' => $resolutionMinutes
                ? BusinessClock::addBusinessMinutes($startedAt->copy(), $resolutionMinutes)
                : null,
            'sla_risk_notified_at' => null,
            'sla_breached_notified_at' => null,
        ]);

        // Ticket just left the not-started queue (promoted to In Progress) — compact the
        // technician's remaining queued tickets forward to fill the gap it leaves behind.
        TicketScheduler::resequence($ticket->assignedTo);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'In Progress',
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

    // Decline ticket → unassign, return to supervisor queue (can decline before starting work)
    public function decline(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if (!in_array($ticket->status, ['Awaiting Support Specialist Acknowledgement', 'Awaiting Start SLA'])) {
            return back()->with('error', 'Tickets already in progress cannot be declined — escalate instead.');
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $oldStatus = $ticket->status;
        $decliningTech = $ticket->assignedTo;

        $ticket->update([
            'assigned_to' => null,
            'status' => 'Awaiting Supervisor',
            'tech_acknowledged_at' => null,
            'started_at' => null,
            'sla_due_at' => null,
            'sla_risk_notified_at' => null,
            'sla_breached_notified_at' => null,
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_overtime' => false,
            'queued_at' => null,
        ]);

        // Ticket just left the not-started queue — compact the technician's remaining
        // queued tickets forward to fill the gap it leaves behind.
        if ($decliningTech) {
            TicketScheduler::resequence($decliningTech);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Supervisor',
            'changed_by' => Auth::id(),
            'notes' => "Declined by " . Auth::user()->name .
                ". Reason: {$request->reason}. Returned to supervisor for reassignment.",
            'changed_at' => now(),
        ]);

        return redirect()
            ->route('technician.dashboard')
            ->with(
                'success',
                "Ticket #{$ticket->ticket_number} declined and returned to supervisor."
            );
    }

    // Add update / progress note (only while In Progress)
    public function update(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'In Progress') {
            return back()->with('error', 'Only started tickets can receive progress updates.');
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
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "[{$request->work_status}] " . $request->progress_notes,
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Progress update logged for #{$ticket->ticket_number}."
        );
    }

    // -----------------------------
    // STEP 3: Resolve ticket (SLA Resolution Time ends here)
    // -----------------------------
    public function resolve(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'In Progress') {
            return back()->with('error', 'Only started tickets can be marked resolved.');
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
            'status' => 'Pending Supervisor Approval',
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
            'resolution_notes' => $request->resolution_notes,
            'service_type' => $request->service_type,
            'findings' => $request->findings,
            'recommendation' => $request->recommendation,
        ]);

        // Ticket just left the In-Progress anchor slot — if it finished early (or ran
        // long), the technician's queued-but-not-started tickets need to slide to
        // match reality instead of keeping their originally-projected times.
        TicketScheduler::resequence($ticket->assignedTo);

        // Ground-truth elapsed time (started_at -> resolved_at) — not self-reported,
        // so it can't drift from what the SLA clock actually measured.
        $timeSpent = $ticket->actualResolutionTime() ?? 'unknown';

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
            'new_status' => 'Pending Supervisor Approval',
            'changed_by' => Auth::id(),
            'notes' => "Resolved by " . Auth::user()->name .
                ". Time spent: {$timeSpent}. " .
                $request->resolution_notes,
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved. Awaiting Supervisor validation."
        );
    }

    // Escalate to Supervisor (only while In Progress)
    // Note: assigned_to is intentionally left untouched here. The ticket keeps showing
    // in this technician's queue (under the Escalated tab, then eventually Closed)
    // even after a supervisor/admin-side member picks it up.
    public function escalate(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'In Progress') {
            return back()->with('error', 'Only started tickets can be escalated.');
        }

        $request->validate([
            'reason' => 'required|string',
            'already_tried' => 'required|string',
        ]);

        $oldStatus = $ticket->status;
        $escalatingTech = $ticket->assignedTo;

        $ticket->update([
            'status' => 'Escalated',
            'assigned_to' => null,
            'escalation_level' => $ticket->escalation_level + 1,
            // Clear stale scheduling data — matches decline()'s cleanup. Nothing
            // currently reads a ticket's schedule while unassigned, but leaving these
            // set is dormant bad data for whenever something eventually does.
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_overtime' => false,
            'queued_at' => null,
        ]);

        // Ticket just left the In-Progress anchor slot and the not-started queue —
        // the technician's remaining queued tickets need to slide to match reality.
        if ($escalatingTech) {
            TicketScheduler::resequence($escalatingTech);
        }

        DB::table('escalations')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'ticket_id' => $ticket->id,
            'escalation_level' => $ticket->escalation_level,
            'escalated_by' => Auth::id(),
            'previous_tech_id' => Auth::id(),
            'reassigned_to' => null,
            'reason' => $request->reason,
            'resolution_notes' => $request->already_tried,
            'escalated_at' => now(),
            'resolved_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Escalated',
            'changed_by' => Auth::id(),
            'notes' => "Escalated to Supervisor by " . Auth::user()->name .
                ". Reason: {$request->reason}. " .
                "Already tried: {$request->already_tried}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} escalated to Supervisor."
        );
    }

    // Request re-classification (only while In Progress) — proposes a corrected category/
    // subcategory/priority for the Supervisor to approve. Unlike escalate(), this doesn't hand
    // the ticket to a higher tier: it's for "this is just miscategorized," and the ticket comes
    // back to the normal classify/assign queue once approved (see
    // SupervisorDashboardController::approveReclassification()).
    public function requestReclassification(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'In Progress') {
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

        $oldStatus = $ticket->status;
        $reclassifyingTech = $ticket->assignedTo;

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

        $ticket->update([
            'status' => 'Pending Reclassification',
            'assigned_to' => null,
            // Clear stale scheduling data — matches decline()'s cleanup.
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_overtime' => false,
            'queued_at' => null,
        ]);

        // Ticket just left the In-Progress anchor slot and the not-started queue —
        // the technician's remaining queued tickets need to slide to match reality.
        if ($reclassifyingTech) {
            TicketScheduler::resequence($reclassifyingTech);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Pending Reclassification',
            'changed_by' => Auth::id(),
            'notes' => "Re-classification requested by " . Auth::user()->name .
                ". Proposed: {$slaRule->subcategory_name} ({$priority}). Reason: {$request->reason}",
            'changed_at' => now(),
        ]);

        $supervisors = User::withActiveRole('Supervisor - Support Specialist')->get();
        foreach ($supervisors as $supervisor) {
            Mail::to($supervisor->email)->send(
                new TicketAssignedMail($ticket, 'A technician has requested re-classification and needs your approval.', 'supervisor.support.dashboard')
            );
        }

        return back()->with(
            'success',
            "Re-classification requested for ticket #{$ticket->ticket_number}. Awaiting Supervisor approval."
        );
    }

    // Authorize that only the assigned tech can act
    private function authorizeTech(Tickets $ticket): void
    {
        if ($ticket->assigned_to !== Auth::id()) {
            abort(403, 'You are not assigned to this ticket.');
        }
    }

    // ── Mine-site Saturday coverage gap ──
    // Mine site locations run Mon-Sat; Helpdesk and Supervisor both follow HQ's
    // Mon-Fri schedule, so nothing acts on a New Request submitted from these
    // locations on a Saturday. selfTriage() below lets any active Technician
    // stand in for the normally-separate Helpdesk-acknowledge +
    // Helpdesk/Supervisor-classify + Supervisor-assign steps, scoped narrowly to
    // this specific gap (Saturday + Mine-site location + still-unacknowledged).
    private const MINE_SITE_LOCATIONS = ['Zambales Site', 'Porac Site', 'Bauan Site'];

    private function isSelfTriageWindow(): bool
    {
        return now()->timezone('Asia/Manila')->isSaturday();
    }

    // Self-triage: acknowledge + classify + self-assign a Mine-site New Request
    // in one action. Priority/response/resolution come from the chosen SLA Rule,
    // same as Helpdesk/Supervisor classification elsewhere — nothing about the
    // SLA data model changes, only who is allowed to trigger it, and only under
    // this narrow condition.
    public function selfTriage(Request $request, Tickets $ticket)
    {
        if (!$this->isSelfTriageWindow()) {
            return back()->with('error', 'Self-triage is only available on Saturdays, when Helpdesk and Supervisor are not on duty.');
        }

        if (!in_array($ticket->location, self::MINE_SITE_LOCATIONS, true)) {
            return back()->with('error', 'Self-triage is only available for Mine site requests.');
        }

        if ($ticket->status !== 'New Request' || !is_null($ticket->date_acknowledged)) {
            return back()->with('error', 'Only unacknowledged New Requests can be self-triaged.');
        }

        $request->validate([
            'sla_rule_id' => 'required|exists:sla_rules,id',
            // ── No 'auto' option here (unlike the Supervisor/Helpdesk classify flows) —
            //    there's no schedule-conflict UI on the technician dashboard to resolve
            //    a needs_decision prompt, so an overflow silently rolls to the next
            //    available day rather than forcing unplanned overtime without asking.
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        $slaRule = SlaRule::findOrFail($request->sla_rule_id);
        $technician = Auth::user();
        $priority = $slaRule->priority;
        $responseTime = $slaRule->response_time_minutes;
        $resolutionTime = $slaRule->resolution_time_minutes;

        $slot = TicketScheduler::commitAssignment(
            $ticket,
            $technician,
            $priority,
            $responseTime + $resolutionTime,
            $request->input('schedule_decision', 'next_day')
        );

        $oldStatus = $ticket->status;

        $ticket->update([
            // Plain strings, not Carbon-cast — computed as Asia/Manila wall-clock
            // values directly, since app.timezone is UTC.
            'date_acknowledged' => now()->timezone('Asia/Manila')->toDateString(),
            'time_acknowledged' => now()->timezone('Asia/Manila')->toTimeString(),
            'sla_category_id' => $slaRule->sla_category_id,
            'subcategory_name' => $slaRule->subcategory_name,
            'ticket_type' => $priority,
            'response_time_minutes' => $responseTime,
            'resolution_time_minutes' => $resolutionTime,
            'assigned_to' => $technician->id,
            'assigned_at' => now(),
            'status' => 'Awaiting Support Specialist Acknowledgement',
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Support Specialist Acknowledgement',
            'changed_by' => Auth::id(),
            'notes' => "Self-triaged by {$technician->name} — Helpdesk/Supervisor not on duty (Saturday, {$ticket->location})."
                . " Classified as {$slaRule->subcategory_name} ({$priority}) and self-assigned.",
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} self-triaged and assigned to you.");
    }
}