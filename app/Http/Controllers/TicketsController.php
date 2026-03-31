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

        $counts = [
            'all' => Tickets::where('users_id', $user->id)->count(),
            'open' => Tickets::where('users_id', $user->id)->where('status', 'Open')->count(),
            'in_progress' => Tickets::where('users_id', $user->id)->where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('users_id', $user->id)->where('status', 'Escalated')->count(),
            'resolved' => Tickets::where('users_id', $user->id)->where('status', 'Resolved')->count(),
            'cancelled' => Tickets::where('users_id', $user->id)->where('status', 'Cancelled')->count(),
        ];

        // ── Load SLA categories with their active rules for the ticket modal
        $slaCategories = \App\Models\SlaCategory::with([
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

        // Add this RIGHT AFTER $slaCategories is built, before the return statement
        $slaCategoriesJson = $slaCategories->map(fn($c) => [
            'id' => $c->id,
            'name' => $c->name,
            'icon' => $c->icon,
            'color' => $c->color,
            'subs' => $c->rules->map(fn($r) => [
                'name' => $r->subcategory_name,
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
            'slaCategoriesJson'   // ← add this
        ));
    }
    // public function index(Request $request)
    // {
    //     $user = Auth::user();
    //     $status = $request->get('status', 'all');
    //     $search = $request->get('search', '');
    //     $category = $request->get('category', '');
    //     $fromDate = $request->get('from_date', '');
    //     $sort = $request->get('sort', 'newest');

    //     $query = Tickets::where('users_id', $user->id)
    //         ->with(['assignedTo']);

    //     // Filter by status
    //     if ($status !== 'all') {
    //         $query->where('status', 'ilike', $status);
    //     }

    //     // Filter by category
    //     if ($category) {
    //         $query->where('request_category', $category);
    //     }

    //     // Filter by date
    //     if ($fromDate) {
    //         $query->whereDate('created_at', '>=', $fromDate);
    //     }

    //     // Search
    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             $q->where('ticket_number', 'ilike', "%{$search}%")
    //                 ->orWhere('subject', 'ilike', "%{$search}%")
    //                 ->orWhere('request_category', 'ilike', "%{$search}%")
    //                 ->orWhere('status', 'ilike', "%{$search}%");
    //         });
    //     }

    //     // Sort
    //     match ($sort) {
    //         'oldest' => $query->orderBy('created_at', 'asc'),
    //         'priority' => $query->orderByRaw("CASE
    //         WHEN ticket_type = 'High'   THEN 1
    //         WHEN ticket_type = 'Medium' THEN 2
    //         WHEN ticket_type = 'Low'    THEN 3
    //         ELSE 4 END"),
    //         default => $query->orderByDesc('created_at'),
    //     };

    //     $tickets = $query->paginate(10)->withQueryString();

    //     // Status counts (unaffected by filters)
    //     $counts = [
    //         'all' => Tickets::where('users_id', $user->id)->count(),
    //         'open' => Tickets::where('users_id', $user->id)->where('status', 'Open')->count(),
    //         'in_progress' => Tickets::where('users_id', $user->id)->where('status', 'In Progress')->count(),
    //         'escalated' => Tickets::where('users_id', $user->id)->where('status', 'Escalated')->count(),
    //         'resolved' => Tickets::where('users_id', $user->id)->where('status', 'Resolved')->count(),
    //         'cancelled' => Tickets::where('users_id', $user->id)->where('status', 'Cancelled')->count(),
    //     ];

    //     $greeting = $this->getGreeting();

    //     return view('dashboard.employee', compact(
    //         'tickets',
    //         'counts',
    //         'status',
    //         'search',
    //         'greeting'
    //     ));
    // }

    // Show single ticket details
    public function show(Tickets $ticket)
    {
        // Make sure employee can only view their own tickets
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
    public function store(Request $request)
    {
        $request->validate([
            'ticket_type' => 'required|string',
            'request_category' => 'required|string',
            'subject' => 'required|string|max:255',
            'concern' => 'required|string',
            'request_details' => 'nullable|string', // ← changed to nullable
            'asset' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        $ticket = Tickets::create([
            'ticket_number' => Tickets::generateTicketNumber(),
            'users_id' => Auth::id(),
            'ticket_type' => $request->ticket_type,
            'request_category' => $request->request_category,
            'subject' => $request->subject,
            'concern' => $request->concern,
            'request_details' => $request->request_details,
            'asset' => $request->asset,
            'location' => $request->location,
            'status' => 'Open',
            'escalation_level' => 0,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => null,
            'new_status' => 'Open',
            'changed_by' => Auth::id(),
            'notes' => 'Ticket submitted by employee.',
            'changed_at' => now(),
        ]);

        // ── Return JSON for AJAX submission
        if (request()->ajax()) {
            return response()->json([
                'ticket_number' => $ticket->ticket_number,
                'ticket_id' => $ticket->id,
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
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Cancelled',
            'changed_by' => Auth::id(),
            'notes' => 'Cancelled by employee.',
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} has been cancelled.");
    }

    private function getGreeting(): string
    {
        $hour = now()->hour;
        return match (true) {
            $hour >= 5 && $hour < 12 => 'Good Morning',
            $hour >= 12 && $hour < 18 => 'Good Afternoon',
            $hour >= 18 && $hour < 22 => 'Good Evening',
            default => 'Good Night',
        };
    }

    // Add this method to the existing TicketController
    public function storeFeedback(Request $request, Tickets $ticket)
    {
        if ($ticket->users_id !== Auth::id()) {
            abort(403);
        }

        if ($ticket->status !== 'Resolved') {
            return back()->with('error', 'You can only rate resolved tickets.');
        }

        if ($ticket->feedback) {
            return back()->with('error', 'You have already submitted feedback for this ticket.');
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string|max:500',
        ]);

        \App\Models\TicketFeedback::create([
            'ticket_id' => $ticket->id,
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comments' => $request->comments,
            'created_at' => now(),
        ]);

        return back()->with('success', 'Thank you for your feedback! ⭐');
    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tickets $tickets)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tickets $tickets)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tickets $tickets)
    {
        //
    }
}
