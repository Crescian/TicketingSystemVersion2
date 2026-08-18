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

class TicketResolvedMail extends Mailable implements ShouldQueue
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
            subject: "Ticket #{$this->ticket->ticket_number} — Please Confirm Resolution",
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
            markdown: 'emails.ticket-resolved',
            with: [
                'ticket' => $this->ticket,
            ],
        );
    }
}
