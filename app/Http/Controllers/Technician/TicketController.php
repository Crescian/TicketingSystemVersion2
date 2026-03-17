<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $status = $request->get('status', 'all');
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'priority');

        $query = Tickets::where('assigned_to', $user->id)
            ->with(['user.department', 'statusHistories.changedBy'])
            ->orderByRaw("CASE
                WHEN status = 'In Progress' THEN 1
                WHEN status = 'Open'        THEN 2
                WHEN status = 'Escalated'   THEN 3
                WHEN status = 'Resolved'    THEN 4
                ELSE 5 END")
            ->orderByRaw("CASE
                WHEN ticket_type = 'High'   THEN 1
                WHEN ticket_type = 'Medium' THEN 2
                WHEN ticket_type = 'Low'    THEN 3
                ELSE 4 END");

        // Status filter
        if ($status !== 'all') {
            $mappedStatus = match ($status) {
                'new-assigned' => 'Open',
                'in-progress' => 'In Progress',
                'escalated' => 'Escalated',
                'resolved' => 'Resolved',
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
            'all' => Tickets::where('assigned_to', $user->id)->count(),
            'new_assigned' => Tickets::where('assigned_to', $user->id)->where('status', 'Open')->count(),
            'in_progress' => Tickets::where('assigned_to', $user->id)->where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('assigned_to', $user->id)->where('status', 'Escalated')->count(),
            'resolved' => Tickets::where('assigned_to', $user->id)->where('status', 'Resolved')->count(),
        ];

        // Weekly stats
        $weekStart = now()->startOfWeek();
        $weekStats = [
            'resolved' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Resolved')
                ->where('resolved_at', '>=', $weekStart)
                ->count(),
            'escalated' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Escalated')
                ->where('updated_at', '>=', $weekStart)
                ->count(),
            'avg_time' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Resolved')
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

        return view('dashboard.technician', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'weekStats'
        ));
    }

    // Accept ticket → status stays In Progress, log acceptance
    public function accept(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        $request->validate([
            'estimated_time' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'In Progress',
            'started_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'In Progress',
            'changed_by' => Auth::id(),
            'notes' => "Ticket accepted by " . Auth::user()->name .
                ". Estimated time: {$request->estimated_time}." .
                ($request->notes ? " Notes: {$request->notes}" : ''),
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} accepted. Status set to In Progress."
        );
    }

    // Decline ticket → unassign, return to helpdesk queue
    public function decline(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        $request->validate([
            'reason' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => null,
            'status' => 'Open',
            'started_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Open',
            'changed_by' => Auth::id(),
            'notes' => "Declined by " . Auth::user()->name .
                ". Reason: {$request->reason}. Returned to helpdesk queue.",
            'changed_at' => now(),
        ]);

        return redirect()
            ->route('technician.dashboard')
            ->with(
                'success',
                "Ticket #{$ticket->ticket_number} declined and returned to helpdesk."
            );
    }

    // Add update / progress note
    public function update(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

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

        return back()->with(
            'success',
            "Progress update logged for #{$ticket->ticket_number}."
        );
    }

    // Resolve ticket → notify helpdesk
    public function resolve(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        $request->validate([
            'resolution_notes' => 'required|string',
            'time_spent' => 'required|string',
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
            'notes' => "Resolved by " . Auth::user()->name .
                ". Time spent: {$request->time_spent}. " .
                $request->resolution_notes,
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved. Helpdesk has been notified."
        );
    }

    // Escalate to IT Admin
    public function escalate(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        $request->validate([
            'reason' => 'required|string',
            'already_tried' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Escalated',
            'escalation_level' => $ticket->escalation_level + 1,
        ]);

        // Log to escalations table
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
            'notes' => "Escalated to IT Admin by " . Auth::user()->name .
                ". Reason: {$request->reason}. " .
                "Already tried: {$request->already_tried}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} escalated to IT Admin."
        );
    }

    // Authorize that only the assigned tech can act
    private function authorizeTech(Tickets $ticket): void
    {
        if ($ticket->assigned_to !== Auth::id()) {
            abort(403, 'You are not assigned to this ticket.');
        }
    }
}