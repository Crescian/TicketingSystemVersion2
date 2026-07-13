<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'active');
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'priority');

        $query = Tickets::where('assigned_to', $user->id)
            ->with(['user.department', 'statusHistories.changedBy'])
            ->orderByRaw("CASE
                WHEN status = 'Awaiting Administrator Acknowledgement' THEN 1
                WHEN status = 'Awaiting Administrator SLA Start'       THEN 2
                WHEN status = 'Admin In Progress'                       THEN 3
                WHEN status = 'Closed'                                  THEN 4
                ELSE 5 END")
            ->orderByRaw("CASE
                WHEN ticket_type = 'High'   THEN 1
                WHEN ticket_type = 'Medium' THEN 2
                WHEN ticket_type = 'Low'    THEN 3
                ELSE 4 END");

        // "active" = everything still actionable by this admin before it's closed
        $activeStatuses = [
            'Awaiting Administrator Acknowledgement',
            'Awaiting Administrator SLA Start',
            'Admin In Progress',
        ];

        if ($status === 'active') {
            $query->whereIn('status', $activeStatuses);
        } else {
            $mappedStatus = match ($status) {
                'awaiting-ack' => 'Awaiting Administrator Acknowledgement',
                'ready-start' => 'Awaiting Administrator SLA Start',
                'in-progress' => 'Admin In Progress',
                'closed' => 'Closed',
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

        // Counts — scoped to this admin's own assignments
        $counts = [
            'awaiting_ack' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Awaiting Administrator Acknowledgement')->count(),
            'ready_start' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Awaiting Administrator SLA Start')->count(),
            'in_progress' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Admin In Progress')->count(),
            'closed' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Closed')->count(),
        ];
        $counts['active'] = $counts['awaiting_ack'] + $counts['ready_start'] + $counts['in_progress'];

        // Technicians (other IT Admins) — used for reassign
        $technicians = User::whereHas('role', fn($q) =>
            $q->where('role_name', 'IT Admin'))
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

        // System overview stats (kept from original, 'Resolved' → 'Closed')
        $systemStats = [
            'avg_resolution' => Tickets::where('status', 'Closed')
                ->whereDate('resolved_at', today())
                ->whereNotNull('started_at')
                ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
                ->value('avg_hours'),
            'total_open' => Tickets::whereIn('status', [
                'Awaiting Administrator Acknowledgement',
                'Awaiting Administrator SLA Start',
                'Admin In Progress',
            ])->count(),
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

        return view('dashboard.admin', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'technicians',
            'systemStats',
            'weekStats'
        ));
    }

    // -----------------------------
    // STEP 1: Acknowledge assignment
    // -----------------------------
    public function acknowledge(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if ($ticket->status !== 'Awaiting Administrator Acknowledgement') {
            return back()->with('error', 'Only newly assigned tickets can be acknowledged.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Awaiting Administrator SLA Start',
            'tech_acknowledged_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Administrator SLA Start',
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

        if ($ticket->status !== 'Awaiting Administrator SLA Start') {
            return back()->with('error', 'Ticket must be acknowledged before it can be started.');
        }

        $request->validate([
            'estimated_time' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Admin In Progress',
            'started_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Admin In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Ticket started by " . Auth::user()->name .
                ". Estimated time: {$request->estimated_time}." .
                ($request->notes ? " Notes: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} started. SLA resolution timer is now running."
        );
    }

    // -----------------------------
    // STEP 3: Resolve & close (no separate Pending Closure/Resolved state)
    // -----------------------------
    public function resolve(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if ($ticket->status !== 'Admin In Progress') {
            return back()->with('error', 'Only started tickets can be resolved.');
        }

        $request->validate([
            'resolution_notes' => 'required|string',
            'root_cause' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Pending Admin Supervisor Approval',
            'resolved_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Pending Admin Supervisor Approval',
            'changed_by' => Auth::id(),
            'notes' => "Resolved and closed by IT Admin " . Auth::user()->name
                . ". Root cause: {$request->root_cause}."
                . " Resolution: {$request->resolution_notes}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved and closed."
        );
    }

    // Decline ticket → unassign, return to Supervisor's classification queue
    public function decline(Request $request, Tickets $ticket)
    {
        $this->authorizeAdmin($ticket);

        if (!in_array($ticket->status, ['Awaiting Administrator Acknowledgement', 'Awaiting Administrator SLA Start'])) {
            return back()->with('error', 'Tickets already in progress cannot be declined — reassign instead.');
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => null,
            'status' => 'Awaiting Admin Classification',
            'tech_acknowledged_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Admin Classification',
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
        ]);

        $oldTech = $ticket->assignedTo?->name ?? 'Unassigned';
        $newTech = User::findOrFail($request->technician_id);
        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'status' => 'Admin In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Admin In Progress',
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
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => Auth::id(),
            'status' => 'Admin In Progress',
            'started_at' => $ticket->started_at ?? now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Admin In Progress',
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

    // View full ticket history (returns JSON for modal)
    public function history(Tickets $ticket)
    {
        $history = $ticket->load([
            'statusHistories.changedBy',
            'user.department',
            'assignedTo',
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
}