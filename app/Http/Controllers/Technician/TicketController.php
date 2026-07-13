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
        $status = $request->get('status', 'active');
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'priority');

        $query = Tickets::where('assigned_to', $user->id)
            ->with(['user.department', 'statusHistories.changedBy'])
            ->orderByRaw("CASE
                WHEN status = 'Awaiting Support Specialist Acknowledgement'  THEN 1
                WHEN status = 'Awaiting Start SLA' THEN 2
                WHEN status = 'Open'         THEN 3
                WHEN status = 'Escalated'    THEN 4
                WHEN status = 'Closed'       THEN 5
                ELSE 6 END")
            ->orderByRaw("CASE
                WHEN ticket_type = 'High'   THEN 1
                WHEN ticket_type = 'Medium' THEN 2
                WHEN ticket_type = 'Low'    THEN 3
                ELSE 4 END");

        // Status filter — simplified to the 4 actionable states + Closed.
        // "active" (default) = everything still actionable by the technician,
        // i.e. everything before it lands with the supervisor for validation/closure.
        $activeStatuses = ['Awaiting Support Specialist Acknowledgement', 'Awaiting Start SLA', 'In Progress', 'Escalated'];

        if ($status === 'active') {
            $query->whereIn('status', $activeStatuses);
        } else {
            $mappedStatus = match ($status) {
                'awaiting-ack' => 'Awaiting Support Specialist Acknowledgement',
                'ready-start' => 'Awaiting Start SLA',
                'in-progress' => 'In Progress',
                'escalated' => 'Escalated',
                'closed' => 'Closed',
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
            'awaiting_ack' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Awaiting Support Specialist Acknowledgement')->count(),
            'ready_start' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Awaiting Start SLA')->count(),
            'in_progress' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'In Progress')->count(),
            'escalated' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Escalated')->count(),
            'closed' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Closed')->count(),
        ];
        $counts['active'] = $counts['awaiting_ack'] + $counts['ready_start']
            + $counts['in_progress'] + $counts['escalated'];

        // Weekly stats
        // Note: resolved_at is stamped once when the tech resolves the ticket, and never
        // cleared afterward — so we key off the timestamp rather than a status string,
        // since the ticket keeps moving through supervisor-side statuses after that.
        $weekStart = now()->startOfWeek();
        $weekStats = [
            'resolved' => Tickets::where('assigned_to', $user->id)
                ->whereNotNull('resolved_at')
                ->where('resolved_at', '>=', $weekStart)
                ->count(),
            'escalated' => Tickets::where('assigned_to', $user->id)
                ->where('status', 'Escalated')
                ->where('updated_at', '>=', $weekStart)
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

        $slaCategories = \App\Models\SlaCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name');

        return view('dashboard.technician', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'weekStats'
        ));
    }

    // -----------------------------
    // STEP 1: Acknowledge assignment (SLA Response Time #5 ends here)
    // -----------------------------
    public function acknowledge(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'Awaiting Support Specialist Acknowledgement') {
            return back()->with('error', 'Only newly assigned tickets can be acknowledged.');
        }

        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Awaiting Start SLA',
            'tech_acknowledged_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Start SLA',
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
    // STEP 2: Start ticket (SLA Resolution Time officially begins here)
    // -----------------------------
    public function start(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'Awaiting Start SLA') {
            return back()->with('error', 'Ticket must be acknowledged before it can be started.');
        }

        $request->validate([
            'estimated_time' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'In Progress',
            'started_at' => now(), // SLA resolution clock starts here
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'In Progress',
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

    // Decline ticket → unassign, return to supervisor queue (can decline before starting work)
    public function decline(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if (!in_array($ticket->status, ['Awaiting Support Specialist Acknowledgement', 'Awaiting Start SLA'])) {
            return back()->with('error', 'Tickets already in progress cannot be declined — escalate instead.');
        }

        $request->validate([
            'reason' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'assigned_to' => null,
            'status' => 'Awaiting Supervisor',
            'tech_acknowledged_at' => null,
            'started_at' => null,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Awaiting Supervisor',
            'changed_by' => Auth::id(),
            'notes' => "Declined by " . Auth::user()->name .
                ". Reason: {$request->reason}. Returned to supervisor for reassignment.",
            'changed_at' => now(),
        ]);

        return redirect()
            ->route('technician.dashboard')
            ->with(
                'success',
                "Ticket #{$ticket->ticket_number} declined and returned to supervisor."
            );
    }

    // Add update / progress note (only while In Progress)
    public function update(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'In Progress') {
            return back()->with('error', 'Only started tickets can receive progress updates.');
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

        return back()->with(
            'success',
            "Progress update logged for #{$ticket->ticket_number}."
        );
    }

    // -----------------------------
    // STEP 3: Resolve ticket (SLA Resolution Time ends here)
    // -----------------------------
    public function resolve(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'In Progress') {
            return back()->with('error', 'Only started tickets can be marked resolved.');
        }

        $request->validate([
            'resolution_notes' => 'required|string',
            'time_spent' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Pending Closure',
            'resolved_at' => now(),
        ]);

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

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved. Awaiting Supervisor validation."
        );
    }

    // Escalate to Supervisor (only while In Progress)
    // Note: assigned_to is intentionally left untouched here. The ticket keeps showing
    // in this technician's queue (under the Escalated tab, then eventually Closed)
    // even after a supervisor/admin-side member picks it up.
    public function escalate(Request $request, Tickets $ticket)
    {
        $this->authorizeTech($ticket);

        if ($ticket->status !== 'In Progress') {
            return back()->with('error', 'Only started tickets can be escalated.');
        }

        $request->validate([
            'reason' => 'required|string',
            'already_tried' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Escalated',
            'assigned_to' => null,
            'escalation_level' => $ticket->escalation_level + 1,
        ]);

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
            'notes' => "Escalated to Supervisor by " . Auth::user()->name .
                ". Reason: {$request->reason}. " .
                "Already tried: {$request->already_tried}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} escalated to Supervisor."
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