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

// FYI-only, sent to Helpdesk — resolutions now go straight to Awaiting Requestor
// once validated/finalized instead of waiting on Helpdesk's old manual Close &
// Notify step, so this is the only signal Helpdesk gets that it happened. No
// action needed, unlike TicketAssignedMail's "Action Needed" framing.
class TicketReadyForRequestorMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tickets $ticket,
        public string $note,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ticket #{$this->ticket->ticket_number} — Resolved & Requestor Notified (FYI)",
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
            markdown: 'emails.ticket-ready-for-requestor',
            with: [
                'ticket' => $this->ticket,
                'note' => $this->note,
            ],
        );
    }
}
