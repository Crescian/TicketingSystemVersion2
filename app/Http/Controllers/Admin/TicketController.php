<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'escalated');

        // Admin only sees escalated tickets + tickets they are handling
        $query = Tickets::with([
            'user.department',
            'assignedTo',
            'statusHistories.changedBy',
            'escalations.escalatedBy',
            'escalations.previousTech',
        ])
            ->where(function ($q) {
                $q->where('status', 'Escalated')
                    ->orWhere('assigned_to', Auth::id());
            })
            ->orderByRaw("CASE
                WHEN status = 'Escalated'   THEN 1
                WHEN status = 'In Progress' THEN 2
                WHEN status = 'Resolved'    THEN 3
                ELSE 4 END")
            ->orderByDesc('created_at');

        // Status filter
        if ($status !== 'all') {
            match ($status) {
                'escalated' => $query->where('status', 'Escalated'),
                'admin-wip' => $query->where('assigned_to', Auth::id())
                    ->where('status', 'In Progress'),
                'reassigned' => $query->whereHas('escalations', fn($q) =>
                    $q->whereNotNull('reassigned_to')),
                'resolved' => $query->where('status', 'Resolved')
                    ->where(function ($q) {
                            $q->where('assigned_to', Auth::id())
                            ->orWhereHas('escalations');
                        }),
                default => null
            };
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'ilike', "%{$search}%")
                    ->orWhere('subject', 'ilike', "%{$search}%")
                    ->orWhereHas('user', fn($u) =>
                        $u->where('name', 'ilike', "%{$search}%"));
            });
        }

        match ($sort) {
            'newest' => $query->reorder()->orderByDesc('created_at'),
            'oldest' => $query->reorder()->orderBy('created_at'),
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
            'all' => Tickets::where(fn($q) =>
                $q->where('status', 'Escalated')
                    ->orWhere('assigned_to', Auth::id()))->count(),
            'escalated' => Tickets::where('status', 'Escalated')->count(),
            'admin_wip' => Tickets::where('assigned_to', Auth::id())
                ->where('status', 'In Progress')->count(),
            'reassigned' => Tickets::whereHas('escalations', fn($q) =>
                $q->whereNotNull('reassigned_to'))->count(),
            'resolved' => Tickets::where('status', 'Resolved')
                ->where(fn($q) =>
                    $q->where('assigned_to', Auth::id())
                        ->orWhereHas('escalations'))->count(),
        ];

        // Technicians with load
        $technicians = User::whereHas('role', fn($q) =>
            $q->where('role_name', 'IT Support Specialist'))
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

        // System overview stats
        $today = now()->startOfDay();
        $systemStats = [
            'avg_resolution' => Tickets::where('status', 'Resolved')
                ->whereDate('resolved_at', today())
                ->whereNotNull('started_at')
                ->selectRaw("ROUND(AVG(EXTRACT(EPOCH FROM (resolved_at - started_at)) / 3600)::numeric, 1) as avg_hours")
                ->value('avg_hours'),
            'sla_breaches' => DB::table('escalations')
                ->whereDate('escalated_at', today())
                ->count(),
            'total_open' => Tickets::whereIn('status', ['Open', 'In Progress'])->count(),
            'avg_rating' => DB::table('ticket_feed_backs')
                ->whereDate('created_at', today())
                ->avg('rating'),
        ];

        $totalToday = Tickets::whereDate('created_at', today())->count();
        $escalatedCount = Tickets::where('status', 'Escalated')->count();
        $escRate = $totalToday > 0
            ? round(($escalatedCount / max(1, $totalToday)) * 100)
            : 0;

        return view('dashboard.admin', compact(
            'tickets',
            'counts',
            'status',
            'search',
            'sort',
            'technicians',
            'systemStats',
            'escRate'
        ));
    }

    // Reassign escalated ticket to new technician
    public function reassign(Request $request, Tickets $ticket)
    {
        $request->validate([
            'technician_id' => 'required|uuid|exists:users,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $oldTech = $ticket->assignedTo?->name ?? 'Unassigned';
        $newTech = User::findOrFail($request->technician_id);
        $oldStatus = $ticket->status;

        // Update escalation record
        DB::table('escalations')
            ->where('ticket_id', $ticket->id)
            ->whereNull('resolved_at')
            ->update([
                'reassigned_to' => $request->technician_id,
                'resolved_at' => now(),
            ]);

        $ticket->update([
            'assigned_to' => $request->technician_id,
            'status' => 'In Progress',
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'In Progress',
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

    // Take over ticket directly
    public function takeover(Request $request, Tickets $ticket)
    {
        $request->validate([
            'reason' => 'required|string',
            'assessment' => 'nullable|string|max:500',
        ]);

        $oldStatus = $ticket->status;

        // Update escalation
        DB::table('escalations')
            ->where('ticket_id', $ticket->id)
            ->whereNull('resolved_at')
            ->update([
                'reassigned_to' => Auth::id(),
            ]);

        $ticket->update([
            'assigned_to' => Auth::id(),
            'status' => 'In Progress',
            'started_at' => $ticket->started_at ?? now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'In Progress',
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

    // Resolve directly
    public function resolve(Request $request, Tickets $ticket)
    {
        $request->validate([
            'resolution_notes' => 'required|string',
            'root_cause' => 'required|string',
        ]);

        $oldStatus = $ticket->status;

        // Close escalation if exists
        DB::table('escalations')
            ->where('ticket_id', $ticket->id)
            ->whereNull('resolved_at')
            ->update([
                'resolution_notes' => $request->resolution_notes,
                'resolved_at' => now(),
            ]);

        $ticket->update([
            'status' => 'Resolved',
            'resolved_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Resolved',
            'changed_by' => Auth::id(),
            'notes' => "Resolved directly by IT Admin " . Auth::user()->name
                . ". Root cause: {$request->root_cause}."
                . " Resolution: {$request->resolution_notes}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved. Helpdesk and customer notified."
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
}