<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->filled('status') ? $request->get('status') : 'awaiting-manager';
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy'])
            ->orderByRaw("CASE
                WHEN status = 'Awaiting Manager' THEN 1
                WHEN status = 'Manager In Progress' THEN 2
                WHEN status = 'Closed' THEN 3
                ELSE 4 END");

        if ($status === 'active') {
            $query->where(function ($q) {
                $q->where('status', 'Awaiting Manager')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'Manager In Progress')->where('assigned_to', Auth::id());
                    });
            });
        } else {
            $mappedStatus = match ($status) {
                'awaiting-manager' => 'Awaiting Manager',
                'in-progress' => 'Manager In Progress',
                'closed' => 'Closed',
                default => null,
            };

            if ($mappedStatus === 'Awaiting Manager') {
                $query->where('status', 'Awaiting Manager');
            } elseif ($mappedStatus) {
                $query->where('status', $mappedStatus)->where('assigned_to', Auth::id());
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
            'oldest' => $query->reorder()->orderBy('created_at'),
            'newest' => $query->reorder()->orderByDesc('created_at'),
            default => null,
        };

        $tickets = $query->paginate(10)->withQueryString();

        $counts = [
            'awaiting_manager' => Tickets::where('status', 'Awaiting Manager')->count(),
            'in_progress' => Tickets::where('status', 'Manager In Progress')->where('assigned_to', Auth::id())->count(),
            'closed' => Tickets::where('status', 'Closed')->where('assigned_to', Auth::id())->count(),
        ];
        $counts['active'] = $counts['awaiting_manager'] + $counts['in_progress'];

        return view('dashboard.manager.dashboard', compact('tickets', 'counts', 'status', 'search', 'sort'));
    }

    public function acknowledge(Tickets $ticket)
    {
        if ($ticket->status !== 'Awaiting Manager') {
            return back()->with('error', 'This ticket is not awaiting Manager acknowledgment.');
        }

        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => 'Manager In Progress',
            'assigned_to' => Auth::id(),
            'started_at' => now(),
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => 'Manager In Progress',
            'changed_by' => Auth::id(),
            'notes' => 'Acknowledged by Manager - ' . Auth::user()->name,
            'changed_at' => now(),
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} acknowledged.");
    }

    public function resolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== 'Manager In Progress' || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only tickets you are actively working on can be resolved.');
        }

        $request->validate([
            'resolution_notes' => 'required|string',
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
            'notes' => 'Resolved by Manager - ' . Auth::user()->name . ". {$request->resolution_notes}",
            'changed_at' => now(),
        ]);

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved. Ready for Helpdesk closure & notification."
        );
    }

    // Full status timeline for a ticket (JSON, used by both the Manager Queue and Executive Dashboard)
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
