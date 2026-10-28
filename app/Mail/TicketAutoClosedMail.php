<?php

namespace App\Mail;

use App\Models\Tickets;
use App\Services\Pdf\TicketServiceReportPdf;
use App\Support\TicketMailThread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TicketAutoClosedMail extends Mailable implements ShouldQueue
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
            subject: "Ticket #{$this->ticket->ticket_number} — Automatically Closed",
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
            markdown: 'emails.ticket-auto-closed',
            with: [
                'ticket' => $this->ticket,
            ],
        );
    }

    // The service report PDF, so the requestor has the record of the work even
    // though they never opened the ticket to confirm it. SerializesModels drops
    // relations on the way to the queue, so load what the PDF renders here —
    // same list as TicketServiceReportController::respond().
    public function attachments(): array
    {
        $filename = "Service-Report-{$this->ticket->ticket_number}.pdf";

        return [
            Attachment::fromData(function () use ($filename) {
                $this->ticket->loadMissing(['user', 'assignedTo', 'slaCategory', 'workloadClass', 'statusHistories.changedBy', 'escalations', 'resolvedBy.role', 'attachments.uploader']);

                return (new TicketServiceReportPdf($this->ticket))->output($filename);
            }, $filename)->withMime('application/pdf'),
        ];
    }
}
