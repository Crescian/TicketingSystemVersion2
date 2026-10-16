<?php

namespace App\Http\Resources\Mobile\Staff;

use App\Http\Resources\Mobile\AttachmentResource;
use App\Support\Mobile\StaffActions;
use App\Support\TicketStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

// Everything a staff member sees for one ticket: the queue card fields plus
// the request, classification and SLA targets, schedule, report fields,
// hold/revision/escalation context, history, attachments — and `actions`,
// the actions this user can take now (StaffActions), each with its form.
class StaffTicketDetailResource extends StaffTicketResource
{
    // Staff can open the service report from the moment it's submitted for review.
    private const REPORT_STATUSES = [
        TicketStatus::REPORT_FOR_REVIEW,
        TicketStatus::APPROVED_SERVICE_REPORT,
        TicketStatus::REQUESTOR_CONFIRMATION,
        TicketStatus::CLOSED,
    ];

    public function toArray(Request $request): array
    {
        $t = $this->resource;
        $pendingReclass = $t->reclassificationRequests->where('status', 'pending')->sortByDesc('requested_at')->first();

        return parent::toArray($request) + [
            'concern' => $t->concern,
            'request_details' => $t->request_details,
            'request_category' => $t->request_category,
            'asset' => $t->asset,
            'location' => $t->location,
            'method' => $t->method,
            'received' => trim(($t->date_received ? Carbon::parse($t->date_received)->format('M j, Y') : '') . ' ' . ($t->time_received ?? '')) ?: null,
            'requester_position' => $t->position,
            'requester_company' => $t->company,
            'classification' => [
                'workload_class' => $t->workloadClass?->name,
                'response_minutes' => $t->effectiveResponseTimeMinutes(),
                'resolution_minutes' => $t->effectiveResolutionTimeMinutes(),
            ],
            'started_at' => $t->started_at?->toIso8601String(),
            'resolved_at' => $t->resolved_at?->toIso8601String(),
            'closed_at' => $t->closed_at?->toIso8601String(),
            'time_spent' => $t->actualResolutionTime(),
            'hold_reason' => $t->status === TicketStatus::ON_HOLD ? $t->hold_reason : null,
            'revision' => $t->revision_requested_at ? [
                'requested_at' => $t->revision_requested_at->toIso8601String(),
                'notes' => $t->revision_notes,
            ] : null,
            'report' => $t->resolution_notes || $t->findings ? [
                'service_type' => $t->service_type,
                'resolution_notes' => $t->resolution_notes,
                'findings' => $t->findings,
                'recommendation' => $t->recommendation,
                'resolved_by' => $t->resolvedBy?->name,
                'validated_at' => $t->validated_at?->toIso8601String(),
                'validation_notes' => $t->validation_notes,
            ] : null,
            'cannot_resolve' => $t->cannot_resolve ? [
                'findings' => $t->cannot_resolve_findings,
                'recommendation' => $t->cannot_resolve_recommendation,
            ] : null,
            'escalations' => $t->escalations->sortByDesc('escalated_at')->values()->map(fn ($e) => [
                'level' => $e->escalation_level,
                'reason' => $e->reason,
                'already_tried' => $e->resolution_notes,
                'by' => $e->escalatedBy?->name,
                'at' => $e->escalated_at ? Carbon::parse($e->escalated_at)->toIso8601String() : null,
            ]),
            'pending_reclassification' => $pendingReclass ? [
                'requested_by' => $pendingReclass->requestedBy?->name,
                'reason' => $pendingReclass->reason,
                'current' => trim("{$pendingReclass->current_subcategory_name} · {$pendingReclass->current_priority}", ' ·'),
                'proposed' => trim("{$pendingReclass->proposed_subcategory_name} · {$pendingReclass->proposed_priority}", ' ·'),
                'requested_at' => $pendingReclass->requested_at ? Carbon::parse($pendingReclass->requested_at)->toIso8601String() : null,
            ] : null,
            'service_report_url' => in_array($t->status, self::REPORT_STATUSES, true)
                ? route('mobile.tickets.service-report', $t)
                : null,
            'attachments' => AttachmentResource::collection($t->attachments->whereNull('ticket_message_id')->values()),
            'history' => $t->statusHistories->sortByDesc('changed_at')->values()->map(fn ($h) => [
                'status' => $h->new_status,
                'from' => $h->old_status,
                'notes' => $h->notes,
                'by' => $h->changedBy?->name,
                'at' => $h->changed_at ? Carbon::parse($h->changed_at)->toIso8601String() : null,
            ]),
            'actions' => StaffActions::availableFor($t, $request->user()),
        ];
    }

    // Relations the detail view needs, for eager loading.
    public const RELATIONS = [
        'user.department', 'assignedTo.role', 'slaCategory', 'workloadClass', 'resolvedBy',
        'statusHistories.changedBy', 'attachments', 'unreadMessages',
        'escalations.escalatedBy', 'reclassificationRequests.requestedBy',
    ];
}
