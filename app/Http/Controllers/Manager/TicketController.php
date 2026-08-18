<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Mail\TicketReadyForRequestorMail;
use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use App\Models\User;
use App\Support\TicketReportProgress;
use App\Support\TicketResolutionRules;
use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->filled('status') ? $request->get('status') : 'awaiting-manager';
        $search = $request->get('search', '');
        $sort = $request->get('sort', 'newest');

        $query = Tickets::with(['user.department', 'assignedTo', 'statusHistories.changedBy'])
            ->orderByRaw("CASE
                WHEN status = 'For Acknowledgment' AND pending_role = 'Manager' THEN 1
                WHEN status = 'In Progress Service Request' THEN 2
                WHEN status = 'In Progress Service Report' THEN 3
                WHEN status = 'Closed' THEN 4
                ELSE 5 END");

        // In Progress Service Report is shared across every resolver track (see
        // TicketReportProgress), but every branch here already filters to
        // assigned_to = Auth::id() first, so it's never ambiguous which manager's
        // ticket it is.
        $inProgressStatuses = [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::IN_PROGRESS_SERVICE_REPORT];
        $awaitingManager = fn ($q) => $q->where('status', TicketStatus::FOR_ACKNOWLEDGMENT)
            ->where('pending_role', TicketStatus::QUEUE_MANAGER);

        if ($status === 'active') {
            $query->where(function ($q) use ($inProgressStatuses, $awaitingManager) {
                $q->where($awaitingManager)
                    ->orWhere(function ($q2) use ($inProgressStatuses) {
                        $q2->whereIn('status', $inProgressStatuses)->where('assigned_to', Auth::id());
                    });
            });
        } else {
            if ($status === 'awaiting-manager') {
                $query->where($awaitingManager);
            } elseif ($status === 'in-progress') {
                $query->whereIn('status', $inProgressStatuses)->where('assigned_to', Auth::id());
            } elseif ($status === 'closed') {
                $query->where('status', TicketStatus::CLOSED)->where('assigned_to', Auth::id());
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
            'awaiting_manager' => Tickets::where($awaitingManager)->count(),
            'in_progress' => Tickets::whereIn('status', $inProgressStatuses)->where('assigned_to', Auth::id())->count(),
            'closed' => Tickets::where('status', TicketStatus::CLOSED)->where('assigned_to', Auth::id())->count(),
        ];
        $counts['active'] = $counts['awaiting_manager'] + $counts['in_progress'];

        return view('dashboard.manager.dashboard', compact('tickets', 'counts', 'status', 'search', 'sort'));
    }

    // Acknowledging is also classifying, assigning (to self), and starting —
    // nobody else classifies for the Manager track — so this one click
    // cascades through all four statuses, logging each hop.
    public function acknowledge(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::FOR_ACKNOWLEDGMENT || $ticket->pending_role !== TicketStatus::QUEUE_MANAGER) {
            return back()->with('error', 'This ticket is not awaiting Manager acknowledgment.');
        }

        $now = now();
        $actor = 'Manager - ' . Auth::user()->name;

        $ticket->update(['status' => TicketStatus::CLASSIFIED, 'pending_role' => null]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::FOR_ACKNOWLEDGMENT,
            'new_status' => TicketStatus::CLASSIFIED,
            'changed_by' => Auth::id(),
            'notes' => "Acknowledged by {$actor}.",
            'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::ASSIGNED, 'assigned_to' => Auth::id()]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::CLASSIFIED,
            'new_status' => TicketStatus::ASSIGNED,
            'changed_by' => Auth::id(),
            'notes' => "Assigned to {$actor}.",
            'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'started_at' => $now]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::ASSIGNED,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => "Started by {$actor}.",
            'changed_at' => $now,
        ]);

        return back()->with('success', "Ticket #{$ticket->ticket_number} acknowledged.");
    }

    // The technical fix is done, separate from writing it up — isolates "still
    // fixing it" from "preparing the service report" as two deliberate actions
    // instead of one combined submit (see TicketReportProgress). Resolve only
    // becomes available after this.
    public function startReport(Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REQUEST || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Only tickets you are actively working on can be marked fixed.');
        }

        TicketReportProgress::markStarted($ticket);

        return back()->with('success', "Ticket #{$ticket->ticket_number} marked fixed — prepare the service report when ready.");
    }

    // The Manager resolving it themselves already IS the approval step (no one
    // above them in this track), so this one click cascades the entire
    // remaining chain — Done Service Report, Report For Review, Approved
    // Service Report — self-approved, logging each hop, before landing on
    // Requestor Confirmation. Only reachable after startReport() (In Progress
    // Service Report) — the fix itself has to already be marked done.
    public function resolve(Request $request, Tickets $ticket)
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REPORT || $ticket->assigned_to !== Auth::id()) {
            return back()->with('error', 'Mark the ticket fixed before preparing the service report.');
        }

        $request->validate(TicketResolutionRules::BASE);

        $now = now();
        $actor = 'Manager - ' . Auth::user()->name;

        $ticket->update([
            'status' => TicketStatus::DONE_SERVICE_REPORT,
            'resolved_at' => $now,
            'resolved_by' => Auth::id(),
            'resolution_notes' => $request->resolution_notes,
            'service_type' => $request->service_type,
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::IN_PROGRESS_SERVICE_REPORT,
            'new_status' => TicketStatus::DONE_SERVICE_REPORT,
            'changed_by' => Auth::id(),
            'notes' => "Resolved by {$actor}. {$request->resolution_notes}",
            'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::REPORT_FOR_REVIEW]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::DONE_SERVICE_REPORT,
            'new_status' => TicketStatus::REPORT_FOR_REVIEW,
            'changed_by' => Auth::id(),
            'notes' => 'Service report submitted.',
            'changed_at' => $now,
        ]);

        $ticket->update([
            'status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'validated_at' => $now,
            'validation_notes' => "Self-approved — resolved directly by the assigned Manager ({$actor}).",
        ]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::REPORT_FOR_REVIEW,
            'new_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'changed_by' => Auth::id(),
            'notes' => "Self-approved by {$actor} — no reviewer above the Manager track.",
            'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::REQUESTOR_CONFIRMATION, 'closed_at' => $now]);
        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::APPROVED_SERVICE_REPORT,
            'new_status' => TicketStatus::REQUESTOR_CONFIRMATION,
            'changed_by' => Auth::id(),
            'notes' => "Resolved by {$actor}. {$request->resolution_notes}",
            'changed_at' => $now,
        ]);

        // FYI only — the requestor's own "please confirm" email is already handled
        // automatically by TicketObserver on the Awaiting Requestor transition.
        $note = 'Resolved by Manager - ' . Auth::user()->name . '.';
        foreach (User::withActiveRole('Helpdesk')->get() as $helpdeskUser) {
            if ($helpdeskUser->email) {
                Mail::to($helpdeskUser->email)->send(new TicketReadyForRequestorMail($ticket, $note));
            }
        }

        return back()->with(
            'success',
            "Ticket #{$ticket->ticket_number} resolved. Requestor notified."
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
