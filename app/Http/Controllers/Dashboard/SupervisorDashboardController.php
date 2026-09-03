<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketReadyForRequestorMail;
use Illuminate\Http\Request;
use App\Models\Tickets;
use App\Models\User;
use App\Models\SlaRule;
use App\Models\SlaCategory;
use App\Models\ReclassificationRequest;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistories;
use App\Services\TicketScheduler;
use App\Support\BusinessClock;
use App\Support\TicketHold;
use App\Support\TicketReportProgress;
use App\Support\TicketResolutionRules;
use App\Support\TicketSlaResolution;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupervisorDashboardController extends Controller
{
    // The time-slot queue assumes serialized work — a technician can only ever be
    // actively working one ticket at a time (same rule as
    // Technician\TicketController::start()'s hard block). reassign()/takeover()/
    // requestRevision() all put a ticket directly into In Progress Service
    // Request for a specific technician, bypassing that check entirely, so each
    // needs its own guard before doing so. Includes the drafting status too — a
    // technician writing up the report is still "on" that ticket, not free for another.
    private function activeTicketFor(User $technician, ?string $excludeTicketId = null): ?Tickets
    {
        return Tickets::where('assigned_to', $technician->id)
            ->whereIn('status', [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT])
            ->when($excludeTicketId, fn ($q) => $q->where('id', '!=', $excludeTicketId))
            ->first();
    }

    // Resolutions land straight on Awaiting Requestor now instead of waiting on
    // Helpdesk's old manual Close & Notify step — the requestor's own "please
    // confirm" email is already handled by TicketObserver (fires automatically on
    // any transition into Awaiting Requestor), so this is purely an FYI to keep
    // Helpdesk in the loop, not a duplicate of that email.
    private function notifyHelpdeskFyi(Tickets $ticket, string $note): void
    {
        $helpdeskUsers = User::withActiveRole('Helpdesk')->get();
        foreach ($helpdeskUsers as $helpdeskUser) {
            if ($helpdeskUser->email) {
                Mail::to($helpdeskUser->email)->send(new TicketReadyForRequestorMail($ticket, $note));
            }
        }
    }

    // Support Supervisor Openning
    public function supportIndex(Request $request)
    {
        $status = $request->filled('status')
            ? $request->get('status')
            : 'awaiting-classification';

        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy', 'reclassificationRequests', 'attachments']);

        // In Progress – Service Report and Done – Service Report are shared across
        // every resolver track (see TicketReportProgress), so this dashboard has to
        // scope them to the Support track's own resolvers (Technician + this
        // supervisor's own take-over resolve) via assigned_to's role — otherwise an
        // IT Admin's drafting/done tickets would bleed into this queue too.
        $supportResolverRoles = ['IT Support Specialist', 'Supervisor - Support Specialist'];
        $supportDoneRoles = ['IT Support Specialist', 'Helpdesk']; // supportResolve() has no approval gate, so a supervisor take-over never reaches Done itself
        // Everyone who can be the resolved_by on a Support-track ticket by the time
        // it reaches Requestor Confirmation — resolver roles plus Helpdesk (L1
        // self-resolve). Requestor Confirmation has no pending_role left to scope
        // by (fully resolved), so this is keyed off who actually resolved it instead.
        $supportRequestorRoles = array_merge($supportResolverRoles, ['Helpdesk']);

        // 'active' = everything still in flight for this supervisor, before it lands on Closed —
        // includes Awaiting Requestor so a ticket the supervisor validated doesn't
        // disappear from view until it's truly Closed. Also includes Awaiting Support
        // Specialist Acknowledgement — the ticket the supervisor just classified &
        // assigned shouldn't vanish from their queue the moment they hand it off; they
        // still need to see it until the tech acts on it.
        // 'For Acknowledgment'/'Classified'/'Assigned'/'In Progress Service
        // Request'/'In Progress Service Report'/'Report For Review'/'Escalated'
        // are shared across every resolver track now — this supervisor's queue
        // is scoped either by pending_role (queue-level, no assignee yet) or by
        // assignedTo.role (once assigned), same pattern as TicketReportProgress
        // already used for the drafting/done statuses.
        switch ($status) {
            case 'active':
                $query->where(function ($q) use ($supportResolverRoles, $supportDoneRoles, $supportRequestorRoles) {
                    $q->where(fn ($q2) => $q2->whereIn('status', [TicketStatus::CLASSIFIED, TicketStatus::ESCALATED])->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR))
                        ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::ASSIGNED, $supportResolverRoles))
                        ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::IN_PROGRESS_SERVICE_REQUEST, $supportResolverRoles))
                        ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::ON_HOLD, $supportResolverRoles))
                        ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::IN_PROGRESS_SERVICE_REPORT, $supportResolverRoles))
                        ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::REPORT_FOR_REVIEW, $supportDoneRoles))
                        ->orWhere(fn ($q2) => $q2->where('status', TicketStatus::REQUESTOR_CONFIRMATION)
                            ->whereHas('resolvedBy.role', fn ($q3) => $q3->whereIn('role_name', $supportRequestorRoles)));
                });
                break;
            case 'awaiting-classification':
                // Helpdesk's classify() lands a ticket straight on Classified, ready
                // for the Supervisor to assign — no separate acknowledge/reclassify
                // step. Escalated tickets have their own dedicated 'escalated' tab
                // below and don't belong here too — an approved reclassification
                // (see approveReclassification() below) already lands back on
                // Classified + this pending_role (assigned_to cleared), which is
                // enough on its own to re-enter this queue.
                $query->where('status', TicketStatus::CLASSIFIED)
                    ->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR)
                    ->whereNull('assigned_to');
                break;
            case 'awaiting-tech-ack':
                // Assigned, not yet acknowledged by the specialist — mirrors
                // Technician\TicketController::index()'s 'awaiting-ack' split so
                // this queue can tell "still needs the specialist's attention" apart
                // from "already acknowledged, just waiting for them to hit Start".
                TicketReportProgress::forRoles($query, TicketStatus::ASSIGNED, ['IT Support Specialist']);
                $query->whereNull('tech_acknowledged_at');
                break;
            case 'ready-to-start':
                // Assigned + acknowledged, not yet started — the "Start Support
                // Request" bucket. Mirrors Technician\TicketController::index()'s
                // 'ready-start' tab so a supervisor can see the same distinction
                // the specialist sees on their own dashboard.
                TicketReportProgress::forRoles($query, TicketStatus::ASSIGNED, ['IT Support Specialist']);
                $query->whereNotNull('tech_acknowledged_at');
                break;
            case 'in-progress':
                TicketReportProgress::forRoles($query, TicketStatus::IN_PROGRESS_SERVICE_REQUEST, $supportResolverRoles);
                break;
            case 'on-hold':
                TicketReportProgress::forRoles($query, TicketStatus::ON_HOLD, $supportResolverRoles);
                break;
            case 'in-progress-report':
                TicketReportProgress::forRoles($query, TicketStatus::IN_PROGRESS_SERVICE_REPORT, $supportResolverRoles);
                break;
            case 'escalated':
                $query->where('status', TicketStatus::ESCALATED)->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR);
                break;
            case 'pending-reclassification':
                // Reclassification is tracked solely on reclassification_requests now
                // — the ticket's own status is left untouched while one is pending.
                $query->whereHas('reclassificationRequests', fn ($q) => $q->where('status', 'pending'));
                break;
            case 'pending-supervisor-approval':
                TicketReportProgress::forRoles($query, TicketStatus::REPORT_FOR_REVIEW, $supportDoneRoles);
                break;
            case 'awaiting-requestor':
                $query->where('status', TicketStatus::REQUESTOR_CONFIRMATION)
                    ->whereHas('resolvedBy.role', fn ($q) => $q->whereIn('role_name', $supportRequestorRoles));
                break;
            case 'closed':
                $query->where('status', TicketStatus::CLOSED);
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
                        WHEN ticket_type = 'Critical' THEN 1
                        WHEN ticket_type = 'High' THEN 2
                        WHEN ticket_type = 'Medium' THEN 3
                        WHEN ticket_type = 'Low' THEN 4
                        ELSE 5
                    END
                ");
                break;
            default: // newest
                $query->orderBy('created_at', 'desc');
                break;
        }

        $tickets = $query->paginate(10)->withQueryString();

        // Annotate each ticket's assigned technician with their CURRENT remaining
        // capacity (not a snapshot from when the ticket was assigned) — the supervisor
        // otherwise has no visibility into how full a specialist's day is once a ticket
        // is already assigned, only while actively picking one in the assign modal.
        // Cached per technician so the same tech isn't recomputed for every ticket row.
        $techCapacityCache = [];
        foreach ($tickets as $t) {
            if (!$t->assignedTo) {
                continue;
            }

            $techId = $t->assignedTo->id;

            if (!isset($techCapacityCache[$techId])) {
                $status = TicketScheduler::statusFor($t->assignedTo);
                $techCapacityCache[$techId] = [
                    'schedule_status' => $status,
                    'availability' => match ($status) {
                        'available' => 'free',
                        'busy' => 'busy',
                        'overtime', 'on_leave' => 'full',
                    },
                    'free_time_label' => TicketScheduler::freeTimeLabel($t->assignedTo),
                ];
            }

            $t->assignedTo->schedule_status = $techCapacityCache[$techId]['schedule_status'];
            $t->assignedTo->availability = $techCapacityCache[$techId]['availability'];
            $t->assignedTo->free_time_label = $techCapacityCache[$techId]['free_time_label'];
        }

        $counts = [
            'awaiting_classification' => Tickets::where('status', TicketStatus::CLASSIFIED)
                ->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR)
                ->whereNull('assigned_to')
                ->count(),
            'awaiting_tech_ack' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::ASSIGNED, ['IT Support Specialist'])->whereNull('tech_acknowledged_at')->count(),
            'ready_to_start' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::ASSIGNED, ['IT Support Specialist'])->whereNotNull('tech_acknowledged_at')->count(),
            'in_progress' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::IN_PROGRESS_SERVICE_REQUEST, $supportResolverRoles)->count(),
            'on_hold' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::ON_HOLD, $supportResolverRoles)->count(),
            'in_progress_report' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::IN_PROGRESS_SERVICE_REPORT, $supportResolverRoles)->count(),
            'escalated' => Tickets::where('status', TicketStatus::ESCALATED)->where('pending_role', TicketStatus::QUEUE_SUPPORT_SUPERVISOR)->count(),
            'pending_reclassification' => Tickets::whereHas('reclassificationRequests', fn ($q) => $q->where('status', 'pending'))->count(),
            'pending_supervisor_approval' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::REPORT_FOR_REVIEW, $supportDoneRoles)->count(),
            'awaiting_requestor' => Tickets::where('status', TicketStatus::REQUESTOR_CONFIRMATION)
                ->whereHas('resolvedBy.role', fn ($q) => $q->whereIn('role_name', $supportRequestorRoles))
                ->count(),
            'closed' => Tickets::where('status', TicketStatus::CLOSED)->count(),
        ];
        $counts['active'] = $counts['awaiting_classification'] + $counts['awaiting_tech_ack'] + $counts['ready_to_start']
            + $counts['in_progress'] + $counts['on_hold'] + $counts['in_progress_report'] + $counts['escalated'] + $counts['pending_reclassification']
            + $counts['pending_supervisor_approval'] + $counts['awaiting_requestor'];

        $technicians = User::whereHas(
            'role',
            fn($q) =>
            $q->where('role_name', 'IT Support Specialist')
        )
            ->get()
            ->map(function ($tech) {
                $status = TicketScheduler::statusFor($tech);
                // Keep the old free/busy/full CSS class names (avail-dot free/busy/full)
                // so styling doesn't need to change — only the human-facing label does.
                $tech->schedule_status = $status;
                $tech->availability = match ($status) {
                    'available' => 'free',
                    'busy' => 'busy',
                    'overtime', 'on_leave' => 'full',
                };
                $tech->free_minutes_today = TicketScheduler::freeMinutesToday($tech);
                $tech->free_time_label = TicketScheduler::freeTimeLabel($tech);
                $tech->free_window_today = TicketScheduler::freeWindowToday($tech);
                return $tech;
            });

        $slaCategories = SlaCategory::with([
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

        $workloadClassesJson = \App\Models\WorkloadClass::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'response_minutes' => $w->response_minutes,
                'resolution_minutes' => $w->resolution_minutes,
                'requires_manual_resolution' => $w->requires_manual_resolution,
            ])->values()->toArray();

        return view('dashboard.support.dashboard', compact(
            'tickets',
            'counts',
            'technicians',
            'status',
            'search',
            'sort',
            'slaCategoriesJson',
            'workloadClassesJson'
        ));
    }

    // Full-page support request details for the Support Specialist Supervisor —
    // this dashboard is a team-wide queue view (not "assigned to me only", see
    // supportIndex() above), so unlike Admin\TicketController::show() there's no
    // assigned_to ownership gate here — the role middleware is the boundary.
    public function show(Tickets $ticket)
    {
        $ticket->load(['user.department', 'assignedTo.role', 'statusHistories.changedBy', 'feedback', 'attachments.uploader', 'slaCategory']);

        return view('supervisor.support.ticket-detail', compact('ticket'));
    }

    public function supportAcknowledge(Tickets $ticket)
    {
        $alreadyAcknowledged = TicketStatusHistories::where('ticket_id', $ticket->id)
            ->where('notes', 'like', 'Acknowledged by Supervisor%')
            ->exists();

        if ($ticket->status !== TicketStatus::FOR_ACKNOWLEDGMENT
            || $ticket->pending_role !== TicketStatus::QUEUE_SUPPORT_SUPERVISOR
            || $alreadyAcknowledged) {
            return back()->with('error', 'This ticket is not awaiting acknowledgment.');
        }

        // Queue-level acknowledgement doesn't change status — mirrors Helpdesk's
        // own acknowledge(), which only stamps a timestamp/note. Real classification
        // is what actually advances the status (classifyAndAssign() below).
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::FOR_ACKNOWLEDGMENT,
            'new_status' => TicketStatus::FOR_ACKNOWLEDGMENT, // keep status unchanged
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
            // Helpdesk's classification is authoritative by default — only required
            // if the Supervisor is actually overriding it.
            'sla_rule_id' => 'nullable|exists:sla_rules,id',
            // Either a technician to assign to, or take_over to keep it — never both,
            // enforced below rather than via required_if since an unchecked checkbox
            // simply omits 'take_over' from the request instead of sending "false".
            'technician_id' => 'nullable|uuid|exists:users,id',
            'take_over' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
            // ── Optional per-ticket overrides — only meaningful alongside sla_rule_id.
            'priority' => 'nullable|in:Critical,High,Medium,Low',
            'workload_class_id' => 'nullable|exists:workload_classes,id',
            'response_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'resolution_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        if ($ticket->status !== TicketStatus::CLASSIFIED
            || $ticket->pending_role !== TicketStatus::QUEUE_SUPPORT_SUPERVISOR) {
            return back()->with('error', 'Ticket must be classified before it can be assigned.');
        }

        $takeOver = $request->boolean('take_over');

        if (!$takeOver && !$request->filled('technician_id')) {
            return back()->withInput()->with('error', 'Select a technician to assign, or choose to take the ticket over yourself.');
        }

        $technician = $takeOver ? null : User::findOrFail($request->technician_id);

        // Overriding Helpdesk's classification is optional — only resolve a new
        // SlaRule if the Supervisor actually picked one; otherwise accept the
        // classification already on the ticket as-is.
        $slaRule = $request->filled('sla_rule_id') ? SlaRule::findOrFail($request->sla_rule_id) : null;
        $workloadClass = $request->filled('workload_class_id')
            ? \App\Models\WorkloadClass::find($request->workload_class_id)
            : null;

        if ($slaRule) {
            $resolved = TicketSlaResolution::resolve(
                $slaRule,
                $workloadClass,
                $request->priority,
                $request->filled('response_time_minutes') ? (int) $request->response_time_minutes : null,
                $request->filled('resolution_time_minutes') ? (int) $request->resolution_time_minutes : null,
            );

            if (isset($resolved['error'])) {
                return back()->with('error', $resolved['error']);
            }

            ['priority' => $priority, 'response_time_minutes' => $responseTime, 'resolution_time_minutes' => $resolutionTime] = $resolved;
        } else {
            $priority = $ticket->ticket_type;
            $responseTime = $ticket->response_time_minutes;
            $resolutionTime = $ticket->resolution_time_minutes;
        }

        // ── Taking it over means starting work now, not queueing behind other not-
        // started tickets — same immediate-anchor-slot mechanism takeover() already
        // uses for escalated tickets, so it lands in 'In Progress' next to a
        // Technician's own tickets and Add Update/Resolve (supportUpdate/
        // supportResolve below) work on it exactly the same way, no extra status
        // needed.
        if ($takeOver) {
            $supervisor = Auth::user();

            if ($activeTicket = $this->activeTicketFor($supervisor, $ticket->id)) {
                return back()->with(
                    'error',
                    "You're already working on ticket #{$activeTicket->ticket_number} — resolve or escalate it before taking over another."
                );
            }

            $slot = TicketScheduler::commitDirectSlot(
                $ticket,
                $supervisor,
                $responseTime + $resolutionTime,
                $request->input('schedule_decision', 'auto')
            );
        } else {
            $slot = TicketScheduler::commitAssignment(
                $ticket,
                $technician,
                $priority,
                $responseTime + $resolutionTime,
                $request->input('schedule_decision', 'auto')
            );
        }

        if ($slot['needs_decision']) {
            return back()->withInput()->with('scheduleConflict', [
                'action_url' => route('supervisor.support.tickets.classify-assign', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $takeOver ? Auth::user()->name : $technician->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $now = now();

        // Ticket already arrived at Classified (Helpdesk's classify() set it) — only
        // touch the classification fields if the Supervisor actually overrode them.
        if ($slaRule) {
            $overrideNote = TicketSlaResolution::overrideNote($slaRule, $priority, $responseTime, $resolutionTime, $workloadClass);

            $ticket->update([
                'sla_category_id' => $slaRule->sla_category_id,
                'subcategory_name' => $slaRule->subcategory_name,
                'workload_class_id' => $workloadClass?->id,
                'ticket_type' => $priority,
                'response_time_minutes' => $responseTime,
                'resolution_time_minutes' => $resolutionTime,
            ]);

            TicketStatusHistories::create([
                'ticket_id' => $ticket->id,
                'old_status' => TicketStatus::CLASSIFIED,
                'new_status' => TicketStatus::CLASSIFIED,
                'changed_by' => Auth::id(),
                'notes' => "Classification overridden by Supervisor - " . Auth::user()->name . ": {$slaRule->subcategory_name} ({$priority})." . $overrideNote,
                'changed_at' => $now,
            ]);
        }

        if ($takeOver) {
            $ticket->update([
                'assigned_to' => Auth::id(),
                'assigned_at' => $now,
                'pending_role' => null,
                'status' => TicketStatus::ASSIGNED,
                'scheduled_start' => $slot['scheduled_start'],
                'scheduled_end' => $slot['scheduled_end'],
                'is_overtime' => $slot['is_overtime'],
                'queued_at' => $slot['queued_at'],
            ]);

            TicketStatusHistories::create([
                'ticket_id' => $ticket->id,
                'old_status' => TicketStatus::CLASSIFIED,
                'new_status' => TicketStatus::ASSIGNED,
                'changed_by' => Auth::id(),
                'notes' => 'Taken over directly by ' . Auth::user()->name . '.'
                    . ($request->notes ? " Note: {$request->notes}" : ''),
                'changed_at' => $now,
            ]);

            $ticket->update([
                'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
                'started_at' => $now, // SLA resolution clock starts here
                'sla_due_at' => $resolutionTime
                    ? BusinessClock::addBusinessMinutes($now->copy(), $resolutionTime)
                    : null,
                'sla_risk_notified_at' => null,
                'sla_breached_notified_at' => null,
            ]);

            TicketStatusHistories::create([
                'ticket_id' => $ticket->id,
                'old_status' => TicketStatus::ASSIGNED,
                'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
                'changed_by' => Auth::id(),
                'notes' => 'Work started immediately on take-over.',
                'changed_at' => $now,
            ]);

            return back()->with('success', "Ticket #{$ticket->ticket_number} classified and taken over.");
        }

        $ticket->update([
            'assigned_to' => $technician->id,
            'assigned_at' => $now,
            'pending_role' => null,
            'status' => TicketStatus::ASSIGNED,
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::CLASSIFIED,
            'new_status' => TicketStatus::ASSIGNED,
            'changed_by' => Auth::id(),
            'notes' => "Assigned to {$technician->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => $now,
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
                'action_url' => route('supervisor.support.tickets.reassign', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $newTech->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'assigned_at' => now(),
            'pending_role' => null,
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
        ]);

        // Ticket just left the outgoing technician's active slot and/or not-started
        // queue — their remaining queued tickets need to slide to match reality.
        if ($oldTechUser && $oldTechUser->id !== $newTech->id) {
            TicketScheduler::resequence($oldTechUser);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Reassignment',
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => "Reassigned from {$oldTech} to {$newTech->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket reassigned to {$newTech->name}.");
    }

    public function takeover(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::ESCALATED || $ticket->pending_role !== TicketStatus::QUEUE_SUPPORT_SUPERVISOR) {
            return back()->with('error', 'Only escalated tickets can be taken over.');
        }

        $request->validate([
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        $oldStatus = $ticket->status;
        $supervisor = Auth::user();

        if ($activeTicket = $this->activeTicketFor($supervisor, $ticket->id)) {
            return back()->with(
                'error',
                "You're already working on ticket #{$activeTicket->ticket_number} — resolve or escalate it before taking over another."
            );
        }

        $slot = TicketScheduler::commitDirectSlot(
            $ticket,
            $supervisor,
            TicketScheduler::effortMinutes($ticket),
            $request->input('schedule_decision', 'auto')
        );

        if ($slot['needs_decision']) {
            return back()->withInput()->with('scheduleConflict', [
                'action_url' => route('supervisor.support.tickets.takeover', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $supervisor->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $ticket->update([
            'assigned_to' => Auth::id(),
            'assigned_at' => now(),
            'pending_role' => null,
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'started_at' => $ticket->started_at ?? now(), // guard against a blank Start Time on the service report if it somehow wasn't already set
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
            'notes' => 'Supervisor took over the ticket directly - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "You have taken over ticket #{$ticket->ticket_number}.");
    }

    // Add update / progress note (In Progress or drafting the report — opening the
    // Resolve modal shouldn't lock the supervisor out of logging more progress if
    // they end up not submitting it)
    public function supportUpdate(Request $request, Tickets $ticket)
    {
        if (!in_array($ticket->status, [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT], true)) {
            return back()->with('error', 'Only in-progress tickets can receive progress updates.');
        }

        $request->validate([
            'progress_notes' => 'required|string',
            'work_status' => 'required|string',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => Auth::id(),
            'notes' => "[{$request->work_status}] " . $request->progress_notes,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Progress update logged for #{$ticket->ticket_number}.");
    }

    // The technical fix is done, separate from writing it up — isolates "still
    // fixing it" from "preparing the service report" as two deliberate actions
    // instead of one combined submit (see TicketReportProgress). Resolve only
    // becomes available after this. No assigned_to check, mirroring
    // supportResolve() below: a supervisor can act on a ticket still assigned to
    // one of their technicians.
    public function supportStartReport(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REQUEST) {
            return back()->with('error', 'Only in-progress tickets can be marked fixed.');
        }

        TicketReportProgress::markStarted($ticket);

        return back()->with('success', "Ticket #{$ticket->ticket_number} marked fixed — prepare the service report when ready.");
    }

    // Pause/Resume — for when finishing the ticket needs more information from
    // the requestor. See TicketHold. No assigned_to check, mirroring
    // supportStartReport() above: the supervisor can act on a ticket still
    // assigned to one of their technicians, not just their own take-over.
    public function supportPause(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REQUEST) {
            return back()->with('error', 'Only in-progress tickets can be paused.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        TicketHold::pause($ticket, $request->reason);

        return back()->with('success', "Ticket #{$ticket->ticket_number} paused. The requestor has been notified.");
    }

    public function supportResume(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::ON_HOLD) {
            return back()->with('error', 'This ticket is not on hold.');
        }

        TicketHold::resume($ticket);

        return back()->with('success', "Work resumed on ticket #{$ticket->ticket_number}.");
    }

    // Mark resolved — self-approval track: the supervisor resolving their own
    // take-over ticket has no reviewer above them, so this cascades the entire
    // remaining chain (Done Service Report -> Report For Review -> Approved
    // Service Report -> Requestor Confirmation) in one action instead of
    // stopping at Report For Review like the tech/Helpdesk-L1 resolve() does.
    // Only reachable after supportStartReport() (In Progress Service Report) —
    // the fix itself has to already be marked done.
    public function supportResolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REPORT) {
            return back()->with('error', 'Mark the ticket fixed before preparing the service report.');
        }

        $request->validate(array_merge(TicketResolutionRules::BASE, [
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]));

        $now = now();
        $oldStatus = $ticket->status;
        $ticket->update([
            'status' => TicketStatus::DONE_SERVICE_REPORT,
            'resolved_at' => $now,
            'resolved_by' => Auth::id(),
            'resolution_notes' => $request->resolution_notes,
            'service_type' => $request->service_type,
            'findings' => $request->findings,
            'recommendation' => $request->recommendation,
        ]);

        // Ground-truth elapsed time (started_at -> resolved_at) — not self-reported, so
        // it can't drift from what the SLA clock actually measured (see Technician track).
        $timeSpent = $ticket->actualResolutionTime() ?? 'unknown';
        $resolutionNote = "Resolved by " . Auth::user()->name .
            ". Time spent: {$timeSpent}. " .
            $request->resolution_notes;

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
            'ticket_id' => $ticket->id, 'old_status' => $oldStatus, 'new_status' => TicketStatus::DONE_SERVICE_REPORT,
            'changed_by' => Auth::id(), 'notes' => $resolutionNote, 'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::REPORT_FOR_REVIEW]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id, 'old_status' => TicketStatus::DONE_SERVICE_REPORT, 'new_status' => TicketStatus::REPORT_FOR_REVIEW,
            'changed_by' => Auth::id(), 'notes' => $resolutionNote, 'changed_at' => $now,
        ]);

        $selfApprovalNote = 'Self-approved — resolved directly by the supervisor who took over this ticket.';
        $ticket->update([
            'status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'validated_at' => $now,
            'validation_notes' => $selfApprovalNote,
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id, 'old_status' => TicketStatus::REPORT_FOR_REVIEW, 'new_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'changed_by' => Auth::id(), 'notes' => $selfApprovalNote, 'changed_at' => $now,
        ]);

        $ticket->update([
            'status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'resolved_at' => $now,
            'resolved_by' => Auth::id(),
            'closed_at' => $now,
        ]);

        // Ticket just left the In-Progress anchor slot — if it finished early (or ran
        // long), the assignee's queued-but-not-started tickets need to slide to match
        // reality instead of keeping their originally-projected times.
        if ($ticket->assignedTo) {
            TicketScheduler::resequence($ticket->assignedTo);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'new_status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'changed_by' => Auth::id(),
            'notes' => $resolutionNote,
            'changed_at' => $now,
        ]);

        $this->notifyHelpdeskFyi($ticket, 'Resolved directly by Supervisor - ' . Auth::user()->name . '.');

        return back()->with('success', "Ticket #{$ticket->ticket_number} resolved. Requestor notified.");
    }

    public function validateResolution(Request $request, Tickets $ticket)
    {
        $request->validate([
            'validation_notes' => 'required|string|max:1000',
        ]);

        // Report For Review is shared across every resolver track (see
        // TicketReportProgress) — restricted to this supervisor's own Technician/
        // Helpdesk-L1 team so they can't validate an IT Admin's ticket via a direct
        // route hit. This is the new report-approval gate: cascades
        // Report For Review -> Approved Service Report -> Requestor Confirmation.
        if ($ticket->status !== TicketStatus::REPORT_FOR_REVIEW
            || !in_array($ticket->assignedTo?->role?->role_name, ['IT Support Specialist', 'Helpdesk'], true)) {
            return back()->with('error', 'Only tickets pending closure can be validated.');
        }

        $now = now();

        $ticket->update([
            'status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'validated_at' => $now,
            'validation_notes' => $request->validation_notes,
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::REPORT_FOR_REVIEW,
            'new_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'changed_by' => Auth::id(),
            'notes' => 'Resolution validated by Supervisor - ' . Auth::user()->name . ". {$request->validation_notes}",
            'changed_at' => $now,
        ]);

        $ticket->update([
            'status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'closed_at' => $now,
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'new_status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'changed_by' => Auth::id(),
            'notes' => 'Resolution validated by Supervisor - ' . Auth::user()->name . ". {$request->validation_notes}",
            'changed_at' => $now,
        ]);

        $this->notifyHelpdeskFyi($ticket, 'Resolution validated by Supervisor - ' . Auth::user()->name . '.');

        return back()->with('success', "Resolution for ticket #{$ticket->ticket_number} validated. Requestor notified.");
    }

    // Supervisor isn't satisfied with the resolution — sends it back to the same
    // technician instead of approving. Clears the previous resolution detail (the
    // tech's "Add Update" log survives independently and still drafts their next
    // Resolve attempt) and reopens the SLA clock exactly where it left off.
    public function requestRevision(Request $request, Tickets $ticket)
    {
        $request->validate([
            'revision_notes' => 'required|string|max:1000',
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        if ($ticket->status !== TicketStatus::REPORT_FOR_REVIEW
            || !in_array($ticket->assignedTo?->role?->role_name, ['IT Support Specialist', 'Helpdesk'], true)) {
            return back()->with('error', 'Only tickets pending approval can be sent back for revision.');
        }

        $oldStatus = $ticket->status;
        $technician = $ticket->assignedTo;

        $scheduleFields = [];

        if ($technician) {
            if ($activeTicket = $this->activeTicketFor($technician, $ticket->id)) {
                return back()->with(
                    'error',
                    "{$technician->name} is already working on ticket #{$activeTicket->ticket_number} — they need to resolve or escalate it before revising this one."
                );
            }

            $slot = TicketScheduler::commitDirectSlot(
                $ticket,
                $technician,
                TicketScheduler::effortMinutes($ticket),
                $request->input('schedule_decision', 'auto')
            );

            if ($slot['needs_decision']) {
                return back()->withInput()->with('scheduleConflict', [
                    'action_url' => route('supervisor.support.tickets.request-revision', $ticket),
                    'ticket_number' => $ticket->ticket_number,
                    'technician_name' => $technician->name,
                    'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                    'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                    'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                    'extra_fields' => $request->except(['_token', 'schedule_decision']),
                ]);
            }

            $scheduleFields = [
                'scheduled_start' => $slot['scheduled_start'],
                'scheduled_end' => $slot['scheduled_end'],
                'is_overtime' => $slot['is_overtime'],
                'queued_at' => $slot['queued_at'],
            ];
        }

        $ticket->update(array_merge([
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_notes' => null,
            'service_type' => null,
            'findings' => null,
            'recommendation' => null,
        ], $scheduleFields));

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => 'Resolution returned for revision by Supervisor - ' . Auth::user()->name .
                ($technician ? ". Returned to {$technician->name}." : '.') .
                " Reason: {$request->revision_notes}",
            'changed_at' => now(),
        ]);

        if ($technician?->email) {
            Mail::to($technician->email)->send(
                new TicketAssignedMail($ticket, 'Your Supervisor requested revisions on this resolution and has returned it to you.', 'technician.dashboard')
            );
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} returned to " . ($technician?->name ?? 'the technician') . ' for revision.');
    }

    // Escalates to either Supervisor - IT Admin (L3, the normal chain) or straight to
    // Manager (L4, skipping IT Admin entirely) — the Supervisor's own call when a
    // ticket is clearly above IT Admin's remit. L4 requires a reason since it's the
    // shortcut, not the default; L3 keeps its existing optional reason.
    public function escalateToAdmin(Request $request, Tickets $ticket)
    {
        $request->validate([
            'level' => 'nullable|in:L3,L4',
            'reason' => 'required_if:level,L4|nullable|string|max:1000',
        ]);

        $oldStatus = $ticket->status;
        $escalatingTech = $ticket->assignedTo;

        if ($request->input('level') === 'L4') {
            $ticket->update([
                'status' => TicketStatus::ESCALATED,
                'pending_role' => TicketStatus::QUEUE_MANAGER,
                'escalation_level' => $ticket->escalation_level + 1,
                'assigned_to' => null,
                'scheduled_start' => null,
                'scheduled_end' => null,
                'is_overtime' => false,
                'queued_at' => null,
            ]);

            if ($escalatingTech) {
                TicketScheduler::resequence($escalatingTech);
            }

            TicketStatusHistories::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => TicketStatus::ESCALATED,
                'changed_by' => Auth::id(),
                'notes' => 'Escalated directly to Manager (skipping IT Admin) by ' . Auth::user()->name . '. Reason: ' . $request->reason,
                'changed_at' => now(),
            ]);

            $managers = User::withActiveRole('Manager')->get();
            foreach ($managers as $manager) {
                if ($manager->email) {
                    Mail::to($manager->email)->send(
                        new TicketAssignedMail($ticket, 'A ticket has been escalated directly to you and needs acknowledgement.', 'executive.tickets.index')
                    );
                }
            }

            return back()->with('success', "Ticket #{$ticket->ticket_number} escalated directly to Manager.");
        }

        $ticket->update([
            'status' => TicketStatus::ESCALATED,
            'pending_role' => TicketStatus::QUEUE_ADMIN_SUPERVISOR,
            'escalation_level' => $ticket->escalation_level + 1,
            'assigned_to' => null,
            // Clear stale scheduling data — matches decline()'s cleanup.
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_overtime' => false,
            'queued_at' => null,
        ]);

        // Ticket just left the technician's active slot and not-started queue — their
        // remaining queued tickets need to slide to match reality.
        if ($escalatingTech) {
            TicketScheduler::resequence($escalatingTech);
        }

        $notes = 'Escalated to Supervisor - IT Admin by ' . Auth::user()->name;
        if ($request->filled('reason')) {
            $notes .= '. Reason: ' . $request->reason;
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::ESCALATED,
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

    // Escalated ticket investigated but not technically fixable — documents findings and
    // sends it straight to Awaiting Requestor (same as a normal resolve) rather than pushing
    // it further up to Admin, since the supervisor has already reviewed it.
    public function cannotResolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::ESCALATED) {
            return back()->with('error', 'Only escalated tickets can be marked as unresolvable.');
        }

        $request->validate([
            'findings' => 'required|string',
            'recommendation' => 'nullable|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'pending_role' => null,
            'resolved_at' => now(),
            'closed_at' => now(),
            'cannot_resolve' => true,
            'cannot_resolve_findings' => $request->findings,
            'cannot_resolve_recommendation' => $request->recommendation,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'changed_by' => Auth::id(),
            'notes' => 'Marked as unable to resolve by Supervisor - ' . Auth::user()->name .
                ". Findings & Analysis: {$request->findings}." .
                ($request->recommendation ? " Other Observations / Recommendation: {$request->recommendation}" : ''),
            'changed_at' => now(),
        ]);

        $this->notifyHelpdeskFyi($ticket, 'Marked as unable to resolve by Supervisor - ' . Auth::user()->name . '.');

        return back()->with('success', "Ticket #{$ticket->ticket_number} marked as unable to resolve. Requestor notified.");
    }

    // A technician has proposed a corrected classification for a miscategorized ticket —
    // accept it as the ticket's new default and drop it back into the normal unassigned
    // classify/assign queue (the same one Helpdesk-acknowledged tickets land in). No separate
    // technician-picker here: the Classify & Assign modal already pre-fills from whatever
    // classification is currently on the ticket, so the proposal just becomes the editable
    // default there.
    // Reclassification is tracked solely on reclassification_requests now — the
    // ticket's own status/assignee were never touched when the request was
    // raised (it stayed wherever it was, same technician still assigned,
    // working normally). Approving applies the corrected classification
    // in place; nothing about ownership changes (the supervisor can always
    // separately reassign() if they also want a different tech).
    public function approveReclassification(Request $request, Tickets $ticket)
    {
        $pending = ReclassificationRequest::where('ticket_id', $ticket->id)
            ->where('status', 'pending')
            ->latest('requested_at')
            ->first();

        if (!$pending) {
            return back()->with('error', 'No pending re-classification request found for this ticket.');
        }

        $request->validate([
            'review_notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;
        $previousAssignee = $ticket->assignedTo;

        // Approving re-drops the ticket back into this supervisor's own
        // Classified/unassigned queue for (re-)assignment — same destination
        // Helpdesk-acknowledged tickets land in — rather than leaving it with
        // whichever technician it happened to be with when the reclassification
        // was requested (they may no longer be the right fit under the new
        // classification).
        $ticket->update([
            'sla_category_id' => $pending->proposed_sla_category_id,
            'subcategory_name' => $pending->proposed_subcategory_name,
            'ticket_type' => $pending->proposed_priority,
            'response_time_minutes' => $pending->proposed_response_time_minutes,
            'resolution_time_minutes' => $pending->proposed_resolution_time_minutes,
            'status' => TicketStatus::CLASSIFIED,
            'assigned_to' => null,
            'pending_role' => TicketStatus::QUEUE_SUPPORT_SUPERVISOR,
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_overtime' => false,
            'queued_at' => null,
        ]);

        // Ticket just left the previous assignee's active slot and/or not-started
        // queue — their remaining queued tickets need to slide to match reality.
        if ($previousAssignee) {
            TicketScheduler::resequence($previousAssignee);
        }

        $pending->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CLASSIFIED,
            'changed_by' => Auth::id(),
            'notes' => 'Re-classification approved by Supervisor - ' . Auth::user()->name .
                ". Now classified as {$pending->proposed_subcategory_name} ({$pending->proposed_priority}). Ready for (re-)assignment."
                . ($request->review_notes ? " Note: {$request->review_notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Re-classification approved for ticket #{$ticket->ticket_number}. Ready to assign.");
    }

    // Supervisor disagrees with the proposed re-classification — nothing about
    // the ticket changes (it was never touched), just the rejection reason
    // attached for the technician's reference.
    public function rejectReclassification(Request $request, Tickets $ticket)
    {
        $pending = ReclassificationRequest::where('ticket_id', $ticket->id)
            ->where('status', 'pending')
            ->latest('requested_at')
            ->first();

        if (!$pending) {
            return back()->with('error', 'No pending re-classification request found for this ticket.');
        }

        $request->validate([
            'review_notes' => 'required|string|max:500',
        ]);

        $technician = User::findOrFail($pending->requested_by);

        $pending->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => Auth::id(),
            'notes' => 'Re-classification rejected by Supervisor - ' . Auth::user()->name .
                ". Reason: {$request->review_notes}",
            'changed_at' => now(),
        ]);

        if ($technician->email) {
            Mail::to($technician->email)->send(
                new TicketAssignedMail($ticket, 'Your re-classification request was declined by your Supervisor.', 'technician.dashboard')
            );
        }

        return back()->with('success', "Re-classification rejected for ticket #{$ticket->ticket_number}.");
    }
    // Support Supervisor Closing

    // ── Admin track equivalents of approveReclassification()/rejectReclassification()
    // above — same mechanics, just the Admin Supervisor's own queue
    // (QUEUE_ADMIN_SUPERVISOR) and notification target instead of Support's.
    public function adminApproveReclassification(Request $request, Tickets $ticket)
    {
        $pending = ReclassificationRequest::where('ticket_id', $ticket->id)
            ->where('status', 'pending')
            ->latest('requested_at')
            ->first();

        if (!$pending) {
            return back()->with('error', 'No pending re-classification request found for this ticket.');
        }

        $request->validate([
            'review_notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;
        $previousAssignee = $ticket->assignedTo;

        $ticket->update([
            'sla_category_id' => $pending->proposed_sla_category_id,
            'subcategory_name' => $pending->proposed_subcategory_name,
            'ticket_type' => $pending->proposed_priority,
            'response_time_minutes' => $pending->proposed_response_time_minutes,
            'resolution_time_minutes' => $pending->proposed_resolution_time_minutes,
            'status' => TicketStatus::CLASSIFIED,
            'assigned_to' => null,
            'pending_role' => TicketStatus::QUEUE_ADMIN_SUPERVISOR,
            'scheduled_start' => null,
            'scheduled_end' => null,
            'is_overtime' => false,
            'queued_at' => null,
        ]);

        if ($previousAssignee) {
            TicketScheduler::resequence($previousAssignee);
        }

        $pending->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CLASSIFIED,
            'changed_by' => Auth::id(),
            'notes' => 'Re-classification approved by Admin Supervisor - ' . Auth::user()->name .
                ". Now classified as {$pending->proposed_subcategory_name} ({$pending->proposed_priority}). Ready for (re-)assignment."
                . ($request->review_notes ? " Note: {$request->review_notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Re-classification approved for ticket #{$ticket->ticket_number}. Ready to assign.");
    }

    public function adminRejectReclassification(Request $request, Tickets $ticket)
    {
        $pending = ReclassificationRequest::where('ticket_id', $ticket->id)
            ->where('status', 'pending')
            ->latest('requested_at')
            ->first();

        if (!$pending) {
            return back()->with('error', 'No pending re-classification request found for this ticket.');
        }

        $request->validate([
            'review_notes' => 'required|string|max:500',
        ]);

        $admin = User::findOrFail($pending->requested_by);

        $pending->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => Auth::id(),
            'notes' => 'Re-classification rejected by Admin Supervisor - ' . Auth::user()->name .
                ". Reason: {$request->review_notes}",
            'changed_at' => now(),
        ]);

        if ($admin->email) {
            Mail::to($admin->email)->send(
                new TicketAssignedMail($ticket, 'Your re-classification request was declined by your Admin Supervisor.', 'admin.dashboard')
            );
        }

        return back()->with('success', "Re-classification rejected for ticket #{$ticket->ticket_number}.");
    }
    // Admin Supervisor Closing

    // Admin Supervisor Openning
    public function index(Request $request)
    {
        // 'awaiting-admin-supervisor' (unacknowledged Escalated tickets) is no
        // longer a navigable tab on this dashboard — the Acknowledge action still
        // works per-ticket, but landing here with no ?status defaults to the next
        // queue instead of a hidden one.
        $status = $request->filled('status')
            ? $request->get('status')
            : 'awaiting-admin-classification';

        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy', 'reclassificationRequests', 'attachments'])
            ->orderByRaw("CASE
            WHEN status = 'Escalated' AND pending_role = 'Supervisor - IT Admin' THEN 1
            WHEN status = 'For Acknowledgment' AND pending_role = 'Supervisor - IT Admin' THEN 2
            WHEN status = 'Classified' THEN 3
            WHEN status = 'Assigned' THEN 4
            WHEN status = 'In Progress Service Request' THEN 5
            WHEN status = 'In Progress Service Report' THEN 6
            WHEN status = 'Report For Review' THEN 7
            WHEN status = 'Closed' THEN 8
            WHEN status = 'Cancelled' THEN 9
            ELSE 9 END");

        // In Progress – Service Report / Done – Service Report are shared across every
        // resolver track (see TicketReportProgress) — scoped to this Admin track's own
        // resolvers here so a Support-track ticket's drafting/done state doesn't bleed
        // into this queue. Includes 'Supervisor - IT Admin' itself — the take-over path
        // in adminClassifyAndAssign()/adminTakeover() assigns the ticket to the
        // supervisor directly, so without it here a taken-over ticket would vanish from
        // this supervisor's own In Progress / In Progress Report queues the moment they
        // took it over (mirrors $supportResolverRoles in supportIndex() above).
        $adminRoles = ['IT Admin', 'Supervisor - IT Admin'];

        // 'active' = everything still in flight across the admin escalation pipeline.
        // For Acknowledgment/Classified/Escalated are shared across every track now —
        // scoped to this Admin Supervisor's own queue via pending_role.
        if ($status === 'active') {
            $query->where(function ($q) use ($adminRoles) {
                $q->where(fn ($q2) => $q2->whereIn('status', [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::CLASSIFIED, TicketStatus::ESCALATED])->where('pending_role', TicketStatus::QUEUE_ADMIN_SUPERVISOR))
                    ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::ASSIGNED, $adminRoles))
                    ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::IN_PROGRESS_SERVICE_REQUEST, $adminRoles))
                    ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::ON_HOLD, $adminRoles))
                    ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::IN_PROGRESS_SERVICE_REPORT, $adminRoles))
                    ->orWhere(fn ($q2) => TicketReportProgress::forRoles($q2, TicketStatus::REPORT_FOR_REVIEW, $adminRoles))
                    // Requestor Confirmation has no pending_role left to scope by
                    // (the ticket is fully resolved by this point) — track it by
                    // who actually resolved it instead, so another track's
                    // Requestor Confirmation tickets (e.g. L2's) don't leak in here.
                    ->orWhere(fn ($q2) => $q2->where('status', TicketStatus::REQUESTOR_CONFIRMATION)
                        ->whereHas('resolvedBy.role', fn ($q3) => $q3->whereIn('role_name', $adminRoles)));
            });
        } elseif ($status === 'admin-in-progress') {
            TicketReportProgress::forRoles($query, TicketStatus::IN_PROGRESS_SERVICE_REQUEST, $adminRoles);
        } elseif ($status === 'admin-on-hold') {
            TicketReportProgress::forRoles($query, TicketStatus::ON_HOLD, $adminRoles);
        } elseif ($status === 'admin-in-progress-report') {
            TicketReportProgress::forRoles($query, TicketStatus::IN_PROGRESS_SERVICE_REPORT, $adminRoles);
        } elseif ($status === 'pending-admin-supervisor-approval') {
            TicketReportProgress::forRoles($query, TicketStatus::REPORT_FOR_REVIEW, $adminRoles);
        } elseif ($status === 'pending-reclassification') {
            // Scoped to the Admin track's own assignees — reclassification_requests
            // has no track field of its own, so without this an L2 technician's
            // pending request would otherwise leak into this queue too.
            $query->whereHas('reclassificationRequests', fn ($q) => $q->where('status', 'pending'))
                ->whereHas('assignedTo.role', fn ($q) => $q->whereIn('role_name', $adminRoles));
        } elseif ($status !== 'all') {
            $mappedStatus = match ($status) {
                'awaiting-admin-supervisor' => TicketStatus::ESCALATED,
                'awaiting-admin-classification' => TicketStatus::FOR_ACKNOWLEDGMENT,
                'awaiting-administrator-ack' => TicketStatus::ASSIGNED,
                'awaiting-administrator-sla-start' => TicketStatus::ASSIGNED,
                'closed' => TicketStatus::CLOSED,
                'cancelled' => TicketStatus::CANCELLED,
                default => null
            };

            if ($status === 'awaiting-admin-supervisor') {
                $query->where('status', TicketStatus::ESCALATED)->where('pending_role', TicketStatus::QUEUE_ADMIN_SUPERVISOR)->whereNull('assigned_to');
            } elseif ($status === 'awaiting-admin-classification') {
                // Includes both a fresh admin-only ticket routed straight in by
                // Helpdesk (already Classified, just needs assigning) and a
                // ticket escalated up to this queue that's been acknowledged and
                // still needs a first classification (For Acknowledgment).
                $query->whereIn('status', [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::CLASSIFIED])
                    ->where('pending_role', TicketStatus::QUEUE_ADMIN_SUPERVISOR)
                    ->whereNull('assigned_to');
            } elseif ($status === 'awaiting-requestor') {
                // Scoped by resolver, same reasoning as the 'active' branch above —
                // Requestor Confirmation has no pending_role left to key off of.
                $query->where('status', TicketStatus::REQUESTOR_CONFIRMATION)
                    ->whereHas('resolvedBy.role', fn ($q) => $q->whereIn('role_name', $adminRoles));
            } elseif (in_array($status, ['awaiting-administrator-ack', 'awaiting-administrator-sla-start'], true)) {
                // Assigned has no pending_role left to key off of (see other branches
                // above) — scoped by assignee's role instead, same as the counts below,
                // so an L2 (IT Support Specialist) ticket can't leak into this L3-only queue.
                TicketReportProgress::forRoles($query, TicketStatus::ASSIGNED, $adminRoles);
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
                    WHEN ticket_type = 'Critical' THEN 1
                    WHEN ticket_type = 'High' THEN 2
                    WHEN ticket_type = 'Medium' THEN 3
                    WHEN ticket_type = 'Low' THEN 4
                    ELSE 5
                END
            ");
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $tickets = $query->paginate(10)->withQueryString();

        $counts = [
            'awaiting_admin_supervisor' => Tickets::where('status', TicketStatus::ESCALATED)->where('pending_role', TicketStatus::QUEUE_ADMIN_SUPERVISOR)->whereNull('assigned_to')->count(),
            'awaiting_admin_classification' => Tickets::whereIn('status', [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::CLASSIFIED])
                ->where('pending_role', TicketStatus::QUEUE_ADMIN_SUPERVISOR)
                ->whereNull('assigned_to')
                ->count(),
            'awaiting_administrator_ack' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::ASSIGNED, $adminRoles)->count(),
            'awaiting_administrator_sla_start' => 0, // collapsed into 'Assigned' — kept for view compatibility, see awaiting_administrator_ack
            'admin_in_progress' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::IN_PROGRESS_SERVICE_REQUEST, $adminRoles)->count(),
            'admin_on_hold' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::ON_HOLD, $adminRoles)->count(),
            'admin_in_progress_report' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::IN_PROGRESS_SERVICE_REPORT, $adminRoles)->count(),
            'pending_admin_supervisor_approval' => TicketReportProgress::forRoles(Tickets::query(), TicketStatus::REPORT_FOR_REVIEW, $adminRoles)->count(),
            'pending_admin_reclassification' => Tickets::whereHas('reclassificationRequests', fn ($q) => $q->where('status', 'pending'))
                ->whereHas('assignedTo.role', fn ($q) => $q->whereIn('role_name', $adminRoles))
                ->count(),
            'awaiting_requestor' => Tickets::where('status', TicketStatus::REQUESTOR_CONFIRMATION)
                ->whereHas('resolvedBy.role', fn ($q) => $q->whereIn('role_name', $adminRoles))
                ->count(),
            'closed' => Tickets::where('status', TicketStatus::CLOSED)->count(),
            'cancelled' => Tickets::where('status', TicketStatus::CANCELLED)->count(),
        ];
        $counts['active'] = $counts['awaiting_admin_supervisor'] + $counts['awaiting_admin_classification']
            + $counts['awaiting_administrator_ack'] + $counts['awaiting_administrator_sla_start']
            + $counts['admin_in_progress'] + $counts['admin_on_hold'] + $counts['admin_in_progress_report'] + $counts['pending_admin_supervisor_approval']
            + $counts['awaiting_requestor'] + $counts['pending_admin_reclassification'];

        $technicians = User::whereHas(
            'role',
            fn($q) =>
            $q->where('role_name', 'IT Admin')
        )
            ->withCount([
                'assignedTickets as active_tickets' => fn($q) =>
                    $q->whereIn('status', [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::ASSIGNED])
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

        // Full IT team availability for the sidebar widget — broader than
        // $technicians above (IT Admins only, kept scoped for the classify/assign
        // and reassign modals since those are the only valid assignees for an
        // Admin-track ticket). This is presence-based (online in the last 5 min),
        // same mechanism as the Employee dashboard's "Available IT" panel, since
        // Helpdesk/Technician tracks don't share a ticket-count or schedule gauge
        // with the Admin track.
        $itTeamOnlineIds = DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->distinct()
            ->pluck('user_id');

        $itTeamAvailability = User::with('role')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', [
                'Helpdesk',
                'IT Support Specialist',
                'Supervisor - Support Specialist',
                'IT Admin',
                'Supervisor - IT Admin',
            ]))
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($member) use ($itTeamOnlineIds) {
                $member->online = $itTeamOnlineIds->contains($member->id);
                return $member;
            })
            ->sortByDesc('online')
            ->values();

        $slaCategories = SlaCategory::with([
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
            'icon' => $c->icon,
            'color' => $c->color,
            'subs' => $c->rules->map(fn($r) => [
                'rule_id' => $r->id,
                'name' => $r->subcategory_name,
                'priority' => $r->priority,
                'response' => $r->response_time_minutes,
                'resolution' => $r->resolution_time_minutes,
                'description' => $r->description,
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

        $workloadClassesJson = \App\Models\WorkloadClass::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'name' => $w->name,
                'response_minutes' => $w->response_minutes,
                'resolution_minutes' => $w->resolution_minutes,
                'requires_manual_resolution' => $w->requires_manual_resolution,
            ])->values()->toArray();

        return view('dashboard.admin.dashboard', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'technicians',
            'itTeamAvailability',
            'slaCategories',
            'slaCategoriesJson',
            'users',
            'workloadClassesJson'
        ));
    }

    public function adminAcknowledge(Tickets $ticket)
    {
        // This queue mixes two origins that both land on this supervisor's desk:
        // a fresh admin-only ticket routed directly here (FOR_ACKNOWLEDGMENT),
        // or a ticket actually escalated up to this tier (ESCALATED). Either
        // way, acknowledging it moves/settles it into the normal classification
        // queue — no separate "acknowledged but not yet classified" status.
        // The double-click/rapid-resubmit race this used to also guard against
        // (via a lifetime scan for a prior "Acknowledged by Admin Supervisor"
        // note) is already closed by the idempotent:8 middleware on this route —
        // that lifetime scan wrongly locked out any ticket escalated to this
        // queue more than once, since it never expires with the escalation
        // cycle that made it true.
        if (!in_array($ticket->status, [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::ESCALATED], true)
            || $ticket->pending_role !== TicketStatus::QUEUE_ADMIN_SUPERVISOR) {
            return back()->with('error', 'This ticket is not awaiting admin supervisor acknowledgment.');
        }

        $oldStatus = $ticket->status;

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::FOR_ACKNOWLEDGMENT,
            'changed_by' => Auth::id(),
            'notes' => 'Acknowledged by Admin Supervisor - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        $ticket->update(['status' => TicketStatus::FOR_ACKNOWLEDGMENT]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} acknowledged."
        );
    }

    public function adminClassifyAndAssign(Request $request, Tickets $ticket)
    {
        $request->validate([
            // Helpdesk's classification is authoritative by default (fresh
            // admin-only tickets land here already Classified) — only required
            // when actually overriding it, or when finishing off a ticket that
            // was escalated up to this queue (still For Acknowledgment) and
            // genuinely does need a first classification.
            'sla_rule_id' => 'nullable|exists:sla_rules,id',
            // Either an IT Admin to assign to, or take_over to keep it — never
            // both, enforced below the same way as the Support Supervisor's own
            // classifyAndAssign() (see there).
            'technician_id' => 'nullable|uuid|exists:users,id',
            'take_over' => 'nullable|boolean',
            'priority' => 'nullable|in:Critical,High,Medium,Low',
            'workload_class_id' => 'nullable|exists:workload_classes,id',
            'response_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'resolution_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'notes' => 'nullable|string|max:500',
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        if (!in_array($ticket->status, [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::CLASSIFIED], true)
            || $ticket->pending_role !== TicketStatus::QUEUE_ADMIN_SUPERVISOR) {
            return back()->with('error', 'Ticket must be classified before it can be assigned.');
        }

        // A ticket escalated up to this queue (arrives here via For
        // Acknowledgment, after adminAcknowledge()) genuinely hasn't been
        // classified yet and needs a real SLA rule pick.
        if ($ticket->status === TicketStatus::FOR_ACKNOWLEDGMENT && !$request->filled('sla_rule_id')) {
            return back()->withInput()->with('error', 'This ticket must be classified — select an SLA rule.');
        }

        $takeOver = $request->boolean('take_over');

        if (!$takeOver && !$request->filled('technician_id')) {
            return back()->withInput()->with('error', 'Select an IT Admin to assign, or choose to take the ticket over yourself.');
        }

        $technician = $takeOver ? null : User::findOrFail($request->technician_id);
        $slaRule = $request->filled('sla_rule_id') ? SlaRule::findOrFail($request->sla_rule_id) : null;
        $workloadClass = $request->filled('workload_class_id')
            ? \App\Models\WorkloadClass::find($request->workload_class_id)
            : null;

        if ($slaRule) {
            $resolved = TicketSlaResolution::resolve(
                $slaRule,
                $workloadClass,
                $request->priority,
                $request->filled('response_time_minutes') ? (int) $request->response_time_minutes : null,
                $request->filled('resolution_time_minutes') ? (int) $request->resolution_time_minutes : null,
            );

            if (isset($resolved['error'])) {
                return back()->with('error', $resolved['error']);
            }

            ['priority' => $priority, 'response_time_minutes' => $responseTime, 'resolution_time_minutes' => $resolutionTime] = $resolved;
        } else {
            $priority = $ticket->ticket_type;
            $responseTime = $ticket->response_time_minutes;
            $resolutionTime = $ticket->resolution_time_minutes;
        }

        // ── Taking it over means starting work now, not queueing behind other
        // not-started tickets — same immediate-anchor-slot mechanism the Support
        // Supervisor's own classifyAndAssign() and adminTakeover() below use.
        if ($takeOver) {
            $supervisor = Auth::user();

            if ($activeTicket = $this->activeTicketFor($supervisor, $ticket->id)) {
                return back()->with(
                    'error',
                    "You're already working on ticket #{$activeTicket->ticket_number} — resolve or escalate it before taking over another."
                );
            }

            $slot = TicketScheduler::commitDirectSlot(
                $ticket,
                $supervisor,
                $responseTime + $resolutionTime,
                $request->input('schedule_decision', 'auto')
            );
        } else {
            $slot = TicketScheduler::commitAssignment(
                $ticket,
                $technician,
                $priority,
                $responseTime + $resolutionTime,
                $request->input('schedule_decision', 'auto')
            );
        }

        if ($slot['needs_decision']) {
            return back()->withInput()->with('scheduleConflict', [
                'action_url' => route('supervisor.tickets.admin-classify-assign', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $takeOver ? Auth::user()->name : $technician->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $now = now();
        $oldStatus = $ticket->status;

        if ($slaRule) {
            $overrideNote = TicketSlaResolution::overrideNote($slaRule, $priority, $responseTime, $resolutionTime, $workloadClass);

            $ticket->update([
                'sla_category_id' => $slaRule->sla_category_id,
                'subcategory_name' => $slaRule->subcategory_name,
                'workload_class_id' => $workloadClass?->id,
                'ticket_type' => $priority,
                'response_time_minutes' => $responseTime,
                'resolution_time_minutes' => $resolutionTime,
                'status' => TicketStatus::CLASSIFIED,
            ]);

            TicketStatusHistories::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => TicketStatus::CLASSIFIED,
                'changed_by' => Auth::id(),
                'notes' => ($oldStatus === TicketStatus::FOR_ACKNOWLEDGMENT ? 'Classified' : 'Classification overridden')
                    . " as {$slaRule->subcategory_name} ({$priority})." . $overrideNote,
                'changed_at' => $now,
            ]);
        }

        if ($takeOver) {
            $ticket->update([
                'assigned_to' => Auth::id(),
                'assigned_at' => $now,
                'pending_role' => null,
                'status' => TicketStatus::ASSIGNED,
                'scheduled_start' => $slot['scheduled_start'],
                'scheduled_end' => $slot['scheduled_end'],
                'is_overtime' => $slot['is_overtime'],
                'queued_at' => $slot['queued_at'],
            ]);

            TicketStatusHistories::create([
                'ticket_id' => $ticket->id,
                'old_status' => TicketStatus::CLASSIFIED,
                'new_status' => TicketStatus::ASSIGNED,
                'changed_by' => Auth::id(),
                'notes' => 'Taken over directly by ' . Auth::user()->name . '.'
                    . ($request->notes ? " Note: {$request->notes}" : ''),
                'changed_at' => $now,
            ]);

            $ticket->update([
                'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
                'started_at' => $now, // SLA resolution clock starts here
                'sla_due_at' => $resolutionTime
                    ? BusinessClock::addBusinessMinutes($now->copy(), $resolutionTime)
                    : null,
                'sla_risk_notified_at' => null,
                'sla_breached_notified_at' => null,
            ]);

            TicketStatusHistories::create([
                'ticket_id' => $ticket->id,
                'old_status' => TicketStatus::ASSIGNED,
                'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
                'changed_by' => Auth::id(),
                'notes' => 'Work started immediately on take-over.',
                'changed_at' => $now,
            ]);

            return back()->with('success', "Ticket #{$ticket->ticket_number} classified and taken over.");
        }

        $ticket->update([
            'assigned_to' => $technician->id,
            'assigned_at' => $now,
            'pending_role' => null,
            'status' => TicketStatus::ASSIGNED,
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::CLASSIFIED,
            'new_status' => TicketStatus::ASSIGNED,
            'changed_by' => Auth::id(),
            'notes' => "Assigned to {$technician->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => $now,
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
                'action_url' => route('supervisor.tickets.reassign', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $newTech->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'assigned_at' => now(),
            'pending_role' => null,
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
        ]);

        if ($oldTechUser && $oldTechUser->id !== $newTech->id) {
            TicketScheduler::resequence($oldTechUser);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Reassignment',
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => "Reassigned from {$oldTech} to {$newTech->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket reassigned to {$newTech->name}.");
    }

    public function adminTakeover(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::ASSIGNED) {
            return back()->with('error', 'Only tickets awaiting SLA start or acknowledgment can be taken over/started.');
        }

        $request->validate([
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        $supervisor = Auth::user();

        if ($activeTicket = $this->activeTicketFor($supervisor, $ticket->id)) {
            return back()->with(
                'error',
                "You're already working on ticket #{$activeTicket->ticket_number} — resolve or escalate it before taking over another."
            );
        }

        $slot = TicketScheduler::commitDirectSlot(
            $ticket,
            $supervisor,
            TicketScheduler::effortMinutes($ticket),
            $request->input('schedule_decision', 'auto')
        );

        if ($slot['needs_decision']) {
            return back()->withInput()->with('scheduleConflict', [
                'action_url' => route('supervisor.tickets.takeover', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $supervisor->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $oldStatus = $ticket->status;
        $startedAt = $ticket->started_at ?? now();
        $resolutionMinutes = $ticket->effectiveResolutionTimeMinutes();

        $ticket->update([
            'assigned_to' => Auth::id(),
            'assigned_at' => now(),
            'pending_role' => null,
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'started_at' => $startedAt, // SLA resolution clock starts here
            'sla_due_at' => $resolutionMinutes
                ? BusinessClock::addBusinessMinutes($startedAt->copy(), $resolutionMinutes)
                : null,
            'sla_risk_notified_at' => null,
            'sla_breached_notified_at' => null,
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

        // Report For Review is shared across every resolver track (see
        // TicketReportProgress) — restricted to 'IT Admin' here so this supervisor
        // can't validate a Support-track ticket via a direct route hit. This is
        // the report-approval gate: cascades Report For Review ->
        // Approved Service Report -> Requestor Confirmation.
        if ($ticket->status !== TicketStatus::REPORT_FOR_REVIEW
            || $ticket->assignedTo?->role?->role_name !== 'IT Admin') {
            return back()->with('error', 'Only tickets currently in progress can be validated.');
        }

        $now = now();

        $ticket->update([
            'status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'validated_at' => $now,
            'validation_notes' => $request->validation_notes,
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::REPORT_FOR_REVIEW,
            'new_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'changed_by' => Auth::id(),
            'notes' => 'Resolution validated and closed by Admin Supervisor - ' . Auth::user()->name . ". {$request->validation_notes}",
            'changed_at' => $now,
        ]);

        $ticket->update([
            'status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'closed_at' => $now,
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'new_status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'changed_by' => Auth::id(),
            'notes' => 'Resolution validated and closed by Admin Supervisor - ' . Auth::user()->name . ". {$request->validation_notes}",
            'changed_at' => $now,
        ]);

        $this->notifyHelpdeskFyi($ticket, 'Resolution validated by Supervisor - IT Admin - ' . Auth::user()->name . '.');

        return back()->with('success', "Ticket #{$ticket->ticket_number} resolved. Requestor notified.");
    }

    // Mirrors requestRevision() above, for the Admin escalation track — Supervisor -
    // IT Admin sending an IT Admin's resolution back instead of approving it.
    public function adminRequestRevision(Request $request, Tickets $ticket)
    {
        $request->validate([
            'revision_notes' => 'required|string|max:1000',
        ]);

        if ($ticket->status !== TicketStatus::REPORT_FOR_REVIEW
            || $ticket->assignedTo?->role?->role_name !== 'IT Admin') {
            return back()->with('error', 'Only tickets pending approval can be sent back for revision.');
        }

        $oldStatus = $ticket->status;
        $admin = $ticket->assignedTo;

        $ticket->update([
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'resolved_at' => null,
            'resolved_by' => null,
            'resolution_notes' => null,
            'service_type' => null,
            'findings' => null,
            'recommendation' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => 'Resolution returned for revision by Admin Supervisor - ' . Auth::user()->name .
                ($admin ? ". Returned to {$admin->name}." : '.') .
                " Reason: {$request->revision_notes}",
            'changed_at' => now(),
        ]);

        if ($admin?->email) {
            Mail::to($admin->email)->send(
                new TicketAssignedMail($ticket, 'Your Supervisor requested revisions on this resolution and has returned it to you.', 'admin.dashboard')
            );
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} returned to " . ($admin?->name ?? 'the IT Admin') . ' for revision.');
    }

    // Assigned -> In Progress Service Request, and starts the SLA clock.
    // Starting work (and the SLA clock with it) is the assigned IT Admin's own call,
    // not the supervisor's — this route only exists for the supervisor's own
    // take-over tickets, which never actually rest at ASSIGNED (adminTakeover()/
    // adminClassifyAndAssign()'s take_over path jump straight to In Progress). So
    // in practice this only ever fires for the supervisor's own ticket; guard it
    // explicitly rather than relying on that never changing.
    public function adminStartSla(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::ASSIGNED) {
            return back()->with('error', 'This ticket is not awaiting SLA start.');
        }

        if ($ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only the assigned IT Admin can start work on this ticket.');
        }

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
            'notes' => 'Work started, SLA clock started - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "SLA clock started for ticket #{$ticket->ticket_number}.");
    }

    // Pause/Resume — for when finishing the ticket needs more information from
    // the requestor. Restricted to the Supervisor's own take-over ticket — an IT
    // Admin's own ticket stays theirs to pause/resume via their own dashboard
    // (Admin\TicketController::pause()/resume()), same "only the assignee" rule
    // as adminStartSla() above. See TicketHold.
    public function adminPause(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REQUEST) {
            return back()->with('error', 'Only in-progress tickets can be paused.');
        }

        if ($ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only your own in-progress tickets can be paused here.');
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        TicketHold::pause($ticket, $request->reason);

        return back()->with('success', "Ticket #{$ticket->ticket_number} paused. The requestor has been notified.");
    }

    public function adminResume(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::ON_HOLD) {
            return back()->with('error', 'This ticket is not on hold.');
        }

        if ($ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only your own tickets can be resumed here.');
        }

        TicketHold::resume($ticket);

        return back()->with('success', "Work resumed on ticket #{$ticket->ticket_number}.");
    }

    // The technical fix is done, separate from writing it up (see
    // TicketReportProgress) — mirrors supportStartReport() above, restricted to
    // the Supervisor's own take-over ticket (see adminPause() above for why).
    public function adminStartReport(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REQUEST || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only your own in-progress tickets can be marked fixed.');
        }

        TicketReportProgress::markStarted($ticket);

        return back()->with('success', "Ticket #{$ticket->ticket_number} marked fixed — prepare the service report when ready.");
    }

    // Mark resolved — self-approval track: the Supervisor resolving their own
    // take-over ticket has no reviewer above them (mirrors supportResolve() /
    // Manager\TicketController::resolve()), so this cascades the entire
    // remaining chain (Done Service Report -> Report For Review -> Approved
    // Service Report -> Requestor Confirmation) in one action. Restricted to
    // the Supervisor's own ticket — an IT Admin's own ticket stays theirs to
    // resolve via their own dashboard (Admin\TicketController::resolve()).
    // Only reachable after adminStartReport() (In Progress Service Report).
    public function adminResolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REPORT || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Mark the ticket fixed before preparing the service report.');
        }

        $request->validate(array_merge(TicketResolutionRules::BASE, [
            'findings' => 'nullable|string',
            'recommendation' => 'nullable|string',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]));

        $now = now();
        $oldStatus = $ticket->status;
        $actor = 'Supervisor - IT Admin - ' . Auth::user()->name;

        $ticket->update([
            'status' => TicketStatus::DONE_SERVICE_REPORT,
            'resolved_at' => $now,
            'resolved_by' => Auth::id(),
            'resolution_notes' => $request->resolution_notes,
            'service_type' => $request->service_type,
            'findings' => $request->findings,
            'recommendation' => $request->recommendation,
        ]);

        $timeSpent = $ticket->actualResolutionTime() ?? 'unknown';
        $resolutionNote = "Resolved by {$actor}. Time spent: {$timeSpent}. " . $request->resolution_notes;

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
            'ticket_id' => $ticket->id, 'old_status' => $oldStatus, 'new_status' => TicketStatus::DONE_SERVICE_REPORT,
            'changed_by' => Auth::id(), 'notes' => $resolutionNote, 'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::REPORT_FOR_REVIEW]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id, 'old_status' => TicketStatus::DONE_SERVICE_REPORT, 'new_status' => TicketStatus::REPORT_FOR_REVIEW,
            'changed_by' => Auth::id(), 'notes' => 'Service report submitted.', 'changed_at' => $now,
        ]);

        $ticket->update([
            'status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'validated_at' => $now,
            'validation_notes' => "Self-approved — resolved directly by the Admin Supervisor ({$actor}).",
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id, 'old_status' => TicketStatus::REPORT_FOR_REVIEW, 'new_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'changed_by' => Auth::id(), 'notes' => "Self-approved by {$actor} — no reviewer above the Admin Supervisor take-over.", 'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::REQUESTOR_CONFIRMATION, 'closed_at' => $now]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id, 'old_status' => TicketStatus::APPROVED_SERVICE_REPORT, 'new_status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'changed_by' => Auth::id(), 'notes' => $resolutionNote, 'changed_at' => $now,
        ]);

        $this->notifyHelpdeskFyi($ticket, 'Resolved by Supervisor - IT Admin - ' . Auth::user()->name . '.');

        return back()->with('success', "Ticket #{$ticket->ticket_number} resolved. Requestor notified.");
    }

    public function escalateToManager(Request $request, Tickets $ticket)
    {
        if (!in_array($ticket->status, [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT], true)) {
            return back()->with('error', 'Only tickets you are actively working on can be escalated to the Manager.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => TicketStatus::ESCALATED,
            'pending_role' => TicketStatus::QUEUE_MANAGER,
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
            'new_status' => TicketStatus::ESCALATED,
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