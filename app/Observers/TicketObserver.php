<?php

namespace App\Observers;

use App\Mail\TicketAwaitingConfirmationMail;
use App\Models\Tickets;
use App\Support\TicketTrackerStep;
use Illuminate\Support\Facades\Mail;

// Employee-facing notifications are deliberately minimal — just two emails across
// the whole lifecycle (ticket submission is sent directly from TicketsController::
// store(), not here, since that's a create event this update-hook can't see):
// this one, fired the moment a ticket reaches "Awaiting Your Confirmation" (step 4
// of TicketTrackerStep) after the Supervisor validates the service report. Every
// other internal status change (acknowledged, classified, assigned, in progress,
// report drafting/review, closed) stays silent — the employee side doesn't surface
// the detailed internal workflow, so there's nothing to notify about in between.
class TicketObserver
{
    /** @var array<int, class-string> */
    private const MAILABLES = [
        4 => TicketAwaitingConfirmationMail::class,
    ];

    public function updated(Tickets $ticket): void
    {
        if (!$ticket->wasChanged(['status', 'date_acknowledged'])) {
            return;
        }

        $oldStep = TicketTrackerStep::highestReached(
            $ticket->getOriginal('status'),
            $ticket->getOriginal('date_acknowledged'),
        );
        $newStep = TicketTrackerStep::highestReached($ticket->status, $ticket->date_acknowledged);

        // Cancelled tickets show no tracker (TicketTrackerStep::highestReached
        // returns null for them) — nothing to notify.
        if ($oldStep === null || $newStep === null || $newStep <= $oldStep) {
            return;
        }

        if (!$ticket->user || !$ticket->user->email) {
            return;
        }

        // Send every step crossed, not just the one landed on — an escalation
        // track (or the Manager/self-resolve self-approval cascade) can jump
        // straight past step 2/3 into step 4, and it should still notify.
        for ($step = $oldStep + 1; $step <= $newStep; $step++) {
            $mailable = self::MAILABLES[$step] ?? null;

            if ($mailable) {
                Mail::to($ticket->user->email)->send(new $mailable($ticket));
            }
        }
    }
}
