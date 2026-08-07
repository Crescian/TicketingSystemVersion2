<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Mail\TicketAssignedMail;
use Illuminate\Http\Request;
use App\Models\Tickets;
use App\Models\User;
use App\Models\SlaRule;
use App\Models\SlaCategory;
use App\Models\ReclassificationRequest;
use App\Models\TicketStatusHistories;
use App\Services\TicketScheduler;
use App\Support\BusinessClock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupervisorDashboardController extends Controller
{
    // The time-slot queue assumes serialized work — a technician can only ever be
    // actively working one ticket at a time (same rule as
    // Technician\TicketController::start()'s hard block). reassign()/takeover()/
    // requestRevision()/rejectReclassification() all put a ticket directly into
    // 'In Progress' for a specific technician, bypassing that check entirely, so each
    // needs its own guard before doing so.
    private function activeTicketFor(User $technician, ?string $excludeTicketId = null): ?Tickets
    {
        return Tickets::where('assigned_to', $technician->id)
            ->where('status', 'In Progress')
            ->when($excludeTicketId, fn ($q) => $q->where('id', '!=', $excludeTicketId))
            ->first();
    }

    // Support Supervisor Openning
    public function supportIndex(Request $request)
    {
        $status = $request->filled('status')
            ? $request->get('status')
            : 'awaiting-classification';

        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy', 'reclassificationRequests']);

        // 'active' = everything still in flight for this supervisor, before it lands on Closed —
        // includes the post-validation states (Pending Closure / Awaiting Requestor) so a
        // ticket the supervisor validated doesn't disappear from view until it's truly Closed.
        // Also includes Awaiting Support Specialist Acknowledgement — the ticket the
        // supervisor just classified & assigned shouldn't vanish from their queue the
        // moment they hand it off; they still need to see it until the tech acts on it.
        $activeStatuses = [
            'Awaiting Supervisor', 'Escalated', 'Awaiting Support Specialist Acknowledgement', 'In Progress',
            'Pending Reclassification', 'Pending Supervisor Approval', 'Pending Closure', 'Awaiting Requestor',
        ];

        switch ($status) {
            case 'active':
                $query->whereIn('status', $activeStatuses);
                break;
            case 'awaiting-classification':
                $query->whereIn('status', ['Awaiting Supervisor', 'Escalated'])
                    ->whereNotNull('date_acknowledged')
                    ->whereNull('assigned_to');
                break;
            case 'awaiting-tech-ack':
                $query->where('status', 'Awaiting Support Specialist Acknowledgement');
                break;
            case 'in-progress':
                $query->where('status', 'In Progress');
                break;
            case 'escalated':
                $query->where('status', 'Escalated');
                break;
            case 'pending-reclassification':
                $query->where('status', 'Pending Reclassification');
                break;
            case 'pending-supervisor-approval':
                $query->where('status', 'Pending Supervisor Approval');
                break;
            case 'pending-closure':
                $query->where('status', 'Pending Closure');
                break;
            case 'awaiting-requestor':
                $query->where('status', 'Awaiting Requestor');
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
            'awaiting_classification' => Tickets::where('status', 'Awaiting Supervisor')->whereNotNull('date_acknowledged')->whereNull('assigned_to')->count(),
            'awaiting_tech_ack' => Tickets::where('status', 'Awaiting Support Specialist Acknowledgement')->count(),
            'in_progress' => Tickets::where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('status', 'Escalated')->count(),
            'pending_reclassification' => Tickets::where('status', 'Pending Reclassification')->count(),
            'pending_supervisor_approval' => Tickets::where('status', 'Pending Supervisor Approval')->count(),
            'pending_closure' => Tickets::where('status', 'Pending Closure')->count(),
            'awaiting_requestor' => Tickets::where('status', 'Awaiting Requestor')->count(),
            'closed' => Tickets::where('status', 'Closed')->count(),
        ];
        $counts['active'] = Tickets::whereIn('status', $activeStatuses)->count();

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
            // ── Optional per-ticket overrides — default to the SLA rule's values below.
            'priority' => 'nullable|in:Critical,High,Medium,Low',
            'workload_class_id' => 'nullable|exists:workload_classes,id',
            'response_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'resolution_time_minutes' => 'nullable|numeric|min:5|max:43200',
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        if ($ticket->status !== 'Awaiting Supervisor' || !$ticket->date_acknowledged) {
            return back()->with('error', 'Ticket must be acknowledged before it can be classified and assigned.');
        }

        $slaRule = SlaRule::findOrFail($request->sla_rule_id);
        $technician = User::findOrFail($request->technician_id);
        $workloadClass = $request->filled('workload_class_id')
            ? \App\Models\WorkloadClass::find($request->workload_class_id)
            : null;

        if ($workloadClass && $workloadClass->requires_manual_resolution && !$request->filled('resolution_time_minutes')) {
            return back()->with('error', "The \"{$workloadClass->name}\" workload class has no fixed resolution target — enter the agreed resolution time in minutes.");
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

        $slot = TicketScheduler::commitAssignment(
            $ticket,
            $technician,
            $priority,
            $responseTime + $resolutionTime,
            $request->input('schedule_decision', 'auto')
        );

        if ($slot['needs_decision']) {
            return back()->withInput()->with('scheduleConflict', [
                'action_url' => route('supervisor.support.tickets.classify-assign', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $technician->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'sla_category_id' => $slaRule->sla_category_id,
            'subcategory_name' => $slaRule->subcategory_name,
            'workload_class_id' => $workloadClass?->id,
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

        $overrideNote = ($priority !== $slaRule->priority
                || $responseTime != $slaRule->response_time_minutes
                || $resolutionTime != $slaRule->resolution_time_minutes)
            ? " (SLA customized by supervisor: {$priority} priority, {$responseTime}m response / {$resolutionTime}m resolution"
                . ($workloadClass ? ", workload class: {$workloadClass->name}" : '') . '.)'
            : '';

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => 'Awaiting Classification',
            'new_status' => 'Awaiting Support Specialist Acknowledgement',
            'changed_by' => Auth::id(),
            'notes' => "Classified as {$slaRule->subcategory_name} ({$priority}) and assigned to {$technician->name}."
                . $overrideNote
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
            'status' => 'In Progress',
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
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Reassigned from {$oldTech} to {$newTech->name}."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket reassigned to {$newTech->name}.");
    }

    public function takeover(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'Escalated') {
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
            'status' => 'In Progress',
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
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
            'resolved_by' => Auth::id(),
        ]);

        // Ticket just left the In-Progress anchor slot — if it finished early (or ran
        // long), the assignee's queued-but-not-started tickets need to slide to match
        // reality instead of keeping their originally-projected times.
        if ($ticket->assignedTo) {
            TicketScheduler::resequence($ticket->assignedTo);
        }

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

        if ($ticket->status !== 'Pending Supervisor Approval') {
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
            'status' => 'In Progress',
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
            'new_status' => 'In Progress',
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

    public function escalateToAdmin(Request $request, Tickets $ticket)
    {
        $oldStatus = $ticket->status;
        $escalatingTech = $ticket->assignedTo;

        $ticket->update([
            'status' => 'Awaiting Admin Supervisor',
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

    // Escalated ticket investigated but not technically fixable — documents findings and
    // sends it straight to Pending Closure (same as a normal resolve) rather than pushing
    // it further up to Admin, since the supervisor has already reviewed it.
    public function cannotResolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'Escalated') {
            return back()->with('error', 'Only escalated tickets can be marked as unresolvable.');
        }

        $request->validate([
            'findings' => 'required|string',
            'recommendation' => 'nullable|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Pending Closure',
            'resolved_at' => now(),
            'cannot_resolve' => true,
            'cannot_resolve_findings' => $request->findings,
            'cannot_resolve_recommendation' => $request->recommendation,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Pending Closure',
            'changed_by' => Auth::id(),
            'notes' => 'Marked as unable to resolve by Supervisor - ' . Auth::user()->name .
                ". Findings & Analysis: {$request->findings}." .
                ($request->recommendation ? " Other Observations / Recommendation: {$request->recommendation}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} marked as unable to resolve. Sent for Helpdesk closure.");
    }

    // A technician has proposed a corrected classification for a miscategorized ticket —
    // accept it as the ticket's new default and drop it back into the normal unassigned
    // classify/assign queue (the same one Helpdesk-acknowledged tickets land in). No separate
    // technician-picker here: the Classify & Assign modal already pre-fills from whatever
    // classification is currently on the ticket, so the proposal just becomes the editable
    // default there.
    public function approveReclassification(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'Pending Reclassification') {
            return back()->with('error', 'Only tickets pending re-classification can be approved.');
        }

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

        $ticket->update([
            'sla_category_id' => $pending->proposed_sla_category_id,
            'subcategory_name' => $pending->proposed_subcategory_name,
            'ticket_type' => $pending->proposed_priority,
            'response_time_minutes' => $pending->proposed_response_time_minutes,
            'resolution_time_minutes' => $pending->proposed_resolution_time_minutes,
            'status' => 'Awaiting Supervisor',
        ]);

        $pending->update([
            'status' => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Supervisor',
            'changed_by' => Auth::id(),
            'notes' => 'Re-classification approved by Supervisor - ' . Auth::user()->name .
                ". Now classified as {$pending->proposed_subcategory_name} ({$pending->proposed_priority}). Ready for assignment."
                . ($request->review_notes ? " Note: {$request->review_notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with('success', "Re-classification approved for ticket #{$ticket->ticket_number}. Ready to assign.");
    }

    // Supervisor disagrees with the proposed re-classification — ticket returns to the
    // requesting technician unchanged, with the rejection reason attached for their reference.
    public function rejectReclassification(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'Pending Reclassification') {
            return back()->with('error', 'Only tickets pending re-classification can be rejected.');
        }

        $pending = ReclassificationRequest::where('ticket_id', $ticket->id)
            ->where('status', 'pending')
            ->latest('requested_at')
            ->first();

        if (!$pending) {
            return back()->with('error', 'No pending re-classification request found for this ticket.');
        }

        $request->validate([
            'review_notes' => 'required|string|max:500',
            'schedule_decision' => 'nullable|in:overtime,next_day',
        ]);

        $oldStatus = $ticket->status;
        $technician = User::findOrFail($pending->requested_by);

        if ($activeTicket = $this->activeTicketFor($technician, $ticket->id)) {
            return back()->with(
                'error',
                "{$technician->name} is already working on ticket #{$activeTicket->ticket_number} — they need to resolve or escalate it before this one returns to them."
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
                'action_url' => route('supervisor.support.tickets.reject-reclassification', $ticket),
                'ticket_number' => $ticket->ticket_number,
                'technician_name' => $technician->name,
                'proposed_end' => $slot['end']->copy()->timezone('Asia/Manila')->format('g:i A, M d'),
                'day_end' => $slot['day_end']->copy()->timezone('Asia/Manila')->format('g:i A'),
                'overtime_minutes' => $slot['end']->gt($slot['day_end']) ? $slot['end']->diffInMinutes($slot['day_end'], true) : 0,
                'extra_fields' => $request->except(['_token', 'schedule_decision']),
            ]);
        }

        $ticket->update([
            'status' => 'In Progress',
            'assigned_to' => $pending->requested_by,
            'scheduled_start' => $slot['scheduled_start'],
            'scheduled_end' => $slot['scheduled_end'],
            'is_overtime' => $slot['is_overtime'],
            'queued_at' => $slot['queued_at'],
        ]);

        $pending->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'review_notes' => $request->review_notes,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => 'Re-classification rejected by Supervisor - ' . Auth::user()->name .
                ". Returned to {$technician->name}. Reason: {$request->review_notes}",
            'changed_at' => now(),
        ]);

        if ($technician->email) {
            Mail::to($technician->email)->send(
                new TicketAssignedMail($ticket, 'Your re-classification request was declined by your Supervisor and the ticket has been returned to you.', 'technician.dashboard')
            );
        }

        return back()->with('success', "Re-classification rejected for ticket #{$ticket->ticket_number}. Returned to {$technician->name}.");
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

    // Mirrors requestRevision() above, for the Admin escalation track — Supervisor -
    // IT Admin sending an IT Admin's resolution back instead of approving it.
    public function adminRequestRevision(Request $request, Tickets $ticket)
    {
        $request->validate([
            'revision_notes' => 'required|string|max:1000',
        ]);

        if ($ticket->status !== 'Pending Admin Supervisor Approval') {
            return back()->with('error', 'Only tickets pending approval can be sent back for revision.');
        }

        $oldStatus = $ticket->status;
        $admin = $ticket->assignedTo;

        $ticket->update([
            'status' => 'Admin In Progress',
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
            'new_status' => 'Admin In Progress',
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

    // Awaiting Administrator SLA Start -> Admin In Progress
    public function adminStartSla(Tickets $ticket)
    {
        if ($ticket->status !== 'Awaiting Administrator SLA Start') {
            return back()->with('error', 'This ticket is not awaiting SLA start.');
        }

        $oldStatus = $ticket->status;
        $startedAt = now();
        $resolutionMinutes = $ticket->effectiveResolutionTimeMinutes();

        $ticket->update([
            'status' => 'Admin In Progress',
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