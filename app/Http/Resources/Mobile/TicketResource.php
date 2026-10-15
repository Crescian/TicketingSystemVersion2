<?php

namespace App\Http\Resources\Mobile;

use App\Services\RequestorTicketService;
use App\Support\TicketStatus;
use App\Support\TicketTrackerStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// One of the employee's own tickets, as shown on a list card. `step` is the
// 5-phase tracker (App\Support\TicketTrackerStep) the web employee dashboard
// uses — null for Cancelled. The `can_*` flags mirror the server-side guards in
// RequestorTicketService, so the app shows exactly the buttons the API accepts.
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $service = app(RequestorTicketService::class);
        $step = TicketTrackerStep::highestReached($this->status, $this->date_acknowledged);

        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'subject' => $this->subject,
            'status' => $this->status,
            'step' => $step,
            'step_label' => $step ? TicketTrackerStep::LABELS[$step] : null,
            'is_on_hold' => $this->status === TicketStatus::ON_HOLD,
            'priority' => $this->ticket_type,
            'category' => $this->slaCategory?->name,
            'subcategory' => $this->subcategory_name,
            'assigned_to' => $this->assignedTo?->name,
            'expected_start' => $service->expectedStartLabel($this->resource),
            'unread_messages' => $this->whenLoaded('unreadMessages', fn () => $this->unreadMessages->count()),
            'can_edit' => $service->isStillWithHelpdesk($this->resource),
            'can_cancel' => $service->isStillWithHelpdesk($this->resource),
            'can_confirm' => $this->status === TicketStatus::REQUESTOR_CONFIRMATION,
            'can_rate' => $this->status === TicketStatus::CLOSED && !$this->feedback,
            'has_service_report' => in_array($this->status, [TicketStatus::REQUESTOR_CONFIRMATION, TicketStatus::CLOSED], true),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
