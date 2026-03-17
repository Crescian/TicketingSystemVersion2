<?php

namespace App\Http\Controllers\Helpdesk;

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
        $status = $request->get('status', 'all');
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo'])
            ->orderByRaw("CASE
                WHEN status = 'Open'        THEN 1
                WHEN status = 'In Progress' THEN 2
                WHEN status = 'Escalated'   THEN 3
                WHEN status = 'Resolved'    THEN 4
                ELSE 5 END")
            ->orderByDesc('created_at');

        if ($status !== 'all') {
            $mappedStatus = match ($status) {
                'unassigned' => 'Open',
                'in-progress' => 'In Progress',
                'escalated' => 'Escalated',
                'resolved' => 'Resolved',
                default => null
            };
            if ($mappedStatus === 'Open') {
                $query->where('status', 'Open')->whereNull('assigned_to');
            } elseif ($mappedStatus) {
                $query->where('status', $mappedStatus);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhere('status', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        match ($sort) {
            'oldest' => $query->reorder()->orderBy('created_at', 'asc'),
            'priority' => $query->reorder()->orderByRaw("CASE
                WHEN ticket_type = 'High'   THEN 1
                WHEN ticket_type = 'Medium' THEN 2
                WHEN ticket_type = 'Low'    THEN 3
                ELSE 4 END"),
            default => null
        };

        $tickets = $query->paginate(10)->withQueryString();

        // Counts
        $counts = [
            'all' => Tickets::count(),
            'unassigned' => Tickets::where('status', 'Open')->whereNull('assigned_to')->count(),
            'in_progress' => Tickets::where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('status', 'Escalated')->count(),
            'resolved' => Tickets::where('status', 'Resolved')->count(),
        ];

        // Technicians with active ticket count
        $technicians = User::whereHas('role', fn($q) =>
            $q->where('role_name', 'IT Technician'))
            ->withCount([
                'assignedTickets as active_tickets' => fn($q) =>
                    $q->whereIn('status', ['In Progress', 'Open'])
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

        return view('dashboard.helpdesk', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'technicians'
        ));
    }

    // Acknowledge ticket (Open → Open with acknowledgment note)
    public function acknowledge(Tickets $ticket)
    {
        if ($ticket->status !== 'Open') {
            return back()->with('error', 'Only Open tickets can be acknowledged.');
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => 'Open',
            'changed_by' => Auth::id(),
            'notes' => 'Ticket acknowledged by Helpdesk — ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} acknowledged.");
    }

    // Assign technician
    public function assign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;
        $tech = User::findOrFail($request->technician_id);

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'status' => 'In Progress',
            'started_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Assigned to {$tech->name} by Helpdesk."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} assigned to {$tech->name}."
        );
    }

    // Reassign technician
    public function reassign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldTech = $ticket->assignedTo?->name ?? 'Unassigned';
        $newTech = User::findOrFail($request->technician_id);

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'status' => 'In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $ticket->status,
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Reassigned from {$oldTech} to {$newTech->name} by Helpdesk."
                . ($request->notes ? " Note: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} reassigned to {$newTech->name}."
        );
    }

    // Escalate to IT Admin
    public function escalate(Request $request, Tickets $ticket)
    {
        $request->validate([
            'reason' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Escalated',
            'escalation_level' => $ticket->escalation_level + 1,
        ]);

        // Log escalation record
        DB::table('escalations')->insert([
            'id' => \Illuminate\Support\Str::uuid(),
            'ticket_id' => $ticket->id,
            'escalation_level' => $ticket->escalation_level,
            'escalated_by' => Auth::id(),
            'previous_tech_id' => $ticket->assigned_to,
            'reassigned_to' => null,
            'reason' => $request->reason,
            'resolution_notes' => $request->notes,
            'escalated_at' => now(),
            'resolved_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Escalated',
            'changed_by' => Auth::id(),
            'notes' => "Escalated to IT Admin. Reason: {$request->reason}."
                . ($request->notes ? " Notes: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} escalated to IT Admin."
        );
    }

    // Mark as resolved
    public function resolve(Request $request, Tickets $ticket)
    {
        $request->validate([
            'resolution_notes' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Resolved',
            'resolved_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Resolved',
            'changed_by' => Auth::id(),
            'notes' => "Resolved by Helpdesk: {$request->resolution_notes}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} marked as resolved."
        );
    }
}