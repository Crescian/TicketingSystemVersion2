<?php

namespace App\Http\Controllers;

use App\Models\Tickets;
use App\Services\Pdf\TicketServiceReportPdf;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\Auth;

class TicketServiceReportController extends Controller
{
    // Statuses from the point a report is actually submitted onward — staff can
    // pull it up any time from here, not just once Closed, so a Supervisor can
    // review what the Support Specialist attached before approving it.
    private const STAFF_VISIBLE_STATUSES = [
        TicketStatus::REPORT_FOR_REVIEW,
        TicketStatus::APPROVED_SERVICE_REPORT,
        TicketStatus::REQUESTOR_CONFIRMATION,
        TicketStatus::CLOSED,
    ];

    // The requestor (owner) sees it once it's handed to them for confirmation
    // ("Awaiting Your Confirmation"), so they can review the report before closing.
    private const OWNER_VISIBLE_STATUSES = [
        TicketStatus::REQUESTOR_CONFIRMATION,
        TicketStatus::CLOSED,
    ];

    // Owner (the requester) or any non-Employee (staff) role can pull it, same
    // access rule as ticket attachments — gated by status per the lists above.
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

        $isVisible = $isStaff
            ? in_array($ticket->status, self::STAFF_VISIBLE_STATUSES, true)
            : in_array($ticket->status, self::OWNER_VISIBLE_STATUSES, true);

        if (!$isVisible) {
            abort(404, $isStaff
                ? 'Service report is only available once a report has been submitted for review.'
                : 'Service report is only available once the request is awaiting your confirmation.');
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
