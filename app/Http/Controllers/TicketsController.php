<?php

namespace App\Http\Controllers;

use App\Models\Tickets;
use App\Models\SlaCategory;
use App\Models\TicketStatusHistories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketsController extends Controller
{
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
        
        $query = Tickets::with(['assignedTo', 'feedback', 'unreadMessages'])
            ->where('users_id', $user->id);

        // Status filter
        if ($status !== 'all') {
            $query->where('status', ucwords($status));
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

        // Sort
        match ($sort) {
            'oldest' => $query->oldest(),
            'priority' => $query->orderByRaw("CASE ticket_type WHEN 'High' THEN 1 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 3 END"),
            default => $query->latest(),
        };

        $tickets = $query->paginate(10)->withQueryString();

        // ── NOTE: 'resolved' replaced with 'awaiting_requestor' + 'closed'
        $counts = [
            'all'                 => Tickets::where('users_id', $user->id)->count(),
            'open'                => Tickets::where('users_id', $user->id)->where('status', 'Open')->count(),
            'in_progress'         => Tickets::where('users_id', $user->id)->where('status', 'In Progress')->count(),
            'escalated'           => Tickets::where('users_id', $user->id)->where('status', 'Escalated')->count(),
            'awaiting_requestor'  => Tickets::where('users_id', $user->id)->where('status', 'Awaiting Requestor')->count(),
            'closed'              => Tickets::where('users_id', $user->id)->where('status', 'Closed')->count(),
            'cancelled'           => Tickets::where('users_id', $user->id)->where('status', 'Cancelled')->count(),
        ];

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

        return view('dashboard.employee', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'slaCategories',
            'slaCategoriesJson',
            'requestor'
        ));
    }

    // Show single ticket details
    public function show(Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        $ticket->load(['assignedTo', 'statusHistories.changedBy', 'feedback']);

        return view('employee.ticket-detail', compact('ticket'));
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
        ]);

        $isHelpdeskFiling = $request->filled('users_id');
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

            // ── Requestor context — auto-filled, not user-entered when self-filed
            'position'       => $isHelpdeskFiling ? $request->position       : ($requestor->position ?? null),
            'business_unit'  => $isHelpdeskFiling ? $request->business_unit  : ($requestor->business_unit ?? null),
            'company'        => $isHelpdeskFiling ? $request->company        : ($requestor->company ?? null),
            'department'     => $isHelpdeskFiling ? $request->department     : ($requestor->department ?? null),

            // ── Date/time received: system timestamp for self-filed tickets
            'date_received'  => $isHelpdeskFiling ? $request->date_received  : now()->toDateString(),
            'time_received'  => $isHelpdeskFiling ? $request->time_received  : now()->format('H:i'),

            // ── Method: defaults to "System" for self-filed tickets
            'method'         => $isHelpdeskFiling ? $request->method : 'System',
        ]);

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

        if (!in_array($ticket->status, ['Open', 'In Progress'])) {
            return back()->with('error', 'Only Open or In Progress tickets can be cancelled.');
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