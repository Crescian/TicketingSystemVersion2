<?php

namespace App\Observers;

use App\Mail\TicketAwaitingConfirmationMail;
use App\Models\Tickets;
use App\Support\RequestorPush;
use App\Support\TicketTrackerStep;
use Illuminate\Support\Facades\Auth;
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

    // Mobile push is less intrusive than email, so it goes out for every step
    // the requestor can see change on the app's tracker — [title, body prefix].
    private const PUSHES = [
        2 => ['Request scheduled', 'A specialist has been scheduled for your request.'],
        3 => ['Work has started', 'A specialist is now working on your request.'],
        4 => ['Please confirm the fix', 'Your request is resolved — open the app to confirm or review the service report.'],
        5 => ['Request closed', 'Your request has been closed.'],
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

        // One push for the step landed on (not one per step crossed), and none
        // when the requestor caused the change themselves (confirming closure).
        if (isset(self::PUSHES[$newStep]) && Auth::id() !== $ticket->users_id) {
            [$title, $body] = self::PUSHES[$newStep];
            RequestorPush::send($ticket, 'status_change', $title, "#{$ticket->ticket_number} — {$body}");
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
