<?php

namespace App\Http\Resources\Mobile;

use App\Support\TicketStatus;
use Illuminate\Http\Request;

// Full ticket view — the list card fields plus the same details, history and
// attachments the web employee ticket page shows.
class TicketDetailResource extends TicketResource
{
    public function toArray(Request $request): array
    {
        return parent::toArray($request) + [
            'concern' => $this->concern,
            'request_details' => $this->request_details,
            'asset' => $this->asset,
            'location' => $this->location,
            'hold_reason' => $this->status === TicketStatus::ON_HOLD ? $this->hold_reason : null,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'service_report_url' => in_array($this->status, [TicketStatus::REQUESTOR_CONFIRMATION, TicketStatus::CLOSED], true)
                ? route('mobile.tickets.service-report', $this->resource)
                : null,
            // Ticket-level files only; chat attachments come with their message.
            'attachments' => AttachmentResource::collection(
                $this->attachments->whereNull('ticket_message_id')->values()
            ),
            'history' => $this->statusHistories
                ->sortByDesc('changed_at')
                ->values()
                ->map(fn ($h) => [
                    'status' => $h->new_status,
                    'notes' => $h->notes,
                    'by' => $h->changedBy?->name,
                    'at' => $h->changed_at ? \Carbon\Carbon::parse($h->changed_at)->toIso8601String() : null,
                ]),
            'feedback' => $this->feedback ? [
                'rating' => $this->feedback->rating,
                'comments' => $this->feedback->comments,
            ] : null,
        ];
    }
}
