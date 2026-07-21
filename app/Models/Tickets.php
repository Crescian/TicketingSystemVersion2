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
        'resolved_at',

        // ── SLA FIELDS
        'sla_category_id',
        'subcategory_name',
        'sla_due_at',
        'sla_risk_notified_at',
        'sla_breached_notified_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'sla_risk_notified_at' => 'datetime',
        'sla_breached_notified_at' => 'datetime',
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


    // Helper: generate ticket number
    public static function generateTicketNumber(): string
    {
        $fullYear = now()->format('Y'); // 2026
        $shortYear = now()->format('y'); // 26

        $count = static::whereYear('created_at', $fullYear)->count() + 1;

        return 'LGICT-' . $shortYear . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    public function escalations()
    {
        return $this->hasMany(\App\Models\Escalations::class, 'ticket_id');
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
}
