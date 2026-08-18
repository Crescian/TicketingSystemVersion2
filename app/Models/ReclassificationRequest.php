<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ReclassificationRequest extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'requested_by',
        'reviewed_by',
        'current_sla_category_id',
        'current_subcategory_name',
        'current_priority',
        'proposed_sla_category_id',
        'proposed_subcategory_name',
        'proposed_priority',
        'proposed_response_time_minutes',
        'proposed_resolution_time_minutes',
        'reason',
        'status',
        'review_notes',
        'requested_at',
        'reviewed_at',
        'reminded_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'reminded_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class, 'ticket_id');
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
