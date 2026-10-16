<?php

namespace App\Http\Resources\Mobile\Staff;

use App\Support\TicketStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// A ticket on a staff member's queue card: internal status (staff see the
// full workflow, unlike the employee tracker), requester, assignee with the
// dashboard's capacity annotation when present, and the live SLA state the
// web cards show (breached / at risk at ≥75% elapsed).
class StaffTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $t = $this->resource;
        $assignee = $t->relationLoaded('assignedTo') ? $t->assignedTo : null;
        $requester = $t->relationLoaded('user') ? $t->user : null;
        $running = in_array($t->status, [TicketStatus::IN_PROGRESS_SERVICE_REQUEST, TicketStatus::ON_HOLD], true);

        return [
            'id' => $t->id,
            'ticket_number' => $t->ticket_number,
            'subject' => $t->subject,
            'status' => $t->status,
            'pending_role' => $t->pending_role,
            'priority' => $t->ticket_type,
            'category' => $t->relationLoaded('slaCategory') ? $t->slaCategory?->name : null,
            'subcategory' => $t->subcategory_name,
            'escalation_level' => (int) $t->escalation_level,
            'requester' => $requester ? [
                'id' => $requester->id,
                'name' => $requester->name,
                'department' => $requester->relationLoaded('department') ? $requester->department?->department_name : $t->department,
            ] : ['id' => $t->users_id, 'name' => $t->employee_name, 'department' => $t->department],
            'assignee' => $assignee ? array_filter([
                'id' => $assignee->id,
                'name' => $assignee->name,
                'role' => $assignee->relationLoaded('role') ? $assignee->role?->role_name : null,
                // Set by the supervisor dashboards (current capacity of the tech).
                'availability' => $assignee->availability ?? null,
                'free_time_label' => $assignee->free_time_label ?? null,
            ], fn ($v) => $v !== null) : null,
            'acknowledged_by_assignee' => (bool) $t->tech_acknowledged_at,
            'is_on_hold' => $t->status === TicketStatus::ON_HOLD,
            'sla' => $t->sla_due_at ? [
                'due_at' => $t->sla_due_at->toIso8601String(),
                'seconds_remaining' => $running ? $t->slaSecondsRemaining() : null,
                'breached' => $t->isSlaBreached(),
                'at_risk' => $t->isSlaAtRisk(),
            ] : null,
            'scheduled_start' => $t->scheduled_start?->toIso8601String(),
            'scheduled_end' => $t->scheduled_end?->toIso8601String(),
            'is_overtime' => (bool) $t->is_overtime,
            'unread_messages' => $this->whenLoaded('unreadMessages', fn () => $t->unreadMessages->count()),
            'created_at' => $t->created_at?->toIso8601String(),
            'updated_at' => $t->updated_at?->toIso8601String(),
        ];
    }
}
