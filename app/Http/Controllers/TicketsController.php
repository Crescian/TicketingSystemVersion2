<?php

namespace App\Http\Controllers;

use App\Mail\TicketAssignedMail;
use App\Mail\TicketSubmittedMail;
use App\Models\Tickets;
use App\Models\SlaCategory;
use App\Models\TicketAttachment;
use App\Models\TicketStatusHistories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TicketsController extends Controller
{
    // Roles the employee "Available IT" panel surfaces — just the two tiers a
    // requester's ticket actually passes through (Helpdesk triage, then the
    // technician who gets assigned). Same online-window mechanism as the
    // Admin/Executive presence panels (sessions.last_activity).
    private const IT_TEAM_ROLES = ['Helpdesk', 'IT Support Specialist'];

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
    private const QUEUED_STATUSES = ['Awaiting Support Specialist Acknowledgement', 'Awaiting Start SLA'];

    // A soft, employee-facing ETA for when a specialist will actually start on this
    // ticket — deliberately fuzzy (rounded hour today, day name if further out) rather
    // than the exact scheduled_start minute, since that projection can legitimately
    // shift later (a higher-priority ticket jumping the queue) or earlier (an earlier
    // ticket finishing ahead of schedule). Showing a precise time that then moves would
    // read as a broken promise even though the system behaved correctly.
    private function expectedStartLabel(Tickets $ticket): ?string
    {
        if (in_array($ticket->status, ['New Request', 'L1 In Progress', 'Awaiting Supervisor'], true)) {
            return 'Awaiting assignment';
        }

        if ($ticket->status === 'In Progress') {
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

    // Dashboard + ticket list
    public function index(Request $request)
    {
        $user = Auth::user();
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

        // ── Phase filters — grouped the same way the progress bar on the ticket
        //    card groups statuses, so a tab and the strip never disagree about
        //    where a ticket sits. (Previously 'open' matched the literal status
        //    'Open', which no controller ever sets — so it was always empty.)
        $phaseFilters = [
            'pending_acknowledgement' => function ($q) {
                $q->where('status', 'New Request')->whereNull('date_acknowledged');
            },
            'classification_assignment' => function ($q) {
                $q->where(function ($q2) {
                    $q2->where('status', 'New Request')->whereNotNull('date_acknowledged');
                })->orWhereIn('status', ['L1 In Progress', 'Awaiting Supervisor']);
            },
            'in_progress' => function ($q) {
                $q->whereIn('status', [
                    'Awaiting Support Specialist Acknowledgement', 'Awaiting Start SLA', 'In Progress', 'Escalated',
                    'Admin In Progress', 'Manager In Progress', 'Awaiting Admin Classification', 'Awaiting Admin Supervisor',
                    'Awaiting Administrator Acknowledgement', 'Awaiting Administrator SLA Start', 'Awaiting Manager',
                    'Pending Supervisor Approval', 'Pending Closure', 'Pending Reclassification', 'Resolved',
                ]);
            },
            'awaiting_requestor' => function ($q) {
                $q->where('status', 'Awaiting Requestor');
            },
            'closed' => function ($q) {
                $q->where('status', 'Closed');
            },
            'cancelled' => function ($q) {
                $q->where('status', 'Cancelled');
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

        return view('dashboard.employee', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'slaCategories',
            'slaCategoriesJson',
            'requestor',
            'itTeam'
        ));
    }

    // Show single ticket details
    public function show(Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        $ticket->load(['assignedTo', 'statusHistories.changedBy', 'feedback', 'attachments', 'slaCategory']);
        $ticket->expected_start_label = $this->expectedStartLabel($ticket);

        return view('employee.ticket-detail', compact('ticket'));
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
    public function store(Request $request)
    {
        $request->validate([
            'ticket_type'       => 'required|string',
            'request_category'  => 'required|string',
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

        // ── users_id is always sent by the employee modal too (hidden field, pre-filled
        //    with their own id), so presence alone can't distinguish helpdesk-on-behalf
        //    filing from employee self-filing — only a *different* id can.
        $isHelpdeskFiling = $request->filled('users_id') && $request->users_id !== Auth::id();
        $usersId = $isHelpdeskFiling ? $request->users_id : Auth::id();

        // ── For self-filed (employee) tickets: auto-fill requestor context.
        // ── Adjust the User model attribute names below (position/business_unit/
        //    company/department) to match whatever columns actually exist on `users`.
        $requestor = $isHelpdeskFiling ? \App\Models\User::find($usersId) : Auth::user();

        $ticket = Tickets::create([
            'ticket_number'     => Tickets::generateTicketNumber(),
            'users_id'          => $usersId,
            'ticket_type'       => $request->ticket_type,
            'request_category'  => $request->request_category,
            'subject'           => $request->subject,
            'concern'           => $request->concern,
            'request_details'   => $request->request_details,
            'asset'             => $request->asset,
            'location'          => $request->location,
            'status'            => 'New Request',
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

            // ── Date/time received: system timestamp for self-filed tickets
            'date_received'  => $isHelpdeskFiling ? $request->date_received  : now()->toDateString(),
            'time_received'  => $isHelpdeskFiling ? $request->time_received  : now()->format('H:i'),

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
            'new_status' => 'New Request',
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
            ->route('employee.tickets.index')
            ->with('new_ticket_number', $ticket->ticket_number)
            ->with('success', "Ticket #{$ticket->ticket_number} submitted successfully!");
    }

    // Cancel ticket
    public function cancel(Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        if ($ticket->status !== 'New Request' || !is_null($ticket->date_acknowledged)) {
            return back()->with('error', 'This ticket can no longer be cancelled — it has already been acknowledged by Helpdesk.');
        }

        $oldStatus = $ticket->status;

        $ticket->update(['status' => 'Cancelled']);

        TicketStatusHistories::create([
            'ticket_id'  => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Cancelled',
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

        if ($ticket->status !== 'Awaiting Requestor') {
            return back()->with('error', 'This ticket is not currently awaiting your acknowledgment.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status'      => 'Closed',
            'resolved_at' => $ticket->resolved_at ?? now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id'  => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Closed',
            'changed_by' => Auth::id(),
            'notes'      => 'Requestor acknowledged resolution — ticket closed.',
            'changed_at' => now(),
        ]);

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

    public function edit(Tickets $tickets) {}
    public function update(Request $request, Tickets $tickets) {}
    public function destroy(Tickets $tickets) {}
}