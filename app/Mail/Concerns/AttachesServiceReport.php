<?php

namespace App\Mail\Concerns;

use App\Services\Pdf\TicketServiceReportPdf;
use Illuminate\Mail\Mailables\Attachment;

// Attaches the ticket's service report PDF — used by the close emails so the
// record of the work goes out however the ticket closed. SerializesModels drops
// relations on the way to the queue, so load what the PDF renders here — same
// list as TicketServiceReportController::respond().
trait AttachesServiceReport
{
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
