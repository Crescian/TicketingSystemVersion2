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

class SlaAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Tickets $ticket
     * @param string $level 'at_risk' or 'breached'
     */
    public function __construct(
        public Tickets $ticket,
        public string $level,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        $subject = $this->level === 'breached'
            ? "Ticket #{$this->ticket->ticket_number} — SLA Breached"
            : "Ticket #{$this->ticket->ticket_number} — SLA At Risk";

        return new Envelope(
            subject: $subject,
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
            markdown: 'emails.sla-alert',
            with: [
                'ticket' => $this->ticket,
                'level' => $this->level,
            ],
        );
    }
}
