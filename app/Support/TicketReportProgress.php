<?php

namespace App\Support;

use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

// Helpers for the shared "drafting the service report" phase of the ticket
// lifecycle — TicketStatus::IN_PROGRESS_SERVICE_REPORT / DONE_SERVICE_REPORT
// are reused across every resolver track (mirrors how For Acknowledgment /
// Classified / Assigned / Requestor Confirmation are already shared) rather
// than a per-role variant.
class TicketReportProgress
{
    // Fired by the resolver's own "Mark Fixed" action — a deliberate, separate
    // step from submitting the report (see each controller's startReport()),
    // not a side effect of opening the Resolve modal. Isolates "the fix works"
    // from "the report is written up" as two distinct moments in time, since the
    // report may not get written until well after the fix itself. Cascades
    // through Closed Service Request first (the service-request side of the
    // ticket is done) before landing on In Progress Service Report, recording
    // both hops in ticket_status_histories. Idempotent: a no-op if the ticket
    // is already in the drafting status.
    public static function markStarted(Tickets $ticket): void
    {
        if ($ticket->status === TicketStatus::IN_PROGRESS_SERVICE_REPORT) {
            return;
        }

        $oldStatus = $ticket->status;
        $now = now();

        $ticket->update([
            'status' => TicketStatus::CLOSED_SERVICE_REQUEST,
            'report_started_at' => $ticket->report_started_at ?? $now,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::CLOSED_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => 'Service request closed — preparing the service report.',
            'changed_at' => $now,
        ]);

        $ticket->update(['status' => TicketStatus::IN_PROGRESS_SERVICE_REPORT]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::CLOSED_SERVICE_REQUEST,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REPORT,
            'changed_by' => Auth::id(),
            'notes' => 'Started preparing the service report.',
            'changed_at' => $now,
        ]);
    }

    // DRAFTING and DONE are shared across every resolver track (Technician, Helpdesk
    // L1, IT Admin, Manager, Support Supervisor take-over) — so a dashboard that
    // mixes tracks (Helpdesk's own view) or gates an approval queue to one specific
    // track (Support Supervisor vs. Supervisor - IT Admin) can't tell them apart by
    // status alone anymore. assigned_to's role is the disambiguator: it doesn't
    // change between entering DRAFTING and reaching DONE, so it reliably says which
    // track a report-phase ticket belongs to.
    public static function forRoles(Builder $query, string $status, array $roleNames): Builder
    {
        return $query->where('status', $status)
            ->whereHas('assignedTo.role', fn (Builder $q) => $q->whereIn('role_name', $roleNames));
    }
}
