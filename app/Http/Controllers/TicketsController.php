<?php

namespace App\Http\Controllers;

use App\Mail\TicketAssignedMail;
use App\Mail\TicketClosedByRequestorMail;
use App\Mail\TicketSubmittedMail;
use App\Models\Tickets;
use App\Models\SlaCategory;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistories;
use App\Models\User;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TicketsController extends Controller
{
    // Roles the employee "Available IT" panel surfaces — the tiers a requester's
    // ticket can pass through (Helpdesk triage, the technician who gets assigned,
    // or an IT Admin/Supervisor track for admin-side requests) plus the
    // supervisors who oversee them. Same online-window mechanism as the
    // Admin/Executive presence panels (sessions.last_activity).
    private const IT_TEAM_ROLES = [
        'Helpdesk',
        'IT Support Specialist',
        'Supervisor - Support Specialist',
        'IT Admin',
        'Supervisor - IT Admin',
    ];

    private const ONLINE_WINDOW_MINUTES = 5;

    private function onlineUserIds(): \Illuminate\Support\Collection
    {
        return DB::table('sessions')
            ->whereNotNull('user_id')
            ->where('last_activity', '>=', now()->subMinutes(self::ONLINE_WINDOW_MINUTES)->timestamp)
            ->distinct()
            ->pluck('user_id');
    }

    // Not-yet-started statuses a ticket passes through once a specialist is assigned —
    // matches App\Services\TicketScheduler's own NOT_STARTED_STATUSES, duplicated here
    // since that constant is private to the scheduler.
    private const QUEUED_STATUSES = [TicketStatus::ASSIGNED];

    // A soft, employee-facing ETA for when a specialist will actually start on this
    // ticket — deliberately fuzzy (rounded hour today, day name if further out) rather
    // than the exact scheduled_start minute, since that projection can legitimately
    // shift later (a higher-priority ticket jumping the queue) or earlier (an earlier
    // ticket finishing ahead of schedule). Showing a precise time that then moves would
    // read as a broken promise even though the system behaved correctly.
    private function expectedStartLabel(Tickets $ticket): ?string
    {
        if (in_array($ticket->status, [TicketStatus::FOR_ACKNOWLEDGMENT, TicketStatus::FOR_CLASSIFICATION, TicketStatus::CLASSIFIED], true)) {
            return 'Awaiting assignment';
        }

        if ($ticket->status === TicketStatus::IN_PROGRESS_SERVICE_REQUEST) {
            return 'Being worked on now';
        }

        if (!in_array($ticket->status, self::QUEUED_STATUSES, true) || !$ticket->scheduled_start) {
            return null;
        }

        $now = now('Asia/Manila');
        $start = $ticket->scheduled_start->copy()->timezone('Asia/Manila');

        if ($start->isSameDay($now)) {
            return $start->lte($now) ? 'Today, shortly' : 'Today, after ' . $start->format('g:00 A');
        }

        if ($start->isSameDay($now->copy()->addDay())) {
            return 'Tomorrow';
        }

        if ($now->diffInDays($start) <= 6) {
            return $start->format('l');
        }

        return 'On ' . $start->format('M j');
    }

    private function itTeamStatus(): \Illuminate\Support\Collection
    {
        $onlineIds = $this->onlineUserIds();

        return User::with('role')
            ->whereHas('role', fn($q) => $q->whereIn('role_name', self::IT_TEAM_ROLES))
            ->where('active', true)
            ->orderBy('name')
            ->get()
            ->map(function ($member) use ($onlineIds) {
                $member->online = $onlineIds->contains($member->id);
                return $member;
            })
            ->sortByDesc('online')
            ->values();
    }

    // Named-route prefix for the requestor-facing views (dashboard.employee,
    // employee.ticket-detail) — 'employee.' for the Employee role's own routes,
    // 'my-requests.' for every other role's self-service ticket routes. Lets
    // one set of views serve both without hardcoding either route namespace.
    private function requestRoutePrefix(): string
    {
        return Auth::user()->hasRole('Employee') ? 'employee.' : 'my-requests.';
    }

    // Dashboard + ticket list
    public function index(Request $request)
    {
        $user = Auth::user();
        $routePrefix = $this->requestRoutePrefix();
        $status = $request->get('status', 'all');
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $requestor = \App\Models\User::query()
        ->select(
            'users.id',
            'users.name',
            'users.position',
            'business_units_name as business_units_name',
            'company_name as company_name',
            'department_name as department_name'
        )
        ->leftJoin('departments', 'departments.id', '=', 'users.department_id')
        ->leftJoin('companies', 'companies.id', '=', 'departments.companies_id')
        ->leftJoin('business_units', 'business_units.id', '=', 'companies.business_units_id')
        ->where('users.id', $user->id)
        ->first();
        
        $query = Tickets::with(['assignedTo', 'feedback', 'unreadMessages', 'slaCategory'])
            ->where('users_id', $user->id);

        // ── Phase filters — same 5-phase vocabulary as the simplified tracker on
        //    the ticket card (App\Support\TicketTrackerStep: Submitted -> Scheduled
        //    -> In Progress -> Awaiting Your Confirmation -> Closed), so a tab and
        //    the tracker never disagree about where a ticket sits. The employee
        //    side doesn't surface the detailed internal workflow (acknowledged vs.
        //    classified vs. assigned), so those no longer get separate tabs.
        $phaseFilters = [
            'submitted' => function ($q) {
                $q->whereIn('status', [
                    TicketStatus::FOR_ACKNOWLEDGMENT,
                    TicketStatus::FOR_CLASSIFICATION,
                    TicketStatus::CLASSIFIED,
                ]);
            },
            'scheduled' => function ($q) {
                $q->where('status', TicketStatus::ASSIGNED);
            },
            'in_progress' => function ($q) {
                $q->whereIn('status', [
                    TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::CLOSED_SERVICE_REQUEST,
                    TicketStatus::IN_PROGRESS_SERVICE_REPORT, TicketStatus::DONE_SERVICE_REPORT,
                    TicketStatus::REPORT_FOR_REVIEW, TicketStatus::APPROVED_SERVICE_REPORT, TicketStatus::ESCALATED,
                ]);
            },
            'awaiting_requestor' => function ($q) {
                $q->where('status', TicketStatus::REQUESTOR_CONFIRMATION);
            },
            'closed' => function ($q) {
                $q->where('status', TicketStatus::CLOSED);
            },
            'cancelled' => function ($q) {
                $q->where('status', TicketStatus::CANCELLED);
            },
        ];

        // Status filter
        if ($status !== 'all' && isset($phaseFilters[$status])) {
            $query->where($phaseFilters[$status]);
        }

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ilike', "%{$search}%")
                    ->orWhere('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('concern', 'ilike', "%{$search}%")
                    ->orWhere('request_category', 'ilike', "%{$search}%");
            });
        }

        // ── Category filter — matches the ticket's classified SLA category
        //    (only set once Helpdesk classifies it), not the employee's
        //    self-submitted request_category, which isn't reliably populated.
        if ($request->filled('category')) {
            $query->where('sla_category_id', $request->get('category'));
        }

        // From/To date filter — scoped to when the ticket was submitted.
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->get('to_date'));
        }

        // Sort
        match ($sort) {
            'oldest' => $query->oldest(),
            'priority' => $query->orderByRaw("CASE ticket_type WHEN 'Critical' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Low' THEN 4 END"),
            default => $query->latest(),
        };

        $tickets = $query->paginate(10)->withQueryString();
        $tickets->getCollection()->each(function (Tickets $ticket) {
            $ticket->expected_start_label = $this->expectedStartLabel($ticket);
        });

        $counts = ['all' => Tickets::where('users_id', $user->id)->count()];
        foreach ($phaseFilters as $key => $filter) {
            $counts[$key] = Tickets::where('users_id', $user->id)->where($filter)->count();
        }

        // ── Load SLA categories with their active rules for the ticket modal
        $slaCategories = SlaCategory::with([
            'rules' => function ($q) {
                $q->where('is_active', true)
                    ->select('id', 'sla_category_id', 'subcategory_name', 'priority')
                    ->orderBy('subcategory_name');
            }
        ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $slaCategoriesJson = $slaCategories->map(fn($c) => [
            'id'    => $c->id,
            'name'  => $c->name,
            'icon'  => $c->icon,
            'color' => $c->color,
            'subs'  => $c->rules->map(fn($r) => [
                'name'     => $r->subcategory_name,
                'priority' => $r->priority,
            ])->values()->toArray(),
        ])->values()->toArray();

        $itTeam = $this->itTeamStatus();
        // ── Onboarding tour is Employee-only — staff roles filing their own
        //    request already know the system from their day job.
        $showOnboarding = $routePrefix === 'employee.' && is_null($user->onboarded_at);

        return view('dashboard.employee', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'slaCategories',
            'slaCategoriesJson',
            'requestor',
            'itTeam',
            'showOnboarding',
            'routePrefix'
        ));
    }

    // Dismisses the one-time "welcome to the new system" modal — called via a
    // background fetch from the modal itself, not a full page navigation, so
    // dismissing it doesn't reset whatever filter/tab the employee was on.
    public function completeOnboarding()
    {
        Auth::user()->update(['onboarded_at' => now()]);

        return response()->noContent();
    }

    // Show single ticket details
    public function show(Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        $ticket->load(['assignedTo', 'statusHistories.changedBy', 'feedback', 'attachments', 'slaCategory']);
        $ticket->expected_start_label = $this->expectedStartLabel($ticket);
        $routePrefix = $this->requestRoutePrefix();

        return view('employee.ticket-detail', compact('ticket', 'routePrefix'));
    }

    // Download a ticket attachment — owner or any non-Employee (staff) role can access.
    public function downloadAttachment(TicketAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($attachment);

        return Storage::disk('local')->download($attachment->stored_path, $attachment->original_name);
    }

    // Stream a ticket attachment inline (PDFs/images open in-browser instead of downloading).
    public function viewAttachment(TicketAttachment $attachment)
    {
        $this->authorizeAttachmentAccess($attachment);

        return Storage::disk('local')->response($attachment->stored_path, $attachment->original_name);
    }

    private function authorizeAttachmentAccess(TicketAttachment $attachment): void
    {
        $user = Auth::user();
        $isOwner = $attachment->ticket->users_id === $user->id;
        $isStaff = $user->role?->role_name !== 'Employee';

        if (!$isOwner && !$isStaff) {
            abort(403);
        }

        if (!Storage::disk('local')->exists($attachment->stored_path)) {
            abort(404, 'File no longer available.');
        }
    }

    // Show create form
    public function create()
    {
        return view('employee.ticket-create');
    }

    // Store new ticket
    // ── Simplified: employee modal is now Issue Type -> Review only.
    // ── Requestor info + date/time received are auto-filled server-side.
    // Every employee self-filed ticket starts at this priority — the employee
    // form has no category/priority picker (that's Helpdesk/Supervisor's job at
    // classification), so nothing employee-supplied is trusted for it. Only
    // Helpdesk's own "file on behalf of" form (which has a real category+
    // subcategory picker wired to the SLA Rule table) can set it directly.
    private const DEFAULT_PRIORITY = 'Medium';

    public function store(Request $request)
    {
        // ── users_id is always sent by the employee modal too (hidden field, pre-filled
        //    with their own id), so presence alone can't distinguish helpdesk-on-behalf
        //    filing from employee self-filing — only a *different* id can. Computed
        //    before validation so ticket_type/request_category can be required only
        //    for the helpdesk-filing path, which is the only one with real values for them.
        $isHelpdeskFiling = $request->filled('users_id') && $request->users_id !== Auth::id();

        $request->validate([
            'ticket_type'       => $isHelpdeskFiling ? 'required|string' : 'nullable|string',
            'request_category'  => $isHelpdeskFiling ? 'required|string' : 'nullable|string',
            'subject'            => 'required|string|max:255',
            'concern'            => 'required|string',
            'request_details'   => 'nullable|string',
            'asset'              => 'nullable|string|max:255',
            'location'           => 'nullable|string|max:255',
            // ── Only present when Helpdesk files on behalf of someone else
            'users_id'           => 'nullable|uuid|exists:users,id',
            // ── Optional supporting files (screenshots, documents, etc.)
            'attachments'        => 'nullable|array|max:5',
            'attachments.*'      => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $usersId = $isHelpdeskFiling ? $request->users_id : Auth::id();

        // ── For self-filed (employee) tickets: auto-fill requestor context.
        // ── Adjust the User model attribute names below (position/business_unit/
        //    company/department) to match whatever columns actually exist on `users`.
        $requestor = $isHelpdeskFiling ? \App\Models\User::find($usersId) : Auth::user();

        $ticket = Tickets::create([
            'ticket_number'     => Tickets::generateTicketNumber(),
            'users_id'          => $usersId,
            'ticket_type'       => $isHelpdeskFiling ? $request->ticket_type : self::DEFAULT_PRIORITY,
            'request_category'  => $isHelpdeskFiling ? $request->request_category : null,
            'subject'           => $request->subject,
            'concern'           => $request->concern,
            'request_details'   => $request->request_details,
            'asset'             => $request->asset,
            'location'          => $request->location,
            'status'            => TicketStatus::FOR_ACKNOWLEDGMENT,
            'pending_role'      => TicketStatus::QUEUE_HELPDESK,
            'escalation_level'  => 0,

            // ── Requestor context — auto-filled, not user-entered when self-filed.
            // department/company/business_unit are traversed off User::department()
            // (a belongsTo relation, not a plain column) — grabbing the relation
            // directly instead of ->department_name would silently JSON-encode the
            // related model into these varchar columns.
            'position'       => $isHelpdeskFiling ? $request->position       : ($requestor->position ?? null),
            'business_unit'  => $isHelpdeskFiling ? $request->business_unit  : ($requestor->department?->company?->businessUnit?->business_units_name ?? null),
            'company'        => $isHelpdeskFiling ? $request->company        : ($requestor->department?->company?->company_name ?? null),
            'department'     => $isHelpdeskFiling ? $request->department     : ($requestor->department?->department_name ?? null),

            // ── Date/time received: system timestamp for self-filed tickets. Plain
            // strings, not Carbon-cast — computed as Asia/Manila wall-clock values
            // directly, since app.timezone is UTC.
            'date_received'  => $isHelpdeskFiling ? $request->date_received  : now()->timezone('Asia/Manila')->toDateString(),
            'time_received'  => $isHelpdeskFiling ? $request->time_received  : now()->timezone('Asia/Manila')->format('H:i'),

            // ── Method: defaults to "System" for self-filed tickets
            'method'         => $isHelpdeskFiling ? $request->method : 'System',
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
            'ticket_id'  => $ticket->id,
            'old_status' => null,
            'new_status' => TicketStatus::FOR_ACKNOWLEDGMENT,
            'changed_by' => Auth::id(),
            'notes'      => $isHelpdeskFiling
                ? 'Ticket filed by Helpdesk on behalf of employee.'
                : 'Ticket submitted by employee.',
            'changed_at' => now(),
        ]);

        if ($requestor && $requestor->email) {
            Mail::to($requestor->email)->send(new TicketSubmittedMail($ticket));
        }

        // Helpdesk owns triage of self-filed tickets — no need to notify them of their own filing.
        if (!$isHelpdeskFiling) {
            $helpdeskUsers = User::withActiveRole('Helpdesk')->get();
            foreach ($helpdeskUsers as $helpdeskUser) {
                Mail::to($helpdeskUser->email)->send(
                    new TicketAssignedMail($ticket, 'A new ticket has been submitted and needs triage.', 'helpdesk.dashboard')
                );
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'ticket_number' => $ticket->ticket_number,
                'ticket_id'     => $ticket->id,
            ]);
        }

        return redirect()
            ->route($this->requestRoutePrefix() . 'tickets.index')
            ->with('new_ticket_number', $ticket->ticket_number)
            ->with('success', "Ticket #{$ticket->ticket_number} submitted successfully!");
    }

    // Cancel ticket
    public function cancel(Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        if ($ticket->status !== TicketStatus::FOR_ACKNOWLEDGMENT
            || $ticket->pending_role !== TicketStatus::QUEUE_HELPDESK
            || !is_null($ticket->date_acknowledged)) {
            return back()->with('error', 'This ticket can no longer be cancelled — it has already been acknowledged by Helpdesk.');
        }

        $oldStatus = $ticket->status;

        $ticket->update(['status' => TicketStatus::CANCELLED]);

        TicketStatusHistories::create([
            'ticket_id'  => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CANCELLED,
            'changed_by' => Auth::id(),
            'notes'      => 'Cancelled by employee.',
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been cancelled.");
    }

    // ── NEW: Employee acknowledges resolution -> ticket moves to Closed
    public function acknowledge(Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        if ($ticket->status !== TicketStatus::REQUESTOR_CONFIRMATION) {
            return back()->with('error', 'This ticket is not currently awaiting your acknowledgment.');
        }

        $oldStatus = $ticket->status;
        $now = now();

        $ticket->update([
            'status'      => TicketStatus::CLOSED,
            'resolved_at' => $ticket->resolved_at ?? $now,
            'closed_at'   => $now,
        ]);

        TicketStatusHistories::create([
            'ticket_id'  => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CLOSED,
            'changed_by' => Auth::id(),
            'notes'      => 'Requestor acknowledged resolution — ticket closed.',
            'changed_at' => $now,
        ]);

        // FYI to Helpdesk (coordination hub, same convention as
        // notifyHelpdeskFyi() in SupervisorDashboardController) plus whoever
        // actually resolved the ticket — they're the internal/ICT side that
        // cares this closed the loop. Deduped by id in case the resolver is
        // themselves a Helpdesk agent.
        $recipients = User::withActiveRole('Helpdesk')->get()
            ->when($ticket->resolvedBy, fn ($users) => $users->push($ticket->resolvedBy))
            ->unique('id');

        foreach ($recipients as $recipient) {
            if ($recipient->email) {
                Mail::to($recipient->email)->send(new TicketClosedByRequestorMail($ticket));
            }
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been closed. Thanks for confirming!");
    }

    private function getGreeting(): string
    {
        $hour = now()->hour;
        return match (true) {
            $hour >= 5 && $hour < 12  => 'Good Morning',
            $hour >= 12 && $hour < 18 => 'Good Afternoon',
            $hour >= 18 && $hour < 22 => 'Good Evening',
            default                   => 'Good Night',
        };
    }

    // ── Feedback now gated on 'Closed' instead of 'Resolved'
    public function storeFeedback(Request $request, Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        if ($ticket->status !== 'Closed') {
            return back()->with('error', 'You can only rate closed tickets.');
        }

        if ($ticket->feedback) {
            return back()->with('error', 'You have already submitted feedback for this ticket.');
        }

        $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:500',
        ]);

        \App\Models\TicketFeedback::create([
            'ticket_id'  => $ticket->id,
            'user_id'    => Auth::id(),
            'rating'     => $request->rating,
            'comments'   => $request->comments,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Thank you for your feedback! ⭐');
    }

    // Edit the request's own details — only while it's still sitting
    // unacknowledged in Helpdesk's queue (same gate as cancel()). Once
    // Helpdesk acknowledges it, the requestor loses the ability to change
    // what they filed.
    public function update(Request $request, Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        if ($ticket->status !== TicketStatus::FOR_ACKNOWLEDGMENT
            || $ticket->pending_role !== TicketStatus::QUEUE_HELPDESK
            || !is_null($ticket->date_acknowledged)) {
            return back()->with('error', 'This support request can no longer be edited — it has already been acknowledged by Helpdesk.');
        }

        $request->validate([
            'subject'           => 'required|string|max:255',
            'concern'           => 'required|string',
            'request_details'   => 'nullable|string',
            'location'          => 'nullable|string|max:255',
            'attachments'       => 'nullable|array|max:5',
            'attachments.*'     => 'file|max:10240|mimes:jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,txt',
        ]);

        $ticket->update([
            'subject'          => $request->subject,
            'concern'          => $request->concern,
            'request_details'  => $request->request_details,
            'location'         => $request->location,
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
            'ticket_id'  => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => $ticket->status,
            'changed_by' => Auth::id(),
            'notes'      => 'Request details updated by employee.',
            'changed_at' => now(),
        ]);

        // Same pattern as a fresh submission (see store() above) — Helpdesk
        // hasn't acknowledged this ticket yet (that's the only time update() is
        // reachable), so they need to know the details changed before they do.
        $helpdeskUsers = User::withActiveRole('Helpdesk')->get();
        foreach ($helpdeskUsers as $helpdeskUser) {
            Mail::to($helpdeskUser->email)->send(
                new TicketAssignedMail($ticket, 'The employee updated this request\'s details before it was acknowledged.', 'helpdesk.dashboard')
            );
        }

        return back()->with('success', "Support request #{$ticket->ticket_number} updated successfully.");
    }
}