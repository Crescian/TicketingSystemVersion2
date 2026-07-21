<?php

namespace App\Mail;

use App\Models\Tickets;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketSubmittedMail extends Mailable implements ShouldQueue
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
            subject: "Ticket Received — #{$this->ticket->ticket_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.ticket-submitted',
            with: [
                'ticket' => $this->ticket,
            ],
        );
    }
}
