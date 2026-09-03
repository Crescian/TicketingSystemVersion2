<?php

namespace App\Mail;

use App\Models\Tickets;
use App\Support\TicketMailThread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

// FYI-only, sent to Helpdesk + whoever resolved the ticket (see
// TicketsController::acknowledge()) once the requestor confirms the resolution
// and the ticket lands on Closed. No action needed — mirrors
// TicketReadyForRequestorMail's "FYI, not Action Needed" framing, just one step
// later in the lifecycle.
class TicketClosedByRequestorMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tickets $ticket,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ticket #{$this->ticket->ticket_number} — Closed (Requestor Confirmed)",
        );
    }

    public function headers(): Headers
    {
        $root = TicketMailThread::rootMessageId($this->ticket);

        return new Headers(
            references: [$root],
            text: ['In-Reply-To' => "<{$root}>"],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticket-closed-by-requestor',
            with: [
                'ticket' => $this->ticket,
            ],
        );
    }
}
