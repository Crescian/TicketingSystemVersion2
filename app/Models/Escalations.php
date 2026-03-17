<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Escalations extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'escalation_level',
        'escalated_by',
        'previous_tech_id',
        'reassigned_to',
        'reason',
        'resolution_notes',
        'escalated_at',
        'resolved_at',
    ];

    protected $casts = [
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(Tickets::class);
    }

    public function escalatedBy()
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function previousTech()
    {
        return $this->belongsTo(User::class, 'previous_tech_id');
    }

    public function reassignedTo()
    {
        return $this->belongsTo(User::class, 'reassigned_to');
    }
}
