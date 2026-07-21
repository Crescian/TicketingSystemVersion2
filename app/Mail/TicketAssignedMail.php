<?php

namespace App\Mail;

use App\Models\Tickets;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Tickets $ticket
     * @param string $action What the recipient needs to do, e.g. "needs triage".
     * @param string $dashboardRoute Named route for the recipient's queue.
     */
    public function __construct(
        public Tickets $ticket,
        public string $action,
        public string $dashboardRoute,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Action Needed — Ticket #{$this->ticket->ticket_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticket-assigned',
            with: [
                'ticket' => $this->ticket,
                'action' => $this->action,
                'dashboardRoute' => $this->dashboardRoute,
            ],
        );
    }
}
