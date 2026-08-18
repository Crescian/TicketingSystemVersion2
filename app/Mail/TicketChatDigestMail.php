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
use Illuminate\Support\Collection;

class TicketChatDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param Tickets $ticket
     * @param Collection<int, \App\Models\TicketMessage> $messages
     */
    public function __construct(
        public Tickets $ticket,
        public Collection $messages,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        $count = $this->messages->count();

        return new Envelope(
            subject: "Ticket #{$this->ticket->ticket_number} — "
                . ($count === 1 ? 'New Message' : "New Messages ({$count})"),
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
            markdown: 'emails.ticket-chat-digest',
            with: [
                'ticket' => $this->ticket,
                'messages' => $this->messages,
            ],
        );
    }
}
