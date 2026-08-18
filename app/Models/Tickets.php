<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Tickets extends Model
{
    use HasUuids;

    protected $fillable = [
        'ticket_number',
        'users_id',
        'assigned_to',

        'ticket_type',
        'request_category',
        'request_details',
        'asset',
        'subject',
        'concern',
        'location',

        'status',
        'pending_role',
        'escalation_level',

        // ── NEW HELP DESK FIELDS
        'position',
        'business_unit',
        'company',
        'department',

        'date_received',
        'time_received',
        'date_acknowledged',
        'time_acknowledged',

        'method',

        'started_at',
        'report_started_at',
        'resolved_at',
        'tech_acknowledged_at',
        'validated_at',
        'validation_notes',
        'closed_at',

        // ── SLA FIELDS
        'sla_category_id',
        'subcategory_name',
        'workload_class_id',
        'sla_due_at',
        'sla_risk_notified_at',
        'sla_breached_notified_at',
        'classification_reminded_at',
        'report_review_reminded_at',
        'requestor_confirmation_reminded_at',
        'helpdesk_ack_reminded_at',
        'helpdesk_classification_reminded_at',
        'tech_ack_reminded_at',
        'response_time_minutes',
        'resolution_time_minutes',
        'cannot_resolve',
        'cannot_resolve_findings',
        'cannot_resolve_recommendation',

        // ── Resolution detail (Technician Resolve modal) — feed the Service Report PDF
        'resolution_notes',
        'service_type',
        'findings',
        'recommendation',
        'resolved_by',

        // ── Workload time-slot scheduling (App\Services\TicketScheduler)
        'scheduled_start',
        'scheduled_end',
        'is_overtime',
        'queued_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'report_started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'tech_acknowledged_at' => 'datetime',
        'validated_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'sla_risk_notified_at' => 'datetime',
        'sla_breached_notified_at' => 'datetime',
        'classification_reminded_at' => 'datetime',
        'report_review_reminded_at' => 'datetime',
        'requestor_confirmation_reminded_at' => 'datetime',
        'helpdesk_ack_reminded_at' => 'datetime',
        'helpdesk_classification_reminded_at' => 'datetime',
        'tech_ack_reminded_at' => 'datetime',
        'cannot_resolve' => 'boolean',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
        'queued_at' => 'datetime',
        'is_overtime' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusHistories()
    {
        return $this->hasMany(TicketStatusHistories::class, 'ticket_id');
    }

    public function feedback()
    {
        return $this->hasOne(TicketFeedBack::class, 'ticket_id');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id');
    }


    // Helper: generate ticket number
    public static function generateTicketNumber(): string
    {
        $shortYear = now()->format('y'); // 26
        $prefix = "LGICT-{$shortYear}-";

        // Derived from the highest existing suffix for this year's prefix, not a row
        // count — a row count drifts the moment tickets are cancelled/deleted or a
        // ticket_number is hand-edited (e.g. via direct DB update).
        $maxSuffix = static::where('ticket_number', 'like', $prefix . '%')
            ->selectRaw('MAX(RIGHT(ticket_number, 4)::int) as max_suffix')
            ->value('max_suffix') ?? 0;

        $floor = config("ticketing.ticket_number_floors.{$shortYear}", 0);
        $maxSuffix = max($maxSuffix, $floor);

        return $prefix . str_pad($maxSuffix + 1, 4, '0', STR_PAD_LEFT);
    }
    public function escalations()
    {
        return $this->hasMany(\App\Models\Escalations::class, 'ticket_id');
    }
    public function reclassificationRequests()
    {
        return $this->hasMany(\App\Models\ReclassificationRequest::class, 'ticket_id');
    }
    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')
            ->orderBy('created_at', 'asc');
    }

    public function unreadMessages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')
            ->where('is_read', false)
            ->where('sender_id', '!=', auth()->id());
    }

    public function slaCategory()
    {
        return $this->belongsTo(SlaCategory::class, 'sla_category_id');
    }

    public function workloadClass()
    {
        return $this->belongsTo(WorkloadClass::class, 'workload_class_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Matching active SLA rule for this ticket's subcategory + priority.
    public function activeSlaRule(): ?SlaRule
    {
        if (!$this->subcategory_name || !$this->ticket_type) {
            return null;
        }

        return SlaRule::where('subcategory_name', $this->subcategory_name)
            ->where('priority', $this->ticket_type)
            ->where('is_active', true)
            ->first();
    }

    // Effective SLA minutes for this ticket — prefers the supervisor's per-ticket
    // override (set at Classify & Assign), falling back to the matched SlaRule for
    // tickets classified before overrides existed.
    public function effectiveResponseTimeMinutes(): ?int
    {
        return $this->response_time_minutes ?? $this->activeSlaRule()?->response_time_minutes;
    }

    public function effectiveResolutionTimeMinutes(): ?int
    {
        return $this->resolution_time_minutes ?? $this->activeSlaRule()?->resolution_time_minutes;
    }

    public function slaSecondsRemaining(): ?int
    {
        if (!$this->sla_due_at) {
            return null;
        }

        return (int) now()->diffInSeconds($this->sla_due_at, false);
    }

    public function isSlaBreached(): bool
    {
        $remaining = $this->slaSecondsRemaining();
        return $remaining !== null && $remaining <= 0;
    }

    public function isSlaAtRisk(): bool
    {
        if (!$this->sla_due_at || !$this->started_at || $this->isSlaBreached()) {
            return false;
        }

        $totalSeconds = $this->started_at->diffInSeconds($this->sla_due_at);
        $elapsedSeconds = $this->started_at->diffInSeconds(now());

        return $totalSeconds > 0 && $elapsedSeconds >= ($totalSeconds * 0.75);
    }

    // Wall-clock time between the SLA resolution clock starting and the ticket being
    // marked resolved — i.e. how long it actually took the technician, not the SLA target.
    public function actualResolutionTime(): ?string
    {
        if (!$this->started_at || !$this->resolved_at) {
            return null;
        }

        $totalMinutes = $this->started_at->diffInMinutes($this->resolved_at);
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return ($hours > 0 ? "{$hours}h " : '') . "{$minutes}m";
    }

    // Collapses the SLA category catalog (Hardware Support, Access & Account
    // Management, Printing Services, ...) down to the 6 broad buckets the Service
    // Report PDF checks off — anything that isn't an exact match falls to "Other".
    public function mainCategoryLabel(): ?string
    {
        $name = $this->slaCategory?->name;
        if (!$name) {
            return null;
        }

        return match (true) {
            str_contains($name, 'Hardware') => 'Hardware',
            str_contains($name, 'Software') => 'Software',
            str_contains($name, 'Network') => 'Network',
            str_contains($name, 'Access') || str_contains($name, 'Account') => 'Account / Access',
            str_contains($name, 'Preventive Maintenance') => 'Preventive Maintenance',
            default => 'Other',
        };
    }

    // "Level of Request" (L1-L4) for the Service Report PDF — driven by which tier
    // actually resolved the ticket (resolved_by's role), not a pre-work estimate:
    // Helpdesk = L1, IT Support Specialist = L2, Supervisor - Support Specialist /
    // IT Admin = L3, Manager = L4. Every closed ticket has a resolver, so this is
    // always determinable — unlike the old workload-class-based guess, which was
    // frequently blank.
    private const RESOLVER_ROLE_LEVELS = [
        'Helpdesk' => 'L1',
        'IT Support Specialist' => 'L2',
        'Supervisor - Support Specialist' => 'L3',
        'IT Admin' => 'L3',
        'Manager' => 'L4',
    ];

    public function levelOfRequest(): ?string
    {
        $role = $this->resolvedBy?->role?->role_name;
        return $role ? (self::RESOLVER_ROLE_LEVELS[$role] ?? null) : null;
    }
}
