<?php

namespace App\Http\Controllers;

use App\Models\Tickets;
use App\Services\Pdf\TicketServiceReportPdf;
use Illuminate\Support\Facades\Auth;

class TicketServiceReportController extends Controller
{
    // Downloadable once a ticket is Closed — owner (the requester) or any
    // non-Employee (staff) role can pull it, same access rule as ticket attachments.
    public function download(Tickets $ticket)
    {
        return $this->respond($ticket, 'attachment');
    }

    // Same PDF, rendered inline so the browser displays it (embedded in an <iframe>
    // in a modal) instead of forcing a download — for a quick look before saving.
    public function preview(Tickets $ticket)
    {
        return $this->respond($ticket, 'inline');
    }

    private function respond(Tickets $ticket, string $disposition)
    {
        $user = Auth::user();
        $isOwner = $ticket->users_id === $user->id;
        $isStaff = $user->role?->role_name !== 'Employee';

        if (!$isOwner && !$isStaff) {
            abort(403);
        }

        if ($ticket->status !== 'Closed') {
            abort(404, 'Service report is only available once the ticket is closed.');
        }

        $ticket->load(['user', 'assignedTo', 'slaCategory', 'workloadClass', 'statusHistories.changedBy', 'escalations', 'resolvedBy.role', 'attachments.uploader']);

        $filename = "Service-Report-{$ticket->ticket_number}.pdf";
        $pdf = new TicketServiceReportPdf($ticket);

        return response($pdf->output($filename), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
    }
}
