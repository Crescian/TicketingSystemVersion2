<?php

namespace App\Support;

use App\Mail\TicketPausedMail;
use App\Models\Tickets;
use App\Models\TicketStatusHistories;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

// Pause/Resume for the "In Progress Service Request" phase — shared across every
// resolver track (Technician, Helpdesk, IT Admin, Manager, Support Supervisor
// take-over, Admin Supervisor take-over) the same way TicketReportProgress shares
// the drafting/done statuses. A detour, not a pipeline step (see
// TicketStatus::ON_HOLD) — it doesn't touch started_at/sla_due_at, so the ticket
// is still owed by its original SLA deadline while paused. Each pause/resume cycle
// does add its held duration to total_hold_minutes, which Tickets::actualResolutionTime()
// subtracts back out, so "time spent" reflects only active work.
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

        $ticket->update([
            'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'hold_reason' => null,
            'paused_at' => null,
            'total_hold_minutes' => $ticket->total_hold_minutes + $heldMinutes,
        ]);

        TicketStatusHistories::create([
            'ticket_id' => $ticket->id,
            'old_status' => TicketStatus::ON_HOLD,
            'new_status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST,
            'changed_by' => Auth::id(),
            'notes' => 'Resumed by ' . Auth::user()->name . '.',
            'changed_at' => $now,
        ]);
    }
}
