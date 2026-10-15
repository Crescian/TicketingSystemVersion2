<?php

namespace App\Http\Controllers;

use App\Exceptions\TicketActionException;
use App\Models\Tickets;
use App\Models\SlaCategory;
use App\Models\TicketAttachment;
use App\Services\RequestorTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TicketsController extends Controller
{
    public function __construct(private RequestorTicketService $requestorTickets)
    {
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
        
        // Phase tabs, search, category, date range and sort — shared with the
        // mobile API (see RequestorTicketService::listQuery()).
        $query = $this->requestorTickets->listQuery($user, $request->only(['status', 'search', 'category', 'from_date', 'to_date', 'sort']))
            ->with(['assignedTo', 'feedback', 'unreadMessages', 'slaCategory']);

        $tickets = $query->paginate(10)->withQueryString();
        $tickets->getCollection()->each(function (Tickets $ticket) {
            $ticket->expected_start_label = $this->requestorTickets->expectedStartLabel($ticket);
        });

        $counts = $this->requestorTickets->counts($user);

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

        $itTeam = $this->requestorTickets->itTeamStatus();
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
        $ticket->expected_start_label = $this->requestorTickets->expectedStartLabel($ticket);
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
        // Developer isn't a support tier — like an Employee, only their own tickets.
        $isStaff = !in_array($user->role?->role_name, ['Employee', 'Developer'], true);

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
    // ── Requestor info + date/time received are auto-filled server-side
    //    (RequestorTicketService::selfFiledAttributes()).
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

        // ── Helpdesk filing on behalf of an employee enters the requestor context,
        //    date/time received and method by hand; a self-filed ticket gets all of
        //    those auto-filled from the requestor's own account.
        $attributes = $isHelpdeskFiling
            ? [
                'users_id'         => $request->users_id,
                'ticket_type'      => $request->ticket_type,
                'request_category' => $request->request_category,
                'subject'          => $request->subject,
                'concern'          => $request->concern,
                'request_details'  => $request->request_details,
                'asset'            => $request->asset,
                'location'         => $request->location,
                'position'         => $request->position,
                'business_unit'    => $request->business_unit,
                'company'          => $request->company,
                'department'       => $request->department,
                'date_received'    => $request->date_received,
                'time_received'    => $request->time_received,
                'method'           => $request->method,
            ]
            : $this->requestorTickets->selfFiledAttributes(Auth::user(), $request->all());

        $ticket = $this->requestorTickets->file(
            $attributes,
            $request->file('attachments', []),
            Auth::user(),
            $isHelpdeskFiling,
        );

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

    // Cancel ticket — only while still unacknowledged in Helpdesk's queue.
    public function cancel(Tickets $ticket)
    {
        try {
            $this->requestorTickets->cancel($ticket, Auth::user());
        } catch (TicketActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been cancelled.");
    }

    // Employee acknowledges resolution -> ticket moves to Closed
    public function acknowledge(Tickets $ticket)
    {
        try {
            $this->requestorTickets->confirmResolution($ticket, Auth::user());
        } catch (TicketActionException $e) {
            return back()->with('error', $e->getMessage());
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

    // Feedback is gated on 'Closed', once per ticket.
    public function storeFeedback(Request $request, Tickets $ticket)
    {
        try {
            $this->requestorTickets->assertCanRate($ticket, Auth::user());
        } catch (TicketActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:500',
        ]);

        $this->requestorTickets->rate($ticket, Auth::user(), (int) $request->rating, $request->comments);

        return back()->with('success', 'Thank you for your feedback! ⭐');
    }

    // Edit the request's own details — only while it's still sitting
    // unacknowledged in Helpdesk's queue (same gate as cancel()). Once
    // Helpdesk acknowledges it, the requestor loses the ability to change
    // what they filed.
    public function update(Request $request, Tickets $ticket)
    {
        $this->requestorTickets->assertOwner($ticket, Auth::user());

        if (!$this->requestorTickets->isStillWithHelpdesk($ticket)) {
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

        try {
            $this->requestorTickets->update($ticket, Auth::user(), $request->all(), $request->file('attachments', []));
        } catch (TicketActionException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Support request #{$ticket->ticket_number} updated successfully.");
    }
}
