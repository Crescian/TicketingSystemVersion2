<?php

namespace App\Console\Commands;

use App\Mail\TicketAutoClosedMail;
use App\Models\TicketStatusHistories;
use App\Models\Tickets;
use App\Support\TicketStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

// Requestor Confirmation is the last step before a ticket is truly Closed —
// the requestor just has to confirm the resolution. If they never respond,
// the ticket would sit open forever waiting on them. This mirrors the manual
// confirm path (TicketsController::acknowledge) but runs unattended: after
// THRESHOLD_HOURS with no response, it auto-confirms on the requestor's
// behalf and closes the ticket, same as if they'd clicked confirm themselves.
class AutoCloseStaleConfirmations extends Command
{
    protected $signature = 'tickets:auto-close-confirmations';

    protected $description = 'Automatically confirm and close tickets that have sat in Requestor Confirmation for 24+ hours with no requestor response.';

    private const THRESHOLD_HOURS = 24;

    public function handle(): int
    {
        $tickets = Tickets::where('status', TicketStatus::REQUESTOR_CONFIRMATION)
            ->with('user')
            ->get();

        $closed = 0;

        foreach ($tickets as $ticket) {
            $enteredAt = $this->statusEnteredAt($ticket);

            if ($enteredAt === null || $enteredAt->diffInHours(now()) < self::THRESHOLD_HOURS) {
                continue;
            }

            $oldStatus = $ticket->status;
            $now = now();

            $ticket->update([
                'status' => TicketStatus::CLOSED,
                'resolved_at' => $ticket->resolved_at ?? $now,
                'closed_at' => $now,
            ]);

            TicketStatusHistories::create([
                'ticket_id' => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => TicketStatus::CLOSED,
                // No human acted here — attribute the history row to the
                // requestor (same actor the manual confirm path would use),
                // with the notes making clear it was automatic.
                'changed_by' => $ticket->users_id,
                'notes' => 'Automatically confirmed and closed — no requestor response within '
                    . self::THRESHOLD_HOURS . ' hours.',
                'changed_at' => $now,
            ]);

            if ($ticket->user && $ticket->user->email) {
                Mail::to($ticket->user->email)->send(new TicketAutoClosedMail($ticket));
            }

            $closed++;
        }

        $this->info("Auto-close check complete. {$closed} ticket(s) automatically confirmed and closed.");

        return self::SUCCESS;
    }

    private function statusEnteredAt(Tickets $ticket): ?Carbon
    {
        $history = $ticket->statusHistories()
            ->where('new_status', TicketStatus::REQUESTOR_CONFIRMATION)
            ->orderByDesc('changed_at')
            ->first();

        return $history->changed_at ?? $ticket->updated_at;
    }
}
