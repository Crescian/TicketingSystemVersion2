<?php

namespace App\Support;

use App\Mail\TicketPausedMail;
use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

// Pause/Resume for the "In Progress Service Request" phase — shared across every
// resolver track (Technician, Helpdesk, IT Admin, Manager, Support Supervisor
// take-over, Admin Supervisor take-over) the same way TicketReportProgress shares
// the drafting/done statuses. A detour, not a pipeline step (see
// TicketStatus::ON_HOLD). The SLA clock stops while paused: on resume, the
// business minutes that were left on sla_due_at at pause time are laid out again
// from the resume moment, so time spent On Hold never counts against the SLA (a
// ticket already past due when paused stays breached). Each pause/resume cycle
// also adds its held duration to total_hold_minutes, which
// Tickets::actualResolutionTime() subtracts back out, so "time spent" reflects
// only active work.
class TicketHold
{
    public static function pause(Tickets $ticket, string $reason): void
    {
        if ($ticket->status !== TicketStatus::IN_PROGRESS_SERVICE_REQUEST) {
            return;
        }

        $oldStatus = $ticket->status;
        $now = now();

        $ticket->update([
            'status' => TicketStatus::ON_HOLD,
            'hold_reason' => $reason,
            'paused_at' => $now,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => TicketStatus::ON_HOLD,
            'changed_by' => Auth::id(),
            'notes' => 'Paused by ' . Auth::user()->name . ". Reason: {$reason}",
            'changed_at' => $now,
        ]);

        if ($ticket->user?->email) {
            Mail::to($ticket->user->email)->send(new TicketPausedMail($ticket, $reason));
        }
    }

    public static function resume(Tickets $ticket): void
    {
        if ($ticket->status !== TicketStatus::ON_HOLD) {
            return;
        }

        $now = now();
        $heldMinutes = $ticket->paused_at ? (int) round($ticket->paused_at->diffInMinutes($now)) : 0;

        $slaDueAt = $ticket->sla_due_at && $ticket->paused_at
            ? self::resumedDueAt($ticket->paused_at, $ticket->sla_due_at, $now)
            : $ticket->sla_due_at;
        $deadlineMoved = $slaDueAt && $ticket->sla_due_at && $slaDueAt->gt($ticket->sla_due_at);

        $ticket->update([
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'hold_reason' => null,
            'paused_at' => null,
            'total_hold_minutes' => $ticket->total_hold_minutes + $heldMinutes,
            'sla_due_at' => $slaDueAt,
        ]);

        // The pushed-back deadline may take the ticket back out of the at-risk
        // window — clear the flag so sla:check can warn again when it re-enters.
        if ($deadlineMoved && $ticket->sla_risk_notified_at && !$ticket->isSlaAtRisk()) {
            $ticket->update(['sla_risk_notified_at' => null]);
        }

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::ON_HOLD,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => 'Resumed by ' . Auth::user()->name . '.',
            'changed_at' => $now,
        ]);
    }

    // The SLA deadline after a hold: whatever business minutes were still left at
    // pause time, laid out again from the resume moment. A ticket that was already
    // past due when paused keeps its original (breached) deadline.
    public static function resumedDueAt(Carbon $pausedAt, Carbon $dueAt, Carbon $resumedAt): Carbon
    {
        $remaining = BusinessClock::businessMinutesSpanning($pausedAt, $dueAt);

        return $remaining > 0
            ? BusinessClock::addBusinessMinutes($resumedAt->copy(), $remaining)
            : $dueAt;
    }
}
