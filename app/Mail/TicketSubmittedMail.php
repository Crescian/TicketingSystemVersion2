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
            subject: "Ticket #{$this->ticket->ticket_number} — Received",
        );
    }

    // Root of the ticket's email thread — every later notification for this
    // ticket references this Message-ID so clients thread them together.
    public function headers(): Headers
    {
        return new Headers(
            messageId: TicketMailThread::rootMessageId($this->ticket),
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
